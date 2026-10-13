<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('processed_events')) {
            return;
        }

        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->unique(['organization_id', 'user_id', 'notification_type'], 'notification_pref_unique');
        });
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('organization_id')->index(); $table->uuid('user_id')->index(); $table->string('platform', 32); $table->string('push_provider', 32); $table->text('push_token_encrypted'); $table->char('token_fingerprint', 64); $table->string('device_label')->nullable(); $table->string('app_version', 64)->nullable(); $table->string('status', 32)->default('active'); $table->timestamp('last_seen_at')->nullable(); $table->timestamp('revoked_at')->nullable(); $table->timestamps();
            $table->unique(['push_provider', 'token_fingerprint']); $table->index(['user_id', 'status', 'last_seen_at'], 'notification_device_state_idx');
        });
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('notification_id'); $table->string('channel', 32); $table->uuid('device_id')->nullable(); $table->string('destination_masked')->nullable(); $table->string('provider', 64)->nullable(); $table->string('provider_message_id')->nullable(); $table->string('delivery_status', 32)->default('queued'); $table->unsignedInteger('attempt_count')->default(0); $table->timestamp('next_attempt_at')->nullable(); $table->timestamp('sent_at')->nullable(); $table->timestamp('delivered_at')->nullable(); $table->timestamp('failed_at')->nullable(); $table->string('failure_code', 64)->nullable(); $table->text('failure_detail_redacted')->nullable(); $table->timestamps();
            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete(); $table->foreign('device_id')->references('id')->on('user_devices')->nullOnDelete(); $table->index(['notification_id', 'channel']); $table->index(['delivery_status', 'next_attempt_at']);
        });
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('organization_id')->index(); $table->uuid('created_by_user_id'); $table->string('title', 500); $table->text('body'); $table->string('priority', 16)->default('normal'); $table->string('source_service', 64)->nullable(); $table->string('source_type', 64)->nullable(); $table->uuid('source_id')->nullable(); $table->string('status', 32)->default('draft'); $table->timestamp('publish_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->boolean('send_email')->default(false); $table->boolean('send_push')->default(false); $table->timestamps(); $table->index(['organization_id', 'status', 'publish_at']);
        });
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('broadcast_id'); $table->string('target_type', 32); $table->uuid('target_id')->nullable(); $table->unsignedInteger('estimated_recipient_count')->nullable(); $table->timestamps();
            $table->foreign('broadcast_id')->references('id')->on('broadcasts')->cascadeOnDelete(); $table->index(['broadcast_id', 'target_type', 'target_id']);
        });
        Schema::create('broadcast_recipient_runs', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('broadcast_id'); $table->uuid('recipient_user_id'); $table->uuid('notification_id')->nullable(); $table->string('status', 32)->default('queued'); $table->timestamp('created_at')->useCurrent();
            $table->foreign('broadcast_id')->references('id')->on('broadcasts')->cascadeOnDelete(); $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete(); $table->unique(['broadcast_id', 'recipient_user_id']);
        });
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('aggregate_type', 128); $table->uuid('aggregate_id'); $table->string('event_type', 128); $table->unsignedSmallInteger('event_version')->default(1); $table->json('payload_json'); $table->timestamp('occurred_at'); $table->timestamp('published_at')->nullable(); $table->unsignedInteger('publish_attempts')->default(0); $table->text('last_error')->nullable(); $table->uuid('correlation_id')->nullable(); $table->timestamps(); $table->index(['published_at', 'occurred_at']);
        });
        Schema::create('processed_events', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('event_id'); $table->string('event_type', 128); $table->string('producer', 100); $table->timestamp('processed_at')->useCurrent(); $table->string('result_hash', 64)->nullable(); $table->timestamps(); $table->unique(['producer', 'event_id']);
        });
    }

    public function down(): void
    {
        foreach (['processed_events', 'outbox_events', 'broadcast_recipient_runs', 'broadcast_targets', 'broadcasts', 'notification_deliveries', 'user_devices'] as $table) { Schema::dropIfExists($table); }
        if (Schema::hasTable('user_notification_preferences')) {
            Schema::table('user_notification_preferences', function (Blueprint $table) { $table->dropUnique('notification_pref_unique'); });
        }
    }
};
