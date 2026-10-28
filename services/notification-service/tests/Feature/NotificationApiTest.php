<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;
    private const ORG = '10000000-0000-4000-8000-000000000001';
    private const USER = '30000000-0000-4000-8000-000000000001';
    protected function setUp(): void { parent::setUp(); config(['services.gateway_key' => str_repeat('n', 40), 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]); }
    private function headers(string $user = self::USER): array { return ['X-Notification-Gateway-Key' => str_repeat('n', 40), 'X-Authenticated-User-Id' => $user, 'X-Authenticated-Organization-Id' => self::ORG]; }

    public function test_auth_owner_expiry_read_and_device_token_privacy(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $id = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $id, 'organization_id' => self::ORG, 'recipient_user_id' => self::USER,
            'notification_type' => 'comment', 'title' => 'Title', 'body' => 'Body', 'source_service' => 'social', 'created_at' => now()]);
        $this->getJson('/api/v1/notifications/unread-count', $this->headers())->assertOk()->assertJsonPath('data.unread_count', 1);
        $other = '30000000-0000-4000-8000-000000000002';
        $this->putJson("/api/v1/notifications/$id/read", [], $this->headers($other))->assertNotFound();
        $this->putJson("/api/v1/notifications/$id/read", [], $this->headers())->assertNoContent();
        $this->putJson("/api/v1/notifications/$id/read", [], $this->headers())->assertNoContent();
        $this->getJson('/api/v1/notifications/unread-count', $this->headers())->assertJsonPath('data.unread_count', 0);
        DB::table('notifications')->where('id', $id)->update(['expires_at' => now()->subSecond()]);
        $this->getJson('/api/v1/notifications', $this->headers())->assertJsonCount(0, 'data');
        $device = $this->postJson('/api/v1/devices', ['platform' => 'android', 'push_token' => 'secret-token'], $this->headers())
            ->assertCreated()->assertJsonMissing(['push_token' => 'secret-token'])->json('data.id');
        $stored = DB::table('user_devices')->where('id', $device)->first();
        $this->assertNotSame('secret-token', $stored->push_token_encrypted);
        $this->assertSame('secret-token', Crypt::decryptString($stored->push_token_encrypted));
        $this->deleteJson('/api/v1/devices/'.$device, [], $this->headers($other))->assertNotFound();
        $this->deleteJson('/api/v1/devices/'.$device, [], $this->headers())->assertNoContent();
    }

    public function test_preference_upserts_and_event_idempotency(): void
    {
        $this->putJson('/api/v1/notification-preferences/comment', ['in_app_enabled' => false, 'push_enabled' => true], $this->headers())->assertNoContent();
        $this->putJson('/api/v1/notification-preferences/comment', ['in_app_enabled' => true, 'push_enabled' => false], $this->headers())->assertNoContent();
        $this->assertDatabaseCount('user_notification_preferences', 1);
        $this->assertDatabaseHas('user_notification_preferences', ['email_enabled' => false]);
        config(['services.event_keys.social' => str_repeat('s', 40)]);
        $this->mock(\App\Services\IdentityDirectory::class)->shouldReceive('requireUsers')->once();
        $event = ['event_id' => (string) Str::uuid(), 'organization_id' => self::ORG, 'notification_type' => 'comment',
            'recipient_user_ids' => [self::USER], 'title' => 'Comment', 'body' => 'A comment'];
        $headers = ['X-Service-Name' => 'social', 'X-Service-Key' => str_repeat('s', 40)];
        $this->postJson('/api/v1/internal/notification-events', $event)->assertUnauthorized();
        $this->postJson('/api/v1/internal/notification-events', $event, $headers)->assertNoContent();
        $this->postJson('/api/v1/internal/notification-events', $event, $headers)->assertNoContent();
        $this->postJson('/api/v1/internal/notification-events', [...$event, 'body' => 'changed'], $headers)->assertStatus(409);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('processed_events', 1);
    }
    public function test_broadcast_permission_publish_retry_and_push_device_ownership(): void
    {
        $this->mock(\App\Services\IdentityDirectory::class)->shouldReceive('requireUsers')->twice();
        $this->postJson('/api/v1/admin/broadcasts', ['title' => 'Title', 'body' => 'Body', 'recipient_user_ids' => [self::USER]], $this->headers())->assertForbidden();
        $admin = [...$this->headers(), 'X-Authenticated-Permissions' => 'notification.broadcasts.manage'];
        $this->postJson('/api/v1/devices', ['platform' => 'android', 'push_token' => 'token'], $this->headers())->assertCreated();
        $id = $this->postJson('/api/v1/admin/broadcasts', ['title' => 'Title', 'body' => 'Body', 'recipient_user_ids' => [self::USER]], $admin)->assertCreated()->json('data.id');
        $this->postJson("/api/v1/admin/broadcasts/$id/publish", [], $admin)->assertNoContent();
        $this->postJson("/api/v1/admin/broadcasts/$id/publish", [], $admin)->assertNoContent();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
        // Reassigned tokens must never receive notifications belonging to their previous owner.
        $this->postJson('/api/v1/devices', ['platform' => 'android', 'push_token' => 'token'], $this->headers('30000000-0000-4000-8000-000000000002'))->assertCreated();
        $fcm = $this->mock(\App\Services\FcmClient::class);
        $fcm->shouldReceive('configured')->once()->andReturnTrue();
        $fcm->shouldNotReceive('send');
        $this->assertSame(0, app(\App\Services\PushDelivery::class)->deliver());
        $this->assertDatabaseHas('notification_deliveries', ['delivery_status' => 'canceled']);
    }

    public function test_push_delivery_retries_and_records_only_provider_result(): void
    {
        $device = $this->postJson('/api/v1/devices', ['platform' => 'android', 'push_token' => 'token'], $this->headers())->assertCreated()->json('data.id');
        $id = app(\App\Services\NotificationFanout::class)->create(['organization_id' => self::ORG, 'notification_type' => 'comment', 'title' => 'Title', 'body' => 'Body', 'producer' => 'social'], self::USER);
        $fcm = $this->mock(\App\Services\FcmClient::class);
        $fcm->shouldReceive('configured')->twice()->andReturnTrue();
        $fcm->shouldReceive('send')->once()->andReturn(['sent' => false, 'invalid_token' => false, 'retryable' => true, 'failure_code' => 'FCM_HTTP_503']);
        $this->assertSame(0, app(\App\Services\PushDelivery::class)->deliver());
        $this->assertDatabaseHas('notification_deliveries', ['notification_id' => $id, 'delivery_status' => 'queued', 'attempt_count' => 1]);
        $this->travel(31)->seconds();
        $fcm->shouldReceive('send')->once()->andReturn(['sent' => true, 'provider_message_id' => 'projects/test/messages/1']);
        $this->assertSame(1, app(\App\Services\PushDelivery::class)->deliver());
        $this->assertDatabaseHas('notification_deliveries', ['notification_id' => $id, 'delivery_status' => 'sent', 'attempt_count' => 2]);
    }
    public function test_domain_event_maps_to_notification_and_replay_does_not_duplicate(): void
    {
        $this->mock(\App\Services\IdentityDirectory::class)->shouldReceive('requireUsers')->once();
        $event = ['event_id' => (string) Str::uuid(), 'event_type' => 'chat.message.created.v1', 'source_service' => 'chat',
            'payload' => ['organization_id' => self::ORG, 'actor_user_id' => '30000000-0000-4000-8000-000000000002',
                'recipient_user_ids' => [self::USER], 'message_id' => (string) Str::uuid()]];
        $consumer = app(\App\Services\NotificationEventConsumer::class);
        $consumer->process($event); $consumer->process($event);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['recipient_user_id' => self::USER, 'notification_type' => 'chat_message']);
        $this->expectException(\InvalidArgumentException::class);
        $consumer->process([...$event, 'source_service' => 'social']);
    }
    public function test_inactive_recipient_does_not_block_other_members_in_a_group_event(): void
    {
        $directory = $this->mock(\App\Services\IdentityDirectory::class);
        $directory->shouldReceive('requireUsers')->once()->andThrow(new \Symfony\Component\HttpKernel\Exception\HttpException(422));
        $directory->shouldReceive('activeUsers')->once()->andReturn([self::USER]);
        app(\App\Services\NotificationIngestor::class)->ingest('chat', ['event_id' => (string) Str::uuid(), 'organization_id' => self::ORG,
            'notification_type' => 'chat_message', 'recipient_user_ids' => [self::USER, '30000000-0000-4000-8000-000000000002'], 'title' => 'Title', 'body' => 'Body']);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['recipient_user_id' => self::USER]);
    }
}
