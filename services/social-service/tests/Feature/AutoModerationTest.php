<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutoModerationTest extends TestCase
{
    use RefreshDatabase;

    private const ORG = '10000000-0000-4000-8000-000000000001';
    private const USER = '30000000-0000-4000-8000-000000000001';
    private const MODERATOR = '30000000-0000-4000-8000-000000000002';
    private const CHANNEL = '60000000-0000-4000-8000-000000000001';
    private const KEY = 'social-test-private-gateway-key-123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gateway_key', self::KEY);
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));

        DB::table('social_channels')->insert([
            'id' => self::CHANNEL, 'organization_id' => self::ORG, 'code' => 'general',
            'name' => 'General', 'visibility' => 'organization', 'membership_policy' => 'open',
            'owner_user_id' => self::USER, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([self::USER, self::MODERATOR] as $user) {
            DB::table('channel_members')->insert([
                'id' => (string) Str::uuid(), 'channel_id' => self::CHANNEL, 'user_id' => $user,
                'membership_status' => 'active', 'member_role' => $user === self::USER ? 'owner' : 'member',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function headers(string $userId = self::USER, string $permissions = ''): array
    {
        return [
            'Accept' => 'application/json',
            'X-Social-Gateway-Key' => self::KEY,
            'X-Authenticated-User-Id' => $userId,
            'X-Authenticated-Organization-Id' => self::ORG,
            'X-Authenticated-Permissions' => $permissions,
        ];
    }

    public function test_post_with_leaked_api_key_is_automatically_hidden_and_flagged(): void
    {
        $payload = [
            'channel_id' => self::CHANNEL,
            'title' => 'Test Webhook',
            'body_markdown' => 'Anh em test webhook này nhé: https://api.novatech.vn/v1/auth?key=SK_LIVE_9823487100000000',
        ];

        $res = $this->postJson('/api/v1/posts', $payload, $this->headers())->assertCreated();
        $postId = $res->json('data.id');

        // Post should be auto-hidden and blocked
        $post = DB::table('posts')->where('id', $postId)->first();
        $this->assertEquals('hidden', $post->publication_status);
        $this->assertEquals('blocked', $post->moderation_status);

        // AI report should exist
        $report = DB::table('post_reports')->where('target_id', $postId)->where('source', 'ai')->first();
        $this->assertNotNull($report);
        $this->assertEquals('secret_exposure', $report->report_reason_code);
        $this->assertGreaterThanOrEqual(0.9, (float) $report->risk_score);

        // Moderation action log should be created
        $action = DB::table('moderation_actions')->where('target_id', $postId)->where('actor_type', 'ai')->first();
        $this->assertNotNull($action);
        $this->assertEquals('hide', $action->action_type);
    }

    public function test_post_with_toxic_content_is_flagged_for_review(): void
    {
        $payload = [
            'channel_id' => self::CHANNEL,
            'body_markdown' => 'Thằng chó này làm việc vcl thế',
        ];

        $res = $this->postJson('/api/v1/posts', $payload, $this->headers())->assertCreated();
        $postId = $res->json('data.id');

        $post = DB::table('posts')->where('id', $postId)->first();
        $this->assertEquals('flagged', $post->moderation_status);

        $report = DB::table('post_reports')->where('target_id', $postId)->where('source', 'ai')->first();
        $this->assertNotNull($report);
        $this->assertEquals('toxic_content', $report->report_reason_code);
    }

    public function test_admin_can_list_and_filter_posts_for_moderation(): void
    {
        // 1 normal post
        $this->postJson('/api/v1/posts', [
            'channel_id' => self::CHANNEL,
            'body_markdown' => 'Nội dung thông báo bình thường.',
        ], $this->headers())->assertCreated();

        // 1 toxic post
        $this->postJson('/api/v1/posts', [
            'channel_id' => self::CHANNEL,
            'body_markdown' => 'Nội dung chứa từ ngữ tục tĩu dmm vcl.',
        ], $this->headers())->assertCreated();

        $modHeaders = $this->headers(self::MODERATOR, 'social.moderation.manage');

        $res = $this->getJson('/api/v1/admin/posts?status=all', $modHeaders)->assertOk();
        $this->assertCount(2, $res->json('data'));
        $this->assertNotNull($res->json('summary'));
        $this->assertEquals(2, $res->json('summary.total_all'));
        $this->assertEquals(1, $res->json('summary.total_flagged'));

        // Filter queue
        $queueRes = $this->getJson('/api/v1/admin/posts?status=queue', $modHeaders)->assertOk();
        $this->assertCount(1, $queueRes->json('data'));
        $this->assertEquals('flagged', $queueRes->json('data.0.moderation_status'));
        $this->assertNotNull($queueRes->json('data.0.ai_alert'));
    }

    public function test_admin_can_scan_clean_post_and_detect_violations(): void
    {
        // Manually insert a post that wasn't scanned before
        $id = (string) Str::uuid();
        DB::table('posts')->insert([
            'id' => $id,
            'organization_id' => self::ORG,
            'channel_id' => self::CHANNEL,
            'author_user_id' => self::USER,
            'created_by_user_id' => self::USER,
            'post_type' => 'update',
            'title' => 'Secret leak',
            'body_markdown' => 'Check key: AKIA1234567890ABCDEF',
            'body_plaintext' => 'Check key: AKIA1234567890ABCDEF',
            'visibility' => 'organization',
            'publication_status' => 'published',
            'moderation_status' => 'clear',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modHeaders = $this->headers(self::MODERATOR, 'social.moderation.manage');

        // Run scan
        $scanRes = $this->postJson("/api/v1/admin/posts/{$id}/scan", [], $modHeaders)->assertOk();
        $this->assertTrue($scanRes->json('data.is_flagged'));
        $this->assertEquals('Lộ thông tin bảo mật', $scanRes->json('data.category'));

        // Post status updated
        $updated = DB::table('posts')->where('id', $id)->first();
        $this->assertEquals('hidden', $updated->publication_status);
        $this->assertEquals('blocked', $updated->moderation_status);
    }

    public function test_admin_can_approve_flagged_post(): void
    {
        $payload = [
            'channel_id' => self::CHANNEL,
            'body_markdown' => 'Nội dung bị nghi ngờ dmm',
        ];
        $res = $this->postJson('/api/v1/posts', $payload, $this->headers())->assertCreated();
        $postId = $res->json('data.id');

        $modHeaders = $this->headers(self::MODERATOR, 'social.moderation.manage');

        // Approve
        $approveRes = $this->postJson("/api/v1/admin/posts/{$postId}/moderation", [
            'action' => 'approve',
            'reason_code' => 'false_positive',
            'note' => 'Đã xem xét và xác nhận an toàn.',
        ], $modHeaders)->assertOk();

        $this->assertEquals('clear', $approveRes->json('data.moderation_status'));

        $post = DB::table('posts')->where('id', $postId)->first();
        $this->assertEquals('clear', $post->moderation_status);

        // Reports resolved
        $openReports = DB::table('post_reports')->where('target_id', $postId)->whereIn('status', ['open', 'under_review'])->count();
        $this->assertEquals(0, $openReports);
    }
}
