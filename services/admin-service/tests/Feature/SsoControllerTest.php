<?php

namespace Tests\Feature;

use App\Services\IdentityGatewayClient;
use Mockery;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    public function test_sso_redirects_to_login_when_no_token_provided(): void
    {
        $response = $this->get('/admin/sso');

        $response->assertRedirect('/admin/login');
    }

    public function test_sso_redirects_to_login_with_error_when_token_is_invalid(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $gateway->shouldReceive('session')->with('invalid-token')->once()->andReturnNull();
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $response = $this->get('/admin/sso?token=invalid-token');

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('data.email');
    }

    public function test_sso_blocks_user_if_password_change_is_required(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $gateway->shouldReceive('session')->with('must-change-token')->once()->andReturn([
            'id' => 'user-1',
            'email' => 'user@example.test',
            'display_name' => 'User',
            'must_change_password' => true,
            'permissions' => ['identity.users.read'],
        ]);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $response = $this->get('/admin/sso?token=must-change-token');

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('data.email');
    }

    public function test_sso_blocks_user_without_admin_permissions(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $gateway->shouldReceive('session')->with('regular-user-token')->once()->andReturn([
            'id' => 'user-2',
            'email' => 'regular@example.test',
            'display_name' => 'Regular User',
            'must_change_password' => false,
            'permissions' => ['social.posts.read'],
        ]);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $response = $this->get('/admin/sso?token=regular-user-token');

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('data.email');
    }

    public function test_sso_authenticates_authorized_user_and_redirects_to_admin(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $gateway->shouldReceive('session')->with('valid-admin-token')->andReturn([
            'id' => 'admin-user-id',
            'email' => 'hr_manager@gmail.com',
            'display_name' => 'Quản lý Nhân sự',
            'must_change_password' => false,
            'permissions' => ['identity.users.read', 'identity.users.update'],
        ]);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $response = $this->get('/admin/sso?token=valid-admin-token');

        $response->assertRedirect('http://127.0.0.1:8001/admin');
        $this->assertAuthenticated('admin');
        $this->assertEquals('valid-admin-token', session('admin.identity_token'));
        $this->assertEquals('admin-user-id', session('admin.identity_user')['id']);
    }

    public function test_sso_works_via_root_sso_alias(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $gateway->shouldReceive('session')->with('valid-admin-token-2')->andReturn([
            'id' => 'admin-user-id-2',
            'email' => 'admin@gmail.com',
            'display_name' => 'Nguyễn Quản Trị',
            'must_change_password' => false,
            'permissions' => ['identity.roles.manage'],
        ]);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $response = $this->get('/sso?token=valid-admin-token-2');

        $response->assertRedirect('http://127.0.0.1:8001/admin');
        $this->assertAuthenticated('admin');
    }
}
