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

class AdminController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
        private readonly AdminAuditLogger $audit,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.read');
        $organizationId = $actor->organization_id;

        return response()->json(['data' => [
            'users_total' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->count(),
            'users_active' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->where('status', 'active')->count(),
            'users_locked' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->where(fn (Builder $query) => $query->whereNotNull('locked_at')->orWhere('locked_until', '>', now()))->count(),
            'users_must_change_password' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->whereNotNull('must_change_password_at')->count(),
            'org_units_total' => $this->authorization->organizationAllowed($actor, 'identity.org_units.read') ? OrgUnit::query()->where('organization_id', $organizationId)->count() : count($this->authorization->allowedUnits($actor, 'identity.org_units.read')),
            'roles_total' => Role::query()->count(),
        ]]);
    }

    public function users(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.read');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = $this->authorization->scopeUsers(User::query()->with('roles:id,code,name'), $actor, 'identity.users.read');
        if (! empty($data['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $data['q']).'%';
            $query->where(fn (Builder $builder) => $builder->where('email', 'like', $term)->orWhere('display_name', 'like', $term)->orWhere('employee_code', 'like', $term));
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        $users = $query->orderBy('display_name')->paginate($data['per_page'] ?? 20)->withQueryString();

        return response()->json(['data' => $users->getCollection()->map(fn (User $user) => $this->userView($user)), 'meta' => $this->pagination($users)]);
    }

    public function user(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.read');
        $this->authorization->requireUser($actor, $user, 'identity.users.read');
        $user->load('roles:id,code,name', 'profile', 'primaryOrgUnit:id,code,name');

        return response()->json(['data' => $this->userView($user, true)]);
    }

    public function updateUserStatus(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.update');
        $this->authorization->requireUser($actor, $user, 'identity.users.update');
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive', 'suspended'])]]);
        abort_if($user->is($actor) && $data['status'] !== 'active', 422, 'Bạn không thể tự vô hiệu hóa tài khoản quản trị của mình.');
        $previousStatus = $user->status;
        $user->update(['status' => $data['status']]);
        $this->audit->record($request, $actor, 'identity.user.status_updated', User::class, $user->id, ['from' => $previousStatus, 'to' => $user->status]);

        return response()->json(['data' => $this->userView($user->fresh()->load('roles:id,code,name'))]);
    }

    public function unlockUser(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.unlock');
        $this->authorization->requireUser($actor, $user, 'identity.users.unlock');
        $user->update(['locked_at' => null, 'locked_until' => null, 'failed_login_count' => 0]);
        $this->audit->record($request, $actor, 'identity.user.unlocked', User::class, $user->id);

        return response()->json(['data' => $this->userView($user->fresh()->load('roles:id,code,name'))]);
    }

    public function forcePasswordChange(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.update');
        $this->authorization->requireUser($actor, $user, 'identity.users.update');
        abort_if($user->is($actor), 422, 'Bạn không thể buộc chính mình đổi mật khẩu từ trang quản trị.');
        $user->update(['must_change_password_at' => now()]);
        $this->audit->record($request, $actor, 'identity.user.password_change_forced', User::class, $user->id);

        return response()->json(['data' => $this->userView($user->fresh()->load('roles:id,code,name'))]);
    }

    public function orgUnits(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.read');
        $query = OrgUnit::query()->where('organization_id', $actor->organization_id);
        if (! $this->authorization->organizationAllowed($actor, 'identity.org_units.read')) {
            $query->whereIn('id', $this->authorization->allowedUnits($actor, 'identity.org_units.read'));
        }
        $units = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['data' => $units->map(fn (OrgUnit $unit) => $this->orgUnitView($unit))]);
    }

    public function storeOrgUnit(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $unit = OrgUnit::query()->create([...$this->validateOrgUnit($request, $actor), 'organization_id' => $actor->organization_id]);
        $this->audit->record($request, $actor, 'identity.org_unit.created', OrgUnit::class, $unit->id, ['code' => $unit->code]);

        return response()->json(['data' => $this->orgUnitView($unit)], 201);
    }

    public function updateOrgUnit(Request $request, OrgUnit $orgUnit): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $this->ensureOrganization($actor, $orgUnit->organization_id);
        $orgUnit->update($this->validateOrgUnit($request, $actor, $orgUnit));
        $this->audit->record($request, $actor, 'identity.org_unit.updated', OrgUnit::class, $orgUnit->id, ['code' => $orgUnit->code]);

        return response()->json(['data' => $this->orgUnitView($orgUnit->fresh())]);
    }

    public function updateOrgUnitManager(Request $request, OrgUnit $orgUnit): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $this->ensureOrganization($actor, $orgUnit->organization_id);
        $data = $request->validate(['manager_user_id' => ['nullable', 'uuid']]);
        if ($data['manager_user_id'] ?? null) {
            abort_unless(User::query()->whereKey($data['manager_user_id'])->where('organization_id', $actor->organization_id)->exists(), 422, 'Quản lý phải thuộc cùng tổ chức.');
        }
        $orgUnit->update($data);
        $this->audit->record($request, $actor, 'identity.org_unit.manager_updated', OrgUnit::class, $orgUnit->id, ['manager_user_id' => $orgUnit->manager_user_id]);

        return response()->json(['data' => $this->orgUnitView($orgUnit->fresh())]);
    }

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

    public function auditLogs(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.audit.read');
        $this->authorization->requireOrganization($actor, 'identity.audit.read');
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $logs = AuditLog::query()->where('organization_id', $actor->organization_id)->latest('created_at')->paginate($data['per_page'] ?? 20)->withQueryString();

        return response()->json(['data' => $logs->items(), 'meta' => $this->pagination($logs)]);
    }

    private function ensureOrganization(User $actor, ?string $organizationId): void
    {
        abort_unless($this->authorization->belongsToOrganization($actor, $organizationId), 404);
    }

    /** @return array<string, mixed> */
    private function validateOrgUnit(Request $request, User $actor, ?OrgUnit $orgUnit = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'uuid'],
            'code' => ['required', 'string', 'max:64', Rule::unique('org_units', 'code')->where('organization_id', $actor->organization_id)->ignore($orgUnit)],
            'name' => ['required', 'string', 'max:255'],
            'unit_type' => ['required', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        if ($data['parent_id'] ?? null) {
            abort_unless($data['parent_id'] !== $orgUnit?->id && OrgUnit::query()->whereKey($data['parent_id'])->where('organization_id', $actor->organization_id)->exists(), 422, 'Đơn vị cha không hợp lệ.');
        }

        $parent = $data['parent_id'] ?? null;
        $seen = $orgUnit ? [$orgUnit->id] : [];
        while ($parent) {
            abort_if(in_array($parent, $seen, true), 422, 'Cây phòng ban không được có vòng lặp.');
            $seen[] = $parent;
            $parent = OrgUnit::query()->where('organization_id', $actor->organization_id)->whereKey($parent)->value('parent_id');
        }

        return $data;
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
    private function userView(User $user, bool $detail = false): array
    {
        $view = ['id' => $user->id, 'organization_id' => $user->organization_id, 'email' => $user->email, 'employee_code' => $user->employee_code,
            'display_name' => $user->display_name, 'status' => $user->status, 'locked' => $user->locked_at !== null || ($user->locked_until?->isFuture() ?? false),
            'must_change_password' => $user->must_change_password_at !== null, 'last_login_at' => $user->last_login_at?->toISOString(),
            'roles' => $user->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'assignment_id' => $role->pivot?->id,
                'code' => $role->code,
                'name' => $role->name,
                'scope_type' => $role->pivot?->scope_type,
                'org_unit_id' => $role->pivot?->org_unit_id,
                'include_descendants' => (bool) $role->pivot?->include_descendants,
                'expires_at' => $role->pivot?->expires_at,
            ])->values()];
        if ($detail) {
            $view['authorization'] = $this->authorization->context($user);
            $view['primary_org_unit'] = $user->primaryOrgUnit ? ['id' => $user->primaryOrgUnit->id, 'code' => $user->primaryOrgUnit->code, 'name' => $user->primaryOrgUnit->name] : null;
            $view['profile'] = $user->profile?->only(['job_title', 'phone', 'avatar_key']);
        }

        return $view;
    }

    /** @return array<string, mixed> */
    private function orgUnitView(OrgUnit $unit): array
    {
        return ['id' => $unit->id, 'organization_id' => $unit->organization_id, 'parent_id' => $unit->parent_id, 'manager_user_id' => $unit->manager_user_id,
            'code' => $unit->code, 'name' => $unit->name, 'unit_type' => $unit->unit_type, 'status' => $unit->status, 'sort_order' => $unit->sort_order];
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

    /** @return array<string, int|null> */
    private function pagination($paginator): array
    {
        return ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }
}
