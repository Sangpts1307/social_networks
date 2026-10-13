<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Http\Request;

class AdminAuthorization
{
    /** @var array<string, array<int, string>> */
    private array $permissionCache = [];

    /** @return array<int, string> */
    public function permissions(User $user): array
    {
        return $this->permissionCache[$user->id] ??= $this->resolvePermissions($user);
    }

    public function allows(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissions($user), true);
    }

    public function require(Request $request, string $permission): User
    {
        /** @var User $actor */
        $actor = $request->user();

        abort_unless($this->allows($actor, $permission), 403, 'Bạn không có quyền thực hiện thao tác này.');

        return $actor;
    }

    public function belongsToOrganization(User $actor, ?string $organizationId): bool
    {
        return $organizationId !== null && $actor->organization_id === $organizationId;
    }

    /** @return array<int, string> */
    private function resolvePermissions(User $user): array
    {
        $assignments = UserRoleAssignment::query()
            ->with('role.permissions:id,code')
            ->where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();

        if ($assignments->contains(fn (UserRoleAssignment $assignment): bool => $assignment->role?->code === 'super_admin')) {
            return Permission::query()->pluck('code')->all();
        }

        return $assignments
            ->flatMap(fn (UserRoleAssignment $assignment) => $assignment->role?->permissions->pluck('code') ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
