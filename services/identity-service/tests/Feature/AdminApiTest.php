<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Initial!Password2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['identity.gateway_key' => str_repeat('t', 64)]);
        $this->withHeader('X-Identity-Service-Key', str_repeat('t', 64));
    }

    public function test_admin_endpoint_requires_its_specific_permission(): void
    {
        $actor = $this->account();

        $this->getJson('/api/v1/admin/dashboard/identity', $this->headers($this->token($actor)))
            ->assertForbidden();
    }

    public function test_user_status_update_is_organization_scoped_and_audited(): void
    {
        $actor = $this->account();
        $target = $this->account($actor->organization_id);
        $outsideOrganization = $this->account();
        $this->grant($actor, ['identity.users.read', 'identity.users.update']);
        $token = $this->token($actor);

        $this->patchJson("/api/v1/admin/users/{$target->id}/status", ['status' => 'suspended'], $this->headers($token))
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $actor->id, 'entity_id' => $target->id, 'event_type' => 'identity.user.status_updated']);
        $this->assertSame(1, AuditLog::query()->count());

        $this->patchJson("/api/v1/admin/users/{$outsideOrganization->id}/status", ['status' => 'suspended'], $this->headers($token))
            ->assertNotFound();
        $this->assertSame('active', $outsideOrganization->fresh()->status);
    }

    public function test_actor_cannot_change_permissions_of_a_role_assigned_to_themself(): void
    {
        $actor = $this->account();
        $role = $this->grant($actor, ['identity.roles.manage']);

        $this->patchJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => []], $this->headers($this->token($actor)))
            ->assertUnprocessable();
    }

    private function account(?string $organizationId = null): User
    {
        $organizationId ??= (string) Str::uuid();
        if (! DB::table('organizations')->where('id', $organizationId)->exists()) {
            DB::table('organizations')->insert(['id' => $organizationId, 'code' => $organizationId, 'name' => 'Test']);
        }

        return User::create([
            'organization_id' => $organizationId,
            'email' => Str::uuid().'@example.test',
            'display_name' => 'Test user',
            'password_hash' => Hash::make(self::PASSWORD),
            'status' => 'active',
        ]);
    }

    /** @param array<int, string> $permissionCodes */
    private function grant(User $user, array $permissionCodes): Role
    {
        $role = Role::create(['code' => 'test-'.Str::uuid(), 'name' => 'Test role', 'is_system' => false]);
        $permissionIds = collect($permissionCodes)->map(function (string $code): string {
            [$resource, $action] = explode('.', $code, 2);

            return Permission::firstOrCreate(['code' => $code], ['name' => $code, 'resource' => $resource, 'action' => $action])->id;
        });
        $role->permissions()->sync($permissionIds);
        UserRoleAssignment::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        return $role;
    }

    private function token(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertOk()
            ->json('access_token');
    }

    /** @return array<string, string> */
    private function headers(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
