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

    public function test_department_scopes_filter_lists_details_updates_and_descendants(): void
    {
        $actor = $this->account();
        $root = $this->unit($actor, 'Engineering');
        $child = $this->unit($actor, 'Backend', $root);
        $other = $this->unit($actor, 'HR');
        $inside = $this->account($actor->organization_id);
        $below = $this->account($actor->organization_id);
        $outside = $this->account($actor->organization_id);
        $inside->update(['primary_org_unit_id' => $root]);
        $below->update(['primary_org_unit_id' => $child]);
        $outside->update(['primary_org_unit_id' => $other]);
        $role = $this->grant($actor, ['identity.users.read', 'identity.users.update']);
        $assignment = UserRoleAssignment::where('role_id', $role->id)->firstOrFail();
        $assignment->update(['scope_type' => 'org_unit', 'org_unit_id' => $root]);
        $headers = $this->headers($this->token($actor));
        $this->getJson('/api/v1/admin/users', $headers)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $inside->id);
        $this->getJson("/api/v1/admin/users/{$below->id}", $headers)->assertNotFound();
        $this->patchJson("/api/v1/admin/users/{$outside->id}/status", ['status' => 'suspended'], $headers)->assertNotFound();
        $assignment->update(['include_descendants' => true]);
        $this->getJson('/api/v1/admin/users', $headers)->assertOk()->assertJsonCount(2, 'data');
        $this->patchJson("/api/v1/admin/users/{$below->id}/status", ['status' => 'suspended'], $headers)->assertOk();
        $assignment->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/v1/admin/users', $headers)->assertForbidden();
    }

    public function test_actions_from_different_grants_do_not_share_scopes(): void
    {
        $actor = $this->account();
        $a = $this->unit($actor, 'A');
        $b = $this->unit($actor, 'B');
        $target = $this->account($actor->organization_id);
        $target->update(['primary_org_unit_id' => $b]);
        $write = $this->grant($actor, ['identity.users.update']);
        $read = $this->grant($actor, ['identity.users.read']);
        UserRoleAssignment::where('role_id', $write->id)->update(['scope_type' => 'org_unit', 'org_unit_id' => $a]);
        UserRoleAssignment::where('role_id', $read->id)->update(['scope_type' => 'org_unit', 'org_unit_id' => $b]);
        $headers = $this->headers($this->token($actor));
        $this->getJson("/api/v1/admin/users/{$target->id}", $headers)->assertOk();
        $this->patchJson("/api/v1/admin/users/{$target->id}/status", ['status' => 'suspended'], $headers)->assertNotFound();
    }

    public function test_self_scope_and_delegation_cannot_escalate_permissions_or_lifetime(): void
    {
        $actor = $this->account();
        $other = $this->account($actor->organization_id);
        $own = $this->grant($actor, ['identity.users.read']);
        UserRoleAssignment::where('role_id', $own->id)->update(['scope_type' => 'self']);
        $headers = $this->headers($this->token($actor));
        $this->getJson('/api/v1/admin/users', $headers)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $actor->id);
        $manager = $this->grant($actor, ['identity.roles.manage']);
        UserRoleAssignment::where('role_id', $manager->id)->update(['expires_at' => now()->addHour()]);
        $powerful = $this->grant($other, ['identity.users.update']);
        $this->postJson("/api/v1/admin/users/{$other->id}/role-assignments", ['role_id' => $powerful->id], $headers)->assertForbidden();
        $this->postJson("/api/v1/admin/users/{$other->id}/role-assignments", ['role_id' => $manager->id], $headers)->assertForbidden();
        $created = $this->postJson("/api/v1/admin/users/{$other->id}/role-assignments", ['role_id' => $manager->id, 'expires_at' => now()->addMinutes(30)->toISOString()], $headers)->assertCreated()->json('data.id');
        $this->deleteJson("/api/v1/admin/users/{$other->id}/role-assignments/{$created}", [], $headers)->assertNoContent();
    }

    public function test_organization_tree_cannot_become_cyclic(): void
    {
        $actor = $this->account();
        $root = $this->unit($actor, 'Root');
        $child = $this->unit($actor, 'Child', $root);
        $this->grant($actor, ['identity.org_units.manage']);
        $this->patchJson("/api/v1/admin/org-units/{$root}", ['parent_id' => $child, 'code' => $root, 'name' => 'Root', 'unit_type' => 'department', 'status' => 'active', 'sort_order' => 0], $this->headers($this->token($actor)))->assertUnprocessable();
        $this->assertNull(DB::table('org_units')->where('id', $root)->value('parent_id'));
    }

    private function unit(User $actor, string $name, ?string $parent = null): string
    {
        $id = (string) Str::uuid();
        DB::table('org_units')->insert(['id' => $id, 'organization_id' => $actor->organization_id, 'code' => $id, 'name' => $name, 'parent_id' => $parent, 'unit_type' => 'department', 'status' => 'active']);

        return $id;
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
    public function test_profile_pii_and_account_creation_reset_authorization(): void
    {
        $actor = $this->account();
        $unit = $this->unit($actor, 'Engineering');
        $this->seed(\Database\Seeders\RbacSeeder::class);
        $this->grant($actor, ['identity.users.create', 'identity.users.update', 'identity.profile.read_self', 'identity.profile.update_self']);
        DB::table('organization_email_domains')->insert(['id' => (string) Str::uuid(), 'organization_id' => $actor->organization_id, 'domain' => 'example.test']);
        $headers = $this->headers($this->token($actor));
        $id = $this->postJson('/api/v1/admin/users', ['email' => 'new@example.test', 'display_name' => 'New', 'primary_org_unit_id' => $unit, 'password' => self::PASSWORD], $headers)
            ->assertCreated()->assertJsonPath('data.must_change_password', true)->assertJsonMissing(['password' => self::PASSWORD])->json('data.id');
        $this->assertDatabaseHas('org_unit_memberships', ['user_id' => $id, 'org_unit_id' => $unit, 'is_primary' => true]);
        $this->patchJson('/api/v1/me/profile', ['phone' => '0900000000'], $headers)->assertOk()->assertJsonPath('data.phone', '0900000000');
        $target = User::findOrFail($id);
        $target->update(['must_change_password_at' => null]);
        $targetHeaders = $this->headers($this->token($target));
        $this->getJson('/api/v1/directory/users/'.$actor->id, $targetHeaders)->assertOk()->assertJsonMissingPath('data.phone')->assertJsonMissingPath('data.email');
        $this->postJson('/api/v1/admin/users/'.$actor->id.'/reset-password', ['password' => 'Another!Password2026'], $targetHeaders)->assertForbidden();
        $this->postJson('/api/v1/admin/users/'.$id.'/reset-password', ['password' => 'Another!Password2026'], $headers)->assertNoContent();
        $this->getJson('/api/v1/me', $targetHeaders)->assertUnauthorized();
        $this->assertTrue(Hash::check('Another!Password2026', $target->fresh()->password_hash));
        $this->assertNotNull($target->fresh()->must_change_password_at);
    }
}
