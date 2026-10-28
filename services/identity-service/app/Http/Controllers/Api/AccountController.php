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
    public function __construct(private AdminAuthorization $auth, private AdminAuditLogger $audit) {}

    public function store(Request $request)
    {
        $actor = $this->auth->require($request, 'identity.users.create');
        $data = $request->validate(['email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'employee_code' => ['nullable', 'string', 'max:64', Rule::unique('users')->where('organization_id', $actor->organization_id)],
            'display_name' => 'required|string|max:255', 'primary_org_unit_id' => 'required|uuid',
            'password' => ['required', 'string', 'max:72', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        abort_if(strlen($data['password']) > 72, 422, 'Password exceeds 72 bytes.');
        $this->unit($actor, $data['primary_org_unit_id'], 'identity.users.create');
        $email = strtolower(trim($data['email']));
        abort_if(User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists(), 422, 'Email is unavailable.');
        $domains = DB::table('organization_email_domains')->where('organization_id', $actor->organization_id)->pluck('domain')->map(fn ($d) => strtolower($d))->all();
        abort_unless(in_array(substr(strrchr($email, '@'), 1), $domains, true), 422, 'Corporate email domain required.');
        return DB::transaction(function () use ($request, $actor, $data, $email) {
            $user = User::create(['organization_id' => $actor->organization_id, 'email' => $email, 'employee_code' => $data['employee_code'] ?? null,
                'display_name' => trim($data['display_name']), 'primary_org_unit_id' => $data['primary_org_unit_id'],
                'password_hash' => Hash::make($data['password']), 'status' => 'active', 'must_change_password_at' => now(), 'password_version' => 1]);
            $user->orgUnitMemberships()->create(['organization_id' => $actor->organization_id, 'org_unit_id' => $data['primary_org_unit_id'], 'is_primary' => true, 'joined_at' => now()]);
            $role = Role::where('code', 'member')->firstOrFail();
            UserRoleAssignment::create(['organization_id' => $actor->organization_id, 'user_id' => $user->id, 'role_id' => $role->id,
                'scope_type' => 'organization', 'include_descendants' => false, 'assigned_by' => $actor->id, 'assigned_at' => now()]);
            $this->audit->record($request, $actor, 'identity.user.created', User::class, $user->id);
            return response()->json(['data' => ['id' => $user->id, 'display_name' => $user->display_name, 'email' => $user->email, 'must_change_password' => true]], 201);
        });
    }

    public function update(Request $request, User $user)
    {
        $actor = $this->auth->require($request, 'identity.users.update');
        $this->auth->requireUser($actor, $user, 'identity.users.update');
        $data = $request->validate(['display_name' => 'sometimes|required|string|max:255', 'primary_org_unit_id' => 'sometimes|required|uuid',
            'job_title' => 'sometimes|nullable|string|max:255']);
        abort_if($data === [], 422, 'No changes supplied.');
        if (isset($data['primary_org_unit_id'])) $this->unit($actor, $data['primary_org_unit_id'], 'identity.users.update');
        return DB::transaction(function () use ($request, $actor, $user, $data) {
            $user->update(array_intersect_key($data, array_flip(['display_name', 'primary_org_unit_id'])));
            if (isset($data['primary_org_unit_id'])) {
                $user->orgUnitMemberships()->update(['is_primary' => false]);
                $user->orgUnitMemberships()->updateOrCreate(['org_unit_id' => $data['primary_org_unit_id']], [
                    'organization_id' => $actor->organization_id, 'is_primary' => true, 'joined_at' => now(), 'left_at' => null]);
            }
            if (array_key_exists('job_title', $data)) $user->profile()->updateOrCreate(['user_id' => $user->id], ['job_title' => $data['job_title']]);
            $this->audit->record($request, $actor, 'identity.user.updated', User::class, $user->id, ['fields' => array_keys($data)]);
            return response()->json(['data' => ['id' => $user->id, 'display_name' => $user->display_name, 'primary_org_unit_id' => $user->primary_org_unit_id]]);
        });
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
