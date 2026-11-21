<?php

namespace Tests\Feature;

use App\Auth\RemoteIdentityUser;
use App\Filament\Pages\Roles;
use App\Services\IdentityGatewayClient;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RolesPageTest extends TestCase
{
    public function test_admin_can_create_edit_and_delete_custom_role(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $roleId = '8b8c9d10-1234-4a5b-8c9d-0123456789ab';

        $gateway->shouldReceive('get')
            ->with('roles', 'test-token')
            ->andReturn(
                ['data' => [
                    ['id' => 'sys-1', 'code' => 'super_admin', 'name' => 'Quản trị hệ thống', 'description' => null, 'is_system' => true, 'permissions' => []],
                ]],
                ['data' => [
                    ['id' => 'sys-1', 'code' => 'super_admin', 'name' => 'Quản trị hệ thống', 'description' => null, 'is_system' => true, 'permissions' => []],
                    ['id' => $roleId, 'code' => 'truong_phong_tai_chinh', 'name' => 'Trưởng phòng Tài chính', 'description' => 'Phê duyệt ngân sách', 'is_system' => false, 'permissions' => []],
                ]],
                ['data' => [
                    ['id' => 'sys-1', 'code' => 'super_admin', 'name' => 'Quản trị hệ thống', 'description' => null, 'is_system' => true, 'permissions' => []],
                    ['id' => $roleId, 'code' => 'truong_phong_tai_chinh', 'name' => 'Giám đốc Tài chính', 'description' => 'Điều hành tài chính', 'is_system' => false, 'permissions' => []],
                ]],
                ['data' => [
                    ['id' => 'sys-1', 'code' => 'super_admin', 'name' => 'Quản trị hệ thống', 'description' => null, 'is_system' => true, 'permissions' => []],
                ]],
            );

        $gateway->shouldReceive('get')
            ->with('permissions', 'test-token')
            ->andReturn(['data' => [
                ['id' => 'perm-1', 'code' => 'identity.users.read', 'name' => 'Xem người dùng'],
            ]]);

        $gateway->shouldReceive('send')
            ->once()
            ->with('POST', 'roles', 'test-token', [
                'name' => 'Trưởng phòng Tài chính',
                'code' => 'truong_phong_tai_chinh',
                'description' => 'Phê duyệt ngân sách',
            ])
            ->andReturn(['data' => ['id' => $roleId]]);

        $gateway->shouldReceive('send')
            ->once()
            ->with('PATCH', "roles/{$roleId}", 'test-token', [
                'name' => 'Giám đốc Tài chính',
                'code' => 'truong_phong_tai_chinh',
                'description' => 'Điều hành tài chính',
            ])
            ->andReturn(['data' => ['id' => $roleId]]);

        $gateway->shouldReceive('send')
            ->once()
            ->with('DELETE', "roles/{$roleId}", 'test-token')
            ->andReturn([]);

        $user = new RemoteIdentityUser([
            'id' => 'remote-admin-id',
            'email' => 'admin@example.test',
            'display_name' => 'Admin',
            'must_change_password' => false,
            'permissions' => ['identity.roles.read', 'identity.roles.manage'],
        ]);

        $this->withSession(['admin.identity_token' => 'test-token'])->actingAs($user, 'admin');

        Livewire::test(Roles::class)
            ->assertDontSee('super_admin')
            ->call('openCreateRole')
            ->assertSet('showRoleForm', true)
            ->set('roleName', 'Trưởng phòng Tài chính')
            ->assertSet('roleCode', 'truong_phong_tai_chinh')
            ->set('roleDescription', 'Phê duyệt ngân sách')
            ->call('saveRole')
            ->assertSet('showRoleForm', false)
            ->assertSet('selectedRoleId', $roleId)
            ->assertSee('Trưởng phòng Tài chính')
            ->call('openEditRole', $roleId)
            ->assertSet('editingRoleId', $roleId)
            ->set('roleName', 'Giám đốc Tài chính')
            ->set('roleDescription', 'Điều hành tài chính')
            ->call('saveRole')
            ->assertSee('Giám đốc Tài chính')
            ->call('deleteRole', $roleId)
            ->assertSet('selectedRoleId', null);
    }
}
