<?php

namespace App\Services;

use App\Models\OrgUnit;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminAuthorization
{
    /** Grants deliberately retain the association between actions and scope. */
    public function grants(User $user): array
    {
        return UserRoleAssignment::query()->with('role.permissions:id,code')
            ->where('user_id', $user->id)->where('organization_id', $user->organization_id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get()->map(function (UserRoleAssignment $assignment) use ($user): array {
                $scope = $assignment->scope_type;
                $units = $scope === 'org_unit' && $assignment->org_unit_id
                    ? $this->unitIds($user, $assignment->org_unit_id, $assignment->include_descendants) : [];

                return [
                    'assignment_id' => $assignment->id, 'role_id' => $assignment->role_id,
                    'role_code' => $assignment->role?->code,
                    'scope_type' => $scope, 'org_unit_id' => $assignment->org_unit_id,
                    'org_unit_ids' => $units, 'include_descendants' => $assignment->include_descendants,
                    'expires_at' => $assignment->expires_at?->toISOString(),
                    'permissions' => $assignment->role?->code === 'super_admin'
                        ? Permission::query()->pluck('code')->all()
                        : ($assignment->role?->permissions->pluck('code')->all() ?? []),
                ];
            })->all();
    }

    public function permissions(User $user): array
    {
        return collect($this->grants($user))->pluck('permissions')->flatten()->unique()->values()->all();
    }

    public function allows(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissions($user), true);
    }

    public function require(Request $request, string $permission): User
    {
        $actor = $request->user();
        abort_unless($this->allows($actor, $permission), 403, 'Bạn không có quyền thực hiện thao tác này.');

        return $actor;
    }

    public function organizationAllowed(User $actor, string $permission): bool
    {
        return collect($this->grants($actor))->contains(fn ($g) => $g['scope_type'] === 'organization' && in_array($permission, $g['permissions'], true));
    }

    public function requireOrganization(User $actor, string $permission): void
    {
        abort_unless($this->organizationAllowed($actor, $permission), 403, 'Thao tác này yêu cầu quyền trên toàn tổ chức.');
    }

    public function unitIds(User $actor, string $root, bool $descendants = false): array
    {
        $units = OrgUnit::query()->where('organization_id', $actor->organization_id)->where('status', 'active')->get(['id', 'parent_id']);
        if (! $units->contains('id', $root)) {
            return [];
        }
        $ids = [$root];
        if ($descendants) {
            do {
                $before = count($ids);
                $ids = array_values(array_unique([...$ids, ...$units->whereIn('parent_id', $ids)->pluck('id')->all()]));
            } while (count($ids) > $before);
        }

        return $ids;
    }

    public function allowedUnits(User $actor, string $permission): array
    {
        return collect($this->grants($actor))->filter(fn ($g) => in_array($permission, $g['permissions'], true))
            ->pluck('org_unit_ids')->flatten()->unique()->values()->all();
    }

    public function scopeUsers(Builder $query, User $actor, string $permission): Builder
    {
        $query->where('organization_id', $actor->organization_id);
        if ($this->organizationAllowed($actor, $permission)) {
            return $query;
        }
        $units = $this->allowedUnits($actor, $permission);
        $self = collect($this->grants($actor))->contains(fn ($g) => $g['scope_type'] === 'self' && in_array($permission, $g['permissions'], true));

        return $query->where(function (Builder $q) use ($actor, $units, $self): void {
            $q->whereRaw('1 = 0');
            if ($self) {
                $q->orWhere('users.id', $actor->id);
            }
            $q->orWhereIn('primary_org_unit_id', $units)
                ->orWhereHas('orgUnitMemberships', fn ($m) => $m->where('organization_id', $actor->organization_id)->whereIn('org_unit_id', $units)
                    ->where(fn ($j) => $j->whereNull('joined_at')->orWhere('joined_at', '<=', now()))
                    ->where(fn ($l) => $l->whereNull('left_at')->orWhere('left_at', '>', now())));
        });
    }

    public function requireUser(User $actor, User $target, string $permission): void
    {
        abort_unless($this->scopeUsers(User::query(), $actor, $permission)->whereKey($target->id)->exists(), 404);
    }

    public function belongsToOrganization(User $actor, ?string $organizationId): bool
    {
        return $organizationId !== null && $actor->organization_id === $organizationId;
    }

    public function context(User $user): array
    {
        $memberships = $user->orgUnitMemberships()->where('organization_id', $user->organization_id)
            ->where(fn ($q) => $q->whereNull('joined_at')->orWhere('joined_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('left_at')->orWhere('left_at', '>', now()))->pluck('org_unit_id')->all();
        if ($user->primary_org_unit_id) {
            $memberships[] = $user->primary_org_unit_id;
        }
        $memberships = OrgUnit::query()->where('organization_id', $user->organization_id)->where('status', 'active')->whereIn('id', $memberships)->pluck('id')->all();

        return ['version' => 1, 'grants' => $this->grants($user), 'org_unit_ids' => $memberships,
            'organization_unit_ids' => OrgUnit::query()->where('organization_id', $user->organization_id)->where('status', 'active')->pluck('id')->all()];
    }
}
