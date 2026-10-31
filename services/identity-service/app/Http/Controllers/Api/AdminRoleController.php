<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\OrgUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AdminRoleController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
        private readonly AdminAuditLogger $audit,
    ) {}

    public function roles(Request $request): JsonResponse
    {
        $this->authorization->require($request, 'identity.roles.read');
        $roles = Role::query()->with('permissions:id,code,name')->orderBy('name')->get();

        return response()->json(['data' => $roles->map(fn (Role $role) => $this->roleView($role))]);
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->authorization->require($request, 'identity.roles.read');

        return response()->json(['data' => Permission::query()->orderBy('resource')->orderBy('action')->get(['id', 'code', 'name', 'description', 'resource', 'action'])]);
    }

    public function syncRolePermissions(Request $request, Role $role): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.roles.manage');
        $this->authorization->requireOrganization($actor, 'identity.roles.manage');
        abort_if($this->actorHasRole($actor, $role->id), 422, 'Bạn không thể sửa permission của role đang được gán cho chính mình.');
        $data = $request->validate(['permission_ids' => ['present', 'array'], 'permission_ids.*' => ['uuid', 'distinct', 'exists:permissions,id']]);
        if (! $this->isSuperAdmin($actor)) {
            $requested = Permission::query()->whereIn('id', $data['permission_ids'])->pluck('code')->all();
            $existing = $role->permissions()->pluck('code')->all();
            foreach (array_unique([...$requested, ...$existing]) as $permission) {
                $this->authorization->requireOrganization($actor, $permission);
            }
            abort_if($role->code === 'super_admin', 403);
        }
        $role->permissions()->sync($data['permission_ids']);
        $this->audit->record($request, $actor, 'identity.role.permissions_synced', Role::class, $role->id, ['permission_ids' => $data['permission_ids']]);

        return response()->json(['data' => $this->roleView($role->fresh()->load('permissions:id,code,name'))]);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.roles.manage');
        $this->authorization->requireUser($actor, $user, 'identity.roles.manage');
        abort_if($user->is($actor), 422, 'Bạn không thể tự gán thêm role.');
        $data = $request->validate(['role_id' => ['required', 'uuid', 'exists:roles,id'], 'org_unit_id' => ['nullable', 'uuid'], 'expires_at' => ['nullable', 'date', 'after:now'], 'scope_type' => ['sometimes', Rule::in(['organization', 'org_unit', 'self'])], 'include_descendants' => ['sometimes', 'boolean']]);
        if ($data['org_unit_id'] ?? null) {
            abort_unless(OrgUnit::query()->whereKey($data['org_unit_id'])->where('organization_id', $actor->organization_id)->exists(), 422, 'Phạm vi phòng ban không hợp lệ.');
        }
        $scope = $data['scope_type'] ?? (! empty($data['org_unit_id']) ? 'org_unit' : 'organization');
        abort_if(($scope === 'org_unit') !== ! empty($data['org_unit_id']), 422, 'Scope phòng ban phải có org_unit_id; scope khác không được có org_unit_id.');
        abort_if($scope !== 'org_unit' && ($data['include_descendants'] ?? false), 422, 'Chỉ scope phòng ban được bao gồm đơn vị con.');
        $role = Role::with('permissions')->findOrFail($data['role_id']);
        $this->ensureDelegation($actor, $role, $scope, $data['org_unit_id'] ?? null, $data['include_descendants'] ?? false, $data['expires_at'] ?? null);
        $assignment = UserRoleAssignment::query()->updateOrCreate(
            ['organization_id' => $actor->organization_id, 'user_id' => $user->id, 'role_id' => $data['role_id'], 'org_unit_id' => $data['org_unit_id'] ?? null, 'scope_type' => $scope],
            ['assigned_by' => $actor->id, 'assigned_at' => now(), 'expires_at' => $data['expires_at'] ?? null, 'include_descendants' => $data['include_descendants'] ?? false],
        );
        $this->audit->record($request, $actor, 'identity.user.role_assigned', UserRoleAssignment::class, $assignment->id, ['user_id' => $user->id, 'role_id' => $assignment->role_id, 'scope_type' => $assignment->scope_type, 'org_unit_id' => $assignment->org_unit_id, 'include_descendants' => $assignment->include_descendants, 'expires_at' => $assignment->expires_at?->toISOString()]);

        return response()->json(['data' => $this->assignmentView($assignment->load('role:id,code,name'))], 201);
    }

    public function removeRoleAssignment(Request $request, User $user, UserRoleAssignment $assignment): Response
    {
        $actor = $this->authorization->require($request, 'identity.roles.manage');
        $this->authorization->requireUser($actor, $user, 'identity.roles.manage');
        abort_unless($assignment->user_id === $user->id && $assignment->organization_id === $actor->organization_id, 404);
        abort_if($user->is($actor), 422, 'Bạn không thể tự gỡ role của mình.');
        $this->ensureDelegation($actor, $assignment->role, $assignment->scope_type, $assignment->org_unit_id, $assignment->include_descendants, $assignment->expires_at?->toISOString());
        $assignmentId = $assignment->id;
        $assignment->delete();
        $this->audit->record($request, $actor, 'identity.user.role_removed', UserRoleAssignment::class, $assignmentId, ['user_id' => $user->id]);

        return response()->noContent();
    }

    private function isSuperAdmin(User $actor): bool
    {
        return collect($this->authorization->grants($actor))->contains(fn ($g) => $g['role_code'] === 'super_admin' && $g['scope_type'] === 'organization');
    }

    private function ensureDelegation(User $actor, Role $role, string $scope, ?string $unit, bool $descendants, ?string $expires): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }
        abort_if($role->code === 'super_admin' || $scope === 'self', 403, 'Không thể ủy quyền role này.');
        $requestedUnits = $unit ? $this->authorization->unitIds($actor, $unit, $descendants) : [];
        $permissions = array_unique(['identity.roles.manage', ...$role->permissions->pluck('code')->all()]);
        // A delegation may not outlive or exceed the action's source grant.
        foreach ($permissions as $permission) {
            $coveredUnits = [];
            $organizationCovered = false;
            foreach ($this->authorization->grants($actor) as $grant) {
                if (! in_array($permission, $grant['permissions'], true)) {
                    continue;
                }
                if ($grant['expires_at'] && (! $expires || strtotime($expires) > strtotime($grant['expires_at']))) {
                    continue;
                }
                if ($grant['scope_type'] === 'organization') {
                    $organizationCovered = true;
                }
                $coveredUnits = array_merge($coveredUnits, $grant['org_unit_ids']);
            }
            abort_unless($organizationCovered || ($scope === 'org_unit' && $requestedUnits && ! array_diff($requestedUnits, $coveredUnits)), 403, 'Quyền cấp vượt phạm vi hoặc thời hạn được ủy quyền.');
        }
    }

    private function actorHasRole(User $actor, string $roleId): bool
    {
        return UserRoleAssignment::query()->where('user_id', $actor->id)->where('role_id', $roleId)->where('organization_id', $actor->organization_id)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
    }

    /** @return array<string, mixed> */
    private function roleView(Role $role): array
    {
        return ['id' => $role->id, 'code' => $role->code, 'name' => $role->name, 'description' => $role->description, 'is_system' => $role->is_system,
            'permissions' => $role->permissions->map(fn (Permission $permission) => ['id' => $permission->id, 'code' => $permission->code, 'name' => $permission->name])->values()];
    }

    /** @return array<string, mixed> */
    private function assignmentView(UserRoleAssignment $assignment): array
    {
        return ['id' => $assignment->id, 'user_id' => $assignment->user_id, 'role_id' => $assignment->role_id, 'org_unit_id' => $assignment->org_unit_id,
            'scope_type' => $assignment->scope_type, 'include_descendants' => $assignment->include_descendants,
            'role' => ['id' => $assignment->role->id, 'code' => $assignment->role->code, 'name' => $assignment->role->name], 'expires_at' => $assignment->expires_at?->toISOString()];
    }
}
