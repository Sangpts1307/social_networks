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

        Schema::table('learning_moderation_actions', function (Blueprint $table) {
            $table->index(['target_type', 'target_id', 'created_at'], 'learning_mod_target_idx');
        });
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 128);
            $table->uuid('aggregate_id');
            $table->string('event_type', 128);
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->json('payload_json');
            $table->timestamp('occurred_at');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
            $table->index(['published_at', 'occurred_at']);
        });
        Schema::create('processed_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->string('event_type', 128);
            $table->string('producer', 100);
            $table->timestamp('processed_at')->useCurrent();
            $table->string('result_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['producer', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_events');
        Schema::dropIfExists('outbox_events');
        if (Schema::hasTable('learning_moderation_actions')) {
            Schema::table('learning_moderation_actions', function (Blueprint $table) {
                $table->dropIndex('learning_mod_target_idx');
            });
        }
    }
};
