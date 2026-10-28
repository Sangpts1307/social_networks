<?php
namespace Tests\Feature;

use App\Models\ConversationMember;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    private const ORG = '10000000-0000-4000-8000-000000000001';
    private const OWNER = '30000000-0000-4000-8000-000000000001';
    private const MEMBER = '30000000-0000-4000-8000-000000000002';
    private const OTHER = '30000000-0000-4000-8000-000000000003';
    private ?string $databaseName = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('CHAT_TEST_MONGODB_URI');
        if (!$uri) $this->markTestSkipped('Set CHAT_TEST_MONGODB_URI to a disposable MongoDB replica set.');
        $this->databaseName = 'chat_api_test_'.str_replace('-', '', (string) Str::uuid());
        config(['services.gateway_key' => str_repeat('c', 40), 'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->databaseName]);
        DB::purge('mongodb');
        $this->artisan('chat:ensure-indexes')->assertSuccessful();
        $this->mock(\App\Services\IdentityDirectory::class)->shouldReceive('requireUsers')->andReturnNull();
    }

    protected function tearDown(): void
    {
        if ($this->databaseName && str_starts_with($this->databaseName, 'chat_api_test_')) DB::connection('mongodb')->getMongoDB()->drop();
        parent::tearDown();
    }

    private function headers(string $user = self::OWNER, string $org = self::ORG): array
    {
        return ['X-Chat-Gateway-Key' => str_repeat('c', 40), 'X-Authenticated-User-Id' => $user, 'X-Authenticated-Organization-Id' => $org];
    }

    public function test_direct_dedup_send_retry_read_and_tenant_isolation(): void
    {
        $this->getJson('/api/v1/conversations')->assertUnauthorized();
        $id = $this->postJson('/api/v1/conversations', ['type' => 'direct', 'member_ids' => [self::MEMBER]], $this->headers())->assertCreated()->json('data.id');
        $this->assertNotEmpty($id);
        $this->postJson('/api/v1/conversations', ['type' => 'direct', 'member_ids' => [self::OWNER]], $this->headers(self::MEMBER))->assertOk()->assertJsonPath('data.id', $id);
        $payload = ['client_message_id' => (string) Str::uuid(), 'body_markdown' => 'Hello'];
        $this->postJson("/api/v1/conversations/$id/messages", $payload, $this->headers())->assertCreated();
        $this->postJson("/api/v1/conversations/$id/messages", $payload, $this->headers())->assertOk();
        $this->postJson("/api/v1/conversations/$id/messages", [...$payload, 'body_markdown' => 'Changed'], $this->headers())->assertStatus(409);
        $this->assertSame(1, Message::count());
        $this->assertSame(1, ConversationMember::where('conversationId', $id)->where('userId', self::MEMBER)->value('unreadCount'));
        $this->getJson("/api/v1/conversations/$id/messages", $this->headers(self::OTHER))->assertNotFound();
        $this->getJson("/api/v1/conversations/$id/messages", $this->headers(self::MEMBER, '10000000-0000-4000-8000-000000000002'))->assertNotFound();
        $this->putJson("/api/v1/conversations/$id/read", [], $this->headers(self::MEMBER))->assertNoContent();
        $this->assertSame(0, ConversationMember::where('conversationId', $id)->where('userId', self::MEMBER)->value('unreadCount'));
        $this->getJson("/api/v1/conversations/$id/messages", $this->headers(self::MEMBER))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_group_owner_transfer_removal_and_message_access(): void
    {
        $id = $this->postJson('/api/v1/conversations', ['type' => 'group', 'title' => 'Team', 'member_ids' => [self::MEMBER]], $this->headers())->assertCreated()->json('data.id');
        $this->postJson("/api/v1/conversations/$id/members", ['user_id' => self::OTHER], $this->headers(self::MEMBER))->assertForbidden();
        $this->postJson("/api/v1/conversations/$id/members", ['user_id' => self::OTHER], $this->headers())->assertNoContent();
        $this->putJson("/api/v1/conversations/$id/owner", ['user_id' => self::MEMBER], $this->headers())->assertNoContent();
        $this->patchJson("/api/v1/conversations/$id", ['title' => 'Changed'], $this->headers())->assertForbidden();
        $this->deleteJson("/api/v1/conversations/$id/members/".self::OTHER, [], $this->headers(self::MEMBER))->assertNoContent();
        $this->getJson("/api/v1/conversations/$id/messages", $this->headers(self::OTHER))->assertNotFound();
        $this->deleteJson("/api/v1/conversations/$id/members/".self::MEMBER, [], $this->headers(self::MEMBER))->assertStatus(409);
    }
    public function test_private_attachments_are_scanned_bound_once_and_membership_guarded(): void
    {
        \Illuminate\Support\Facades\Storage::fake('s3');
        \Illuminate\Support\Facades\Storage::disk('s3')->buildTemporaryUrlsUsing(fn () => 'https://files.example.test/signed');
        config(['attachments.disk' => 's3', 'attachments.scanner_key' => str_repeat('s', 40)]);
        $id = $this->postJson('/api/v1/conversations', ['type' => 'direct', 'member_ids' => [self::MEMBER]], $this->headers())->assertCreated()->json('data.id');
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1, 'application/pdf');
        $attachment = $this->postJson("/api/v1/conversations/$id/attachments", ['file' => $file], $this->headers())->assertCreated()->json('data.id');
        $payload = ['client_message_id' => (string) Str::uuid(), 'attachments' => [$attachment]];
        $this->postJson("/api/v1/conversations/$id/messages", $payload, $this->headers())->assertUnprocessable();
        $hash = \App\Models\ChatAttachment::findOrFail($attachment)->sha256;
        $this->putJson("/api/v1/internal/attachments/$attachment/scan", ['sha256' => $hash, 'status' => 'clean'], ['X-Scanner-Key' => str_repeat('s', 40)])->assertNoContent();
        $this->postJson("/api/v1/conversations/$id/messages", $payload, $this->headers())->assertCreated()->assertJsonPath('data.attachments.0.id', $attachment);
        $this->postJson("/api/v1/conversations/$id/messages", $payload, $this->headers())->assertOk();
        $this->postJson("/api/v1/conversations/$id/messages", [...$payload, 'client_message_id' => (string) Str::uuid()], $this->headers())->assertUnprocessable();
        $this->getJson("/api/v1/conversations/$id/attachments/$attachment/download", $this->headers(self::MEMBER))->assertOk();
        $this->getJson("/api/v1/conversations/$id/attachments/$attachment/download", $this->headers(self::OTHER))->assertNotFound();
    }
}
