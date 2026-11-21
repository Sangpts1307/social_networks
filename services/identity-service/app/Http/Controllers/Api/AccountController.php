<?php
namespace App\Http\Controllers\Api;

use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Models\UserSession;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController
{
    public function __construct(private AdminAuthorization $auth, private AdminAuditLogger $audit, private AdminRoleController $roles) {}

    public function store(Request $request)
    {
        $actor = $this->auth->require($request, 'identity.users.create');
        $data = $request->validate(['email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'gender' => 'nullable|in:male,female,other', 'birth_date' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'display_name' => 'required|string|max:255', 'primary_org_unit_id' => 'required|uuid',
            'password' => ['required', 'string', 'max:72'], 'role_id' => ['nullable', 'uuid', 'exists:roles,id']]);
        abort_if(strlen($data['password']) > 72, 422, 'Password exceeds 72 bytes.');
        $this->unit($actor, $data['primary_org_unit_id'], 'identity.users.create');
        $role = isset($data['role_id']) ? Role::with('permissions')->findOrFail($data['role_id']) : Role::where('code', 'member')->firstOrFail();
        if (isset($data['role_id'])) {
            $this->auth->require($request, 'identity.roles.manage');
            $this->roles->ensureDelegation($actor, $role, 'organization', null, false, null);
        }
        $email = strtolower(trim($data['email']));
        abort_if(User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists(), 422, 'Email is unavailable.');
        $domains = DB::table('organization_email_domains')->where('organization_id', $actor->organization_id)->pluck('domain')->map(fn ($d) => strtolower($d))->all();
        abort_unless(in_array(substr(strrchr($email, '@'), 1), $domains, true), 422, 'Corporate email domain required.');
        return DB::transaction(function () use ($request, $actor, $data, $email, $role) {
            // Serialize account creation within the organization so two requests cannot claim the same code.
            DB::table('organizations')->where('id', $actor->organization_id)->lockForUpdate()->first();
            $lastNumber = User::withTrashed()->where('organization_id', $actor->organization_id)
                ->where('employee_code', 'like', 'NV%')
                ->pluck('employee_code')
                ->reduce(fn (int $max, ?string $code): int => preg_match('/^NV([0-9]+)$/', $code ?? '', $matches)
                    ? max($max, (int) $matches[1]) : $max, 0);
            $employeeCode = 'NV'.str_pad((string) ($lastNumber + 1), 2, '0', STR_PAD_LEFT);
            $user = User::create(['organization_id' => $actor->organization_id, 'email' => $email, 'employee_code' => $employeeCode,
                'display_name' => trim($data['display_name']), 'primary_org_unit_id' => $data['primary_org_unit_id'],
                'password_hash' => Hash::make($data['password']), 'status' => 'active', 'must_change_password_at' => now(), 'password_version' => 1]);
            $profile = array_intersect_key($data, array_flip(['birth_date', 'gender', 'job_title']));
            if ($profile) {
                $user->profile()->updateOrCreate(['user_id' => $user->id], $profile);
            }
            $user->orgUnitMemberships()->create(['organization_id' => $actor->organization_id, 'org_unit_id' => $data['primary_org_unit_id'], 'is_primary' => true, 'joined_at' => now()]);
            UserRoleAssignment::create(['organization_id' => $actor->organization_id, 'user_id' => $user->id, 'role_id' => $role->id,
                'scope_type' => 'organization', 'include_descendants' => false, 'assigned_by' => $actor->id, 'assigned_at' => now()]);
            $this->audit->record($request, $actor, 'identity.user.created', User::class, $user->id);
            return response()->json(['data' => ['id' => $user->id, 'display_name' => $user->display_name, 'email' => $user->email, 'employee_code' => $employeeCode, 'role_id' => $role->id, 'must_change_password' => true]], 201);
        });
    }

    public function update(Request $request, User $user)
    {
        $actor = $this->auth->require($request, 'identity.users.update');
        $this->auth->requireUser($actor, $user, 'identity.users.update');
        $data = $request->validate(['display_name' => 'sometimes|required|string|max:255', 'primary_org_unit_id' => 'sometimes|required|uuid',
            'gender' => 'nullable|in:male,female,other', 'birth_date' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'job_title' => 'sometimes|nullable|string|max:255',
            'employee_code' => ['sometimes', 'nullable', 'string', 'max:64', Rule::unique('users', 'employee_code')->where('organization_id', $actor->organization_id)->ignore($user->id)],
            'password' => ['sometimes', 'required', 'string', 'max:72'],
            'role_id' => ['sometimes', 'required', 'uuid', 'exists:roles,id'], 'replace_role_assignment_id' => ['sometimes', 'uuid']]);
        abort_if($data === [], 422, 'No changes supplied.');
        if (isset($data['primary_org_unit_id'])) $this->unit($actor, $data['primary_org_unit_id'], 'identity.users.update');
        if (isset($data['password'])) {
            abort_if($user->is($actor), 422, 'Use your password change endpoint.');
            abort_if(strlen($data['password']) > 72, 422, 'Password exceeds 72 bytes.');
        }
        $role = null;
        $oldAssignment = null;
        if (isset($data['role_id'])) {
            $this->auth->require($request, 'identity.roles.manage');
            $this->auth->requireUser($actor, $user, 'identity.roles.manage');
            $role = Role::with('permissions')->findOrFail($data['role_id']);
            $this->roles->ensureDelegation($actor, $role, 'organization', null, false, null);
            if (isset($data['replace_role_assignment_id'])) {
                $oldAssignment = UserRoleAssignment::where('organization_id', $actor->organization_id)
                    ->where('user_id', $user->id)->findOrFail($data['replace_role_assignment_id']);
            }
        }
        return DB::transaction(function () use ($request, $actor, $user, $data, $role, $oldAssignment) {
            $user->update(array_intersect_key($data, array_flip(['display_name', 'primary_org_unit_id', 'employee_code'])));
            if (isset($data['password'])) {
                $user->update(['password_hash' => Hash::make($data['password']), 'password_version' => $user->password_version + 1,
                    'must_change_password_at' => now(), 'password_changed_at' => now()]);
                UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
            if ($role && $oldAssignment?->role_id !== $role->id) {
                $oldAssignment?->delete();
                UserRoleAssignment::firstOrCreate(
                    ['organization_id' => $actor->organization_id, 'user_id' => $user->id, 'role_id' => $role->id,
                        'org_unit_id' => null, 'scope_type' => 'organization'],
                    ['assigned_by' => $actor->id, 'assigned_at' => now(), 'include_descendants' => false],
                );
            }
            if (isset($data['primary_org_unit_id'])) {
                $user->orgUnitMemberships()->update(['is_primary' => false]);
                $user->orgUnitMemberships()->updateOrCreate(['org_unit_id' => $data['primary_org_unit_id']], [
                    'organization_id' => $actor->organization_id, 'is_primary' => true, 'joined_at' => now(), 'left_at' => null]);
            }
            $profile = array_intersect_key($data, array_flip(['birth_date', 'gender', 'job_title']));
            if ($profile) {
                $user->profile()->updateOrCreate(['user_id' => $user->id], $profile);
            }
            $this->audit->record($request, $actor, 'identity.user.updated', User::class, $user->id, ['fields' => array_keys($data)]);
            return response()->json(['data' => ['id' => $user->id, 'display_name' => $user->display_name, 'employee_code' => $user->employee_code, 'primary_org_unit_id' => $user->primary_org_unit_id, 'role_id' => $role?->id]]);
        });
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $this->auth->require($request, 'identity.users.update');
        $this->auth->requireUser($actor, $user, 'identity.users.update');
        abort_if($actor->is($user), 422, 'Bạn không thể xoá tài khoản của chính mình.');
        abort_if($user->roles()->where('code', 'super_admin')->exists(), 422, 'Không thể xoá quản trị viên hệ thống.');
        DB::transaction(function () use ($request, $actor, $user) {
            UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $user->delete();
            $this->audit->record($request, $actor, 'identity.user.deleted', User::class, $user->id);
        });
        return response()->noContent();
    }

    public function resetPassword(Request $request, User $user)
    {
        $actor = $this->auth->require($request, 'identity.users.update');
        $this->auth->requireUser($actor, $user, 'identity.users.update');
        abort_if($actor->id === $user->id, 422, 'Use your password change endpoint.');
        if ($user->roles()->where('code', 'super_admin')->exists()) $this->auth->requireOrganization($actor, 'identity.roles.manage');
        $data = $request->validate(['password' => ['required', 'string', 'max:72', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        abort_if(strlen($data['password']) > 72, 422, 'Password exceeds 72 bytes.');
        DB::transaction(function () use ($request, $actor, $user, $data) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->update(['password_hash' => Hash::make($data['password']), 'password_version' => $locked->password_version + 1,
                'must_change_password_at' => now(), 'password_changed_at' => now()]);
            UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $this->audit->record($request, $actor, 'identity.user.password_reset', User::class, $user->id);
        });
        return response()->noContent();
    }

    private function unit(User $actor, string $id, string $permission): void
    {
        abort_unless(OrgUnit::where('organization_id', $actor->organization_id)->where('status', 'active')->whereKey($id)->exists(), 422, 'Invalid organizational unit.');
        abort_unless($this->auth->organizationAllowed($actor, $permission) || in_array($id, $this->auth->allowedUnits($actor, $permission), true), 403);
    }
}
