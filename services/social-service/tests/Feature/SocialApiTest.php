<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class SocialApiTest extends TestCase
{
    use RefreshDatabase;

    private const ORG = '10000000-0000-4000-8000-000000000001';

    private const USER = '30000000-0000-4000-8000-000000000001';

    private const OTHER = '30000000-0000-4000-8000-000000000002';

    private const CHANNEL = '60000000-0000-4000-8000-000000000001';

    private const KEY = 'social-test-private-gateway-key-123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gateway_key', self::KEY);
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        DB::table('social_channels')->insert(['id' => self::CHANNEL, 'organization_id' => self::ORG, 'code' => 'general', 'name' => 'General', 'visibility' => 'organization', 'membership_policy' => 'open', 'owner_user_id' => self::USER, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([self::USER, self::OTHER] as $user) {
            DB::table('channel_members')->insert(['id' => (string) Str::uuid(), 'channel_id' => self::CHANNEL, 'user_id' => $user, 'membership_status' => 'active', 'member_role' => $user === self::USER ? 'owner' : 'member', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function headers(string $user = self::USER, string $org = self::ORG, string $permissions = ''): array
    {
        return ['X-Social-Gateway-Key' => self::KEY, 'X-Authenticated-User-Id' => $user, 'X-Authenticated-Organization-Id' => $org, 'X-Authenticated-Permissions' => $permissions];
    }

    private function createPost(array $extra = []): string
    {
        return $this->postJson('/api/v1/posts', [...['channel_id' => self::CHANNEL, 'body_markdown' => 'Xin chào'], ...$extra], $this->headers())->assertCreated()->json('data.id');
    }

    public function test_private_gateway_authentication_and_fail_closed_configuration(): void
    {
        $this->getJson('/api/v1/posts')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
        $this->getJson('/api/v1/posts', [...$this->headers(), 'X-Authenticated-User-Id' => 'invalid'])->assertUnauthorized();
        config()->set('services.gateway_key', '');
        $this->getJson('/api/v1/posts', $this->headers())->assertStatus(503);
    }

    public function test_post_lifecycle_tenant_isolation_and_owner_guard(): void
    {
        $id = $this->createPost(['publication_status' => 'draft']);
        $this->getJson('/api/v1/posts', $this->headers())->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertNotFound();
        $this->patchJson('/api/v1/posts/'.$id, ['publication_status' => 'published'], $this->headers())->assertOk()->assertJsonPath('data.publication_status', 'published');
        $date = $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertOk()->assertJsonPath('data.author_user_id', self::USER)->json('data.published_at');
        $this->assertStringEndsWith('Z', $date);
        $this->patchJson('/api/v1/posts/'.$id, ['body_markdown' => 'x'], $this->headers(self::OTHER))->assertForbidden();
        $this->getJson('/api/v1/posts/'.$id, $this->headers(self::OTHER, '10000000-0000-4000-8000-000000000002'))->assertNotFound();
        $this->getJson('/api/v1/posts', $this->headers(self::OTHER, '10000000-0000-4000-8000-000000000002'))->assertJsonCount(0, 'data');
        $this->patchJson('/api/v1/posts/'.$id, ['body_markdown' => 'Đã sửa'], $this->headers())->assertOk()->assertJsonPath('data.published_at', $date);
        $this->deleteJson('/api/v1/posts/'.$id, [], $this->headers())->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertNotFound();
        $this->assertDatabaseHas('outbox_events', ['aggregate_id' => $id, 'event_type' => 'social.post.deleted']);
    }

    public function test_channel_scope_join_leave_and_membership_write_guard(): void
    {
        $id = $this->createPost();
        $this->deleteJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id, $this->headers(self::OTHER))->assertOk();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x'], $this->headers(self::OTHER))->assertForbidden();
        $this->putJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertOk();
        $this->putJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertOk();
        $this->assertSame(2, DB::table('channel_members')->count());
        $this->deleteJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers())->assertStatus(409);
        DB::table('social_channels')->where('id', self::CHANNEL)->update(['visibility' => 'members']);
        $this->deleteJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id, $this->headers(self::OTHER))->assertNotFound();
        $this->getJson('/api/v1/posts', $this->headers(self::OTHER))->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/channels', $this->headers(self::OTHER))->assertJsonCount(0, 'data');
        DB::table('social_channels')->where('id', self::CHANNEL)->update(['membership_policy' => 'managed']);
        $this->putJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertForbidden();
    }

    public function test_create_channel_permission_unique_code_and_owner_membership(): void
    {
        $data = ['code' => 'project', 'name' => 'Dự án', 'visibility' => 'members', 'membership_policy' => 'managed'];
        $this->postJson('/api/v1/channels', $data, $this->headers())->assertForbidden();
        $h = $this->headers(permissions: 'social.channels.manage');
        $id = $this->postJson('/api/v1/channels', $data, $h)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('channel_members', ['channel_id' => $id, 'user_id' => self::USER, 'member_role' => 'owner']);
        $this->postJson('/api/v1/channels', $data, $h)->assertStatus(409);
    }

    public function test_feed_cursor_ties_filters_and_invalid_cursor(): void
    {
        $ids = [$this->createPost(), $this->createPost(), $this->createPost()];
        DB::table('posts')->update(['published_at' => '2026-10-13 00:00:00']);
        $first = $this->getJson('/api/v1/posts?limit=2', $this->headers())->assertOk()->assertJsonCount(2, 'data');
        $cursor = $first->json('meta.next_cursor');
        $next = $this->getJson('/api/v1/posts?limit=2&cursor='.urlencode($cursor), $this->headers())->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.has_more', false);
        $all = [...array_column($first->json('data'), 'id'), ...array_column($next->json('data'), 'id')];
        $this->assertCount(3, array_unique($all));
        $this->assertEqualsCanonicalizing($ids, $all);
        $this->getJson('/api/v1/posts?cursor=invalid', $this->headers())->assertUnprocessable();
        $this->getJson('/api/v1/posts?limit=101', $this->headers())->assertUnprocessable();
        $this->getJson('/api/v1/posts?saved=1&cursor='.urlencode($cursor), $this->headers())->assertUnprocessable();
        $this->getJson('/api/v1/posts?cursor='.urlencode($cursor), $this->headers(self::OTHER))->assertUnprocessable();
    }

    public function test_reaction_and_private_save_are_idempotent_and_revalidated(): void
    {
        $id = $this->createPost();
        foreach (['like', 'like', 'love'] as $reaction) {
            $this->putJson('/api/v1/posts/'.$id.'/reaction', ['reaction_type' => $reaction], $this->headers())->assertOk();
        }
        $this->assertSame(1, DB::table('post_reactions')->count());
        $this->putJson('/api/v1/posts/'.$id.'/reaction', ['reaction_type' => 'wow'], $this->headers())->assertUnprocessable();
        for ($i = 0; $i < 2; $i++) {
            $this->putJson('/api/v1/posts/'.$id.'/save', [], $this->headers())->assertOk();
        }
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertJsonPath('data.my_reaction', 'love')->assertJsonPath('data.is_saved', true)->assertJsonPath('data.reaction_count', 1);
        $this->getJson('/api/v1/posts?saved=1', $this->headers())->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/posts?saved=1', $this->headers(self::OTHER))->assertJsonCount(0, 'data');
        $this->deleteJson('/api/v1/posts/'.$id.'/reaction', [], $this->headers())->assertNoContent();
        $this->deleteJson('/api/v1/posts/'.$id.'/reaction', [], $this->headers())->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertJsonPath('data.reaction_count', 0);
        DB::table('social_channels')->where('id', self::CHANNEL)->update(['status' => 'archived']);
        $this->getJson('/api/v1/posts?saved=1', $this->headers())->assertJsonCount(0, 'data');
        $this->putJson('/api/v1/posts/'.$id.'/reaction', ['reaction_type' => 'like'], $this->headers())->assertNotFound();
    }

    public function test_comments_reply_depth_parent_post_and_owner_permissions(): void
    {
        $id = $this->createPost();
        $comment = $this->postJson('/api/v1/posts/'.$id.'/comments', ['body_markdown' => 'Một'], $this->headers())->assertCreated()->json('data.id');
        $reply = $this->postJson('/api/v1/posts/'.$id.'/comments', ['body_markdown' => 'Hai', 'parent_comment_id' => $comment], $this->headers(self::OTHER))->assertCreated()->json('data.id');
        $this->postJson('/api/v1/posts/'.$id.'/comments', ['body_markdown' => 'Ba', 'parent_comment_id' => $reply], $this->headers())->assertUnprocessable();
        $otherPost = $this->createPost();
        $this->postJson('/api/v1/posts/'.$otherPost.'/comments', ['body_markdown' => 'x', 'parent_comment_id' => $comment], $this->headers())->assertUnprocessable();
        $this->patchJson('/api/v1/comments/'.$comment, ['body_markdown' => 'Sửa'], $this->headers(self::OTHER))->assertForbidden();
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertJsonPath('data.comment_count', 2);
        $this->deleteJson('/api/v1/comments/'.$comment, [], $this->headers())->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id.'/comments', $this->headers())->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertJsonPath('data.comment_count', 0);
        $this->patchJson('/api/v1/comments/'.$reply, ['body_markdown' => 'Sửa'], $this->headers(self::OTHER))->assertNotFound();
    }

    public function test_reports_moderation_audit_and_restore_do_not_leak_hidden_content(): void
    {
        $id = $this->createPost();
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/v1/posts/'.$id.'/reports', ['reason_code' => 'spam'], $this->headers(self::OTHER))->assertSuccessful();
        }
        $this->assertSame(1, DB::table('post_reports')->count());
        $response = $this->getJson('/api/v1/admin/reports', $this->headers())->assertForbidden();
        $this->assertInstanceOf(\stdClass::class, json_decode($response->getContent())->errors);
        $this->postJson('/api/v1/admin/posts/'.$id.'/moderation', ['action' => 'hide', 'reason_code' => 'spam'], $this->headers())->assertForbidden();
        $h = $this->headers(permissions: 'social.moderation.manage');
        $this->getJson('/api/v1/admin/reports', $h)->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/admin/posts/'.$id.'/moderation', ['action' => 'lock_comments', 'reason_code' => 'review'], $h)->assertOk();
        $this->postJson('/api/v1/posts/'.$id.'/comments', ['body_markdown' => 'x'], $this->headers())->assertStatus(409);
        $this->postJson('/api/v1/admin/posts/'.$id.'/moderation', ['action' => 'hide', 'reason_code' => 'spam'], $h)->assertOk();
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertNotFound();
        $this->patchJson('/api/v1/posts/'.$id, ['body_markdown' => 'Bypass'], $this->headers())->assertStatus(409);
        $this->getJson('/api/v1/posts', $this->headers())->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/admin/reports?status=resolved', $h)->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/admin/posts/'.$id.'/moderation', ['action' => 'restore', 'reason_code' => 'reviewed'], $h)->assertOk();
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertOk();
        $this->assertSame(3, DB::table('moderation_actions')->count());
        $draft = $this->createPost(['publication_status' => 'draft']);
        $this->postJson('/api/v1/admin/posts/'.$draft.'/moderation', ['action' => 'restore', 'reason_code' => 'x'], $h)->assertStatus(409);
    }

    public function test_idempotency_key_replays_creation_and_rejects_changed_payload(): void
    {
        $h = [...$this->headers(), 'Idempotency-Key' => 'mobile-offline-request-1'];
        $data = ['channel_id' => self::CHANNEL, 'body_markdown' => 'Hello'];
        $first = $this->postJson('/api/v1/posts', $data, $h)->assertCreated();
        $this->postJson('/api/v1/posts', $data, $h)->assertCreated()->assertHeader('Idempotency-Replayed', 'true')->assertExactJson($first->json());
        $this->postJson('/api/v1/posts', [...$data, 'body_markdown' => 'Changed'], $h)->assertStatus(409);
        $this->assertSame(1, DB::table('posts')->count());
        $this->assertSame(1, DB::table('outbox_events')->count());
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL], [...$this->headers(), 'Idempotency-Key' => 'retry-error'])->assertUnprocessable();
        $this->postJson('/api/v1/posts', $data, [...$this->headers(), 'Idempotency-Key' => 'retry-error'])->assertCreated();
    }

    public function test_validation_special_publish_permissions_and_untrusted_identity(): void
    {
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => '  '], $this->headers())->assertUnprocessable();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x', 'author_user_id' => self::OTHER], $this->headers())->assertUnprocessable();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x', 'post_type' => 'announcement', 'title' => 'News'], $this->headers())->assertForbidden();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x', 'post_type' => 'announcement', 'title' => 'News'], $this->headers(permissions: 'social.announcement.publish'))->assertCreated();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x', 'post_type' => 'news'], $this->headers(permissions: 'social.news.publish'))->assertUnprocessable();
        $this->postJson('/api/v1/posts', ['channel_id' => self::CHANNEL, 'body_markdown' => 'x', 'post_type' => 'poll'], $this->headers())->assertUnprocessable();
    }

    public function test_idempotency_replay_rechecks_membership_and_current_permissions(): void
    {
        $headers = [...$this->headers(self::OTHER), 'Idempotency-Key' => 'revoked-membership'];
        $data = ['channel_id' => self::CHANNEL, 'body_markdown' => 'Retry safely'];
        $this->postJson('/api/v1/posts', $data, $headers)->assertCreated();
        $this->deleteJson('/api/v1/channels/'.self::CHANNEL.'/membership', [], $this->headers(self::OTHER))->assertNoContent();
        $this->postJson('/api/v1/posts', $data, $headers)->assertForbidden();
        $channelData = ['code' => 'secure', 'name' => 'Secure', 'visibility' => 'members', 'membership_policy' => 'managed'];
        $this->postJson('/api/v1/channels', $channelData, [...$this->headers(permissions: 'social.channels.manage'), 'Idempotency-Key' => 'revoke-permission'])->assertCreated();
        $this->postJson('/api/v1/channels', $channelData, [...$this->headers(), 'Idempotency-Key' => 'revoke-permission'])->assertForbidden();
    }

    public function test_unsupported_restricted_visibility_is_never_readable(): void
    {
        $id = $this->createPost();
        DB::table('posts')->where('id', $id)->update(['visibility' => 'restricted']);
        $this->getJson('/api/v1/posts', $this->headers())->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/posts/'.$id, $this->headers())->assertNotFound();
    }

    public function test_rate_limit_is_scoped_to_authenticated_actor(): void
    {
        // Laravel prefixes named-limit keys with a hash. Set the configured limit to 1 for this test.
        RateLimiter::for('social', fn ($request) => Limit::perMinute(1)->by($request->attributes->get('social_actor')['user_id']));
        $this->getJson('/api/v1/posts', $this->headers())->assertOk();
        $this->getJson('/api/v1/posts', $this->headers())->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('code', 'RATE_LIMITED');
        $this->getJson('/api/v1/posts', $this->headers(self::OTHER))->assertOk();
    }
}
