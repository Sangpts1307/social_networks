<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
        private readonly AdminAuditLogger $audit,
    ) {}

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

    /** @return array<string, int|null> */
    private function pagination($paginator): array
    {
        return ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }
}
