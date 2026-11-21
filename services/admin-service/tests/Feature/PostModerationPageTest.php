<?php

namespace Tests\Feature;

use App\Auth\RemoteIdentityUser;
use App\Filament\Pages\PostModeration;
use App\Services\IdentityGatewayClient;
use App\Services\SocialGatewayClient;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class PostModerationPageTest extends TestCase
{
    public function test_large_embedded_image_stays_out_of_livewire_snapshot_and_detail(): void
    {
        $this->authenticateUser();

        $identityGateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $identityGateway);
        $identityGateway->shouldReceive('get')->andReturn(['data' => []]);

        $socialGateway = Mockery::mock(SocialGatewayClient::class);
        $this->app->instance(SocialGatewayClient::class, $socialGateway);
        $socialGateway->shouldReceive('channels')->andReturn(['data' => []]);
        $image = '![Ảnh đính kèm](data:image/jpeg;base64,' . str_repeat('A', 1200000) . ')';
        $socialGateway->shouldReceive('adminPosts')->andReturn(['data' => [[
            'id' => 'post-with-image',
            'title' => 'Tiêu đề ngắn',
            'body_markdown' => $image,
            'body_plaintext' => $image,
            'author_user_id' => null,
            'publication_status' => 'published',
            'moderation_status' => 'clear',
            'post_type' => 'update',
            'created_at' => now()->toIso8601String(),
        ]], 'meta' => [], 'summary' => []]);

        Livewire::test(PostModeration::class)
            ->assertSee('Tiêu đề ngắn')
            ->assertDontSee('data:image/jpeg;base64')
            ->call('openDetail', 'post-with-image')
            ->assertSet('showDetailModal', true)
            ->assertSee('Ảnh đính kèm')
            ->assertDontSee('data:image/jpeg;base64');
    }

    private function authenticateUser(): void
    {
        $user = new RemoteIdentityUser([
            'id' => '30000000-0000-4000-8000-000000000001',
            'email' => 'admin@gmail.com',
            'display_name' => 'Nguyễn Quản Trị',
            'must_change_password' => false,
            'permissions' => ['social.moderation.manage', 'identity.roles.manage'],
        ]);

        $this->withSession(['admin.identity_token' => 'test-token'])->actingAs($user, 'admin');
    }

    public function test_post_moderation_page_renders_and_loads_posts(): void
    {
        $this->authenticateUser();

        $identityGateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $identityGateway);
        $identityGateway->shouldReceive('get')->with('users?per_page=100', 'test-token')
            ->andReturn(['data' => [
                ['id' => '30000000-0000-4000-8000-000000000001', 'display_name' => 'Nguyễn Quản Trị'],
            ]]);

        $socialGateway = Mockery::mock(SocialGatewayClient::class);
        $this->app->instance(SocialGatewayClient::class, $socialGateway);

        $socialGateway->shouldReceive('channels')->with('test-token')
            ->andReturn(['data' => [
                ['id' => '60000000-0000-4000-8000-000000000001', 'code' => 'general', 'name' => 'Thông báo chung'],
            ]]);

        $mockPosts = [
            [
                'id' => 'post-1',
                'channel_id' => '60000000-0000-4000-8000-000000000001',
                'channel_code' => 'general',
                'author_user_id' => '30000000-0000-4000-8000-000000000001',
                'post_type' => 'update',
                'title' => 'Test Post',
                'body_markdown' => 'Nội dung test kiểm duyệt',
                'body_plaintext' => 'Nội dung test kiểm duyệt',
                'publication_status' => 'published',
                'moderation_status' => 'clear',
                'created_at' => now()->toIso8601String(),
                'reaction_count' => 5,
                'comment_count' => 2,
                'open_reports_count' => 0,
                'ai_alert' => null,
            ],
            [
                'id' => 'post-2',
                'channel_id' => '60000000-0000-4000-8000-000000000001',
                'channel_code' => 'general',
                'author_user_id' => '30000000-0000-4000-8000-000000000001',
                'post_type' => 'update',
                'title' => 'Leaked Token Post',
                'body_markdown' => 'Webhook key=SK_LIVE_98234871...',
                'body_plaintext' => 'Webhook key=SK_LIVE_98234871...',
                'publication_status' => 'hidden',
                'moderation_status' => 'blocked',
                'created_at' => now()->toIso8601String(),
                'reaction_count' => 1,
                'comment_count' => 0,
                'open_reports_count' => 1,
                'ai_alert' => 'AI Cảnh báo: Lộ API Key (96%)',
            ],
        ];

        $socialGateway->shouldReceive('adminPosts')
            ->andReturn([
                'data' => $mockPosts,
                'meta' => ['total' => 2, 'page' => 1, 'per_page' => 15, 'last_page' => 1],
                'summary' => [
                    'total_all' => 2,
                    'total_news' => 0,
                    'total_posts' => 2,
                    'total_queue' => 1,
                    'total_reported' => 0,
                    'total_flagged' => 1,
                    'total_hidden' => 1,
                ],
            ]);

        Livewire::test(PostModeration::class)
            ->assertSuccessful()
            ->assertSee('Bài viết mạng xã hội')
            ->assertSee('Test Post')
            ->assertSee('Leaked Token Post')
            ->assertSee('AI Cảnh báo: Lộ API Key (96%)');
    }

    public function test_admin_can_perform_quick_moderation_actions(): void
    {
        $this->authenticateUser();

        $identityGateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $identityGateway);
        $identityGateway->shouldReceive('get')->andReturn(['data' => []]);

        $socialGateway = Mockery::mock(SocialGatewayClient::class);
        $this->app->instance(SocialGatewayClient::class, $socialGateway);
        $socialGateway->shouldReceive('channels')->andReturn(['data' => []]);
        $socialGateway->shouldReceive('adminPosts')->andReturn(['data' => [], 'meta' => [], 'summary' => []]);

        // Mock quick hide
        $socialGateway->shouldReceive('moderate')
            ->once()
            ->with('test-token', 'post-1', 'hide', 'sensitive_content', 'Tạm ẩn nhanh từ danh sách kiểm duyệt.')
            ->andReturn(['data' => ['id' => 'post-1', 'publication_status' => 'hidden']]);

        // Mock quick restore
        $socialGateway->shouldReceive('moderate')
            ->once()
            ->with('test-token', 'post-1', 'restore', 'approved_by_admin', 'Khôi phục bài viết sau khi kiểm tra.')
            ->andReturn(['data' => ['id' => 'post-1', 'publication_status' => 'published']]);

        // Mock quick approve
        $socialGateway->shouldReceive('moderate')
            ->once()
            ->with('test-token', 'post-1', 'approve', 'false_positive', 'Admin đã kiểm tra và phê duyệt an toàn.')
            ->andReturn(['data' => ['id' => 'post-1', 'moderation_status' => 'clear']]);

        Livewire::test(PostModeration::class)
            ->call('quickHide', 'post-1')
            ->assertNotified('Đã tạm ẩn bài viết.')
            ->call('quickRestore', 'post-1')
            ->assertNotified('Đã khôi phục bài viết về trạng thái đã đăng.')
            ->call('quickApprove', 'post-1')
            ->assertNotified('Đã đánh dấu an toàn và giải quyết các cảnh báo.');
    }

    public function test_admin_can_run_ai_scan(): void
    {
        $this->authenticateUser();

        $identityGateway = Mockery::mock(IdentityGatewayClient::class);
        $this->app->instance(IdentityGatewayClient::class, $identityGateway);
        $identityGateway->shouldReceive('get')->andReturn(['data' => []]);

        $socialGateway = Mockery::mock(SocialGatewayClient::class);
        $this->app->instance(SocialGatewayClient::class, $socialGateway);
        $socialGateway->shouldReceive('channels')->andReturn(['data' => []]);
        $socialGateway->shouldReceive('adminPosts')->andReturn(['data' => [], 'meta' => [], 'summary' => []]);

        // Mock scan post
        $socialGateway->shouldReceive('scanPost')
            ->once()
            ->with('test-token', 'post-1')
            ->andReturn(['data' => [
                'is_flagged' => true,
                'risk_score' => 0.96,
                'category' => 'Lộ thông tin bảo mật',
            ]]);

        // Mock scan all
        $socialGateway->shouldReceive('scanAll')
            ->once()
            ->with('test-token')
            ->andReturn(['data' => [
                'scanned_count' => 10,
                'flagged_count' => 2,
            ]]);

        Livewire::test(PostModeration::class)
            ->call('scanPostWithAi', 'post-1')
            ->assertNotified('AI Guard phát hiện rủi ro: Lộ thông tin bảo mật (Điểm rủi ro: 96%)')
            ->call('scanAllWithAi')
            ->assertNotified('AI Guard hoàn tất: Đã quét 10 bài viết, phát hiện 2 bài viết có rủi ro.');
    }
}
