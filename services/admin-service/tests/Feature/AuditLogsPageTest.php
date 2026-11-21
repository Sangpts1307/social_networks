<?php

namespace Tests\Feature;

use App\Auth\RemoteIdentityUser;
use App\Filament\Pages\AuditLogs;
use App\Services\IdentityGatewayClient;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AuditLogsPageTest extends TestCase
{
    public function test_admin_can_view_and_filter_audit_logs(): void
    {
        $gateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $gateway);

        $gateway->shouldReceive('get')
            ->andReturn([
                'data' => [
                    [
                        'id' => 'log-1',
                        'actor_name' => 'Võ Thành Luân (CTO)',
                        'actor_email' => 'luan.vt@openhello.vn',
                        'event_type' => 'system.ai_config.updated',
                        'module' => 'ai',
                        'action' => 'UPDATE_CONFIG',
                        'ip_address' => '118.69.182.45',
                        'user_agent' => 'macOS Chrome',
                        'risk_level' => 'medium',
                        'status' => 'success',
                        'payload' => ['description' => 'Đổi API Key Gemini Embedding'],
                        'created_at' => '2026-11-16T17:00:00Z',
                    ],
                    [
                        'id' => 'log-2',
                        'actor_name' => 'System Guard (AI)',
                        'actor_email' => 'system.ai@internal.network',
                        'event_type' => 'social.post.auto_blocked',
                        'module' => 'social',
                        'action' => 'AUTO_BLOCK_POST',
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Worker Daemon',
                        'risk_level' => 'high',
                        'status' => 'blocked',
                        'payload' => ['description' => 'Phát hiện chuỗi SK_LIVE key'],
                        'created_at' => '2026-11-16T16:00:00Z',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 2,
                ],
                'summary' => [
                    'total' => 2,
                    'high_risk' => 1,
                    'medium_risk' => 1,
                    'today' => 2,
                    'actors_count' => 2,
                ],
            ]);

        $this->actingAsUser(['identity.audit.read']);

        Livewire::test(AuditLogs::class)
            ->assertSee('Nhật ký Kiểm toán')
            ->assertSee('Võ Thành Luân (CTO)')
            ->assertSee('System Guard (AI)')
            ->assertSee('UPDATE_CONFIG')
            ->assertSee('AUTO_BLOCK_POST')
            ->assertSee('High Risk')
            ->assertSee('Medium')
            ->set('moduleFilter', 'ai')
            ->assertSet('pageNumber', 1)
            ->call('openDetail', 'log-1')
            ->assertSet('showDetailModal', true)
            ->assertSee('Đổi API Key Gemini Embedding')
            ->call('closeDetail')
            ->assertSet('showDetailModal', false);
    }

    public function test_user_without_audit_permission_cannot_access(): void
    {
        $this->actingAsUser(['identity.users.read']);

        $this->assertFalse(AuditLogs::canAccess());
    }

    private function actingAsUser(array $permissions): void
    {
        $user = new RemoteIdentityUser([
            'id' => 'remote-admin-id',
            'display_name' => 'Quản trị viên',
            'email' => 'admin@gmail.com',
            'organization_id' => 'org-1',
            'must_change_password' => false,
            'permissions' => $permissions,
        ]);

        $this->withSession(['admin.identity_token' => 'test-token'])->actingAs($user, 'admin');
    }
}
