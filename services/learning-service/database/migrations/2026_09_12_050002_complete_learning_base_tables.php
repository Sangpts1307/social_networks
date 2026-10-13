<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learning_moderation_actions')) {
            return;
        }

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_version_id');
            $table->uuid('reviewer_user_id')->index();
            $table->string('review_action', 32);
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();
            $table->foreign('document_version_id')->references('id')->on('document_versions')->cascadeOnDelete();
        });
        Schema::create('document_follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->unique(['document_id', 'user_id']);
        });
        Schema::create('document_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('user_id');
            $table->smallInteger('vote_value');
            $table->timestamps();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->unique(['document_id', 'user_id']);
        });
        Schema::create('document_access_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('document_version_id')->nullable();
            $table->uuid('user_id')->index();
            $table->string('event_type', 32);
            $table->timestamp('occurred_at')->useCurrent();
            $table->uuid('correlation_id')->nullable();
            $table->string('ip_hash', 128)->nullable();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('document_version_id')->references('id')->on('document_versions')->nullOnDelete();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('topic_id')->nullable();
            $table->uuid('owner_user_id')->index();
            $table->string('title', 500);
            $table->text('description_markdown')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('organization');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('topic_id')->references('id')->on('knowledge_topics')->nullOnDelete();
        });
        Schema::create('course_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['course_id', 'sort_order']);
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_module_id');
            $table->string('lesson_type', 32);
            $table->string('title');
            $table->text('content_markdown')->nullable();
            $table->uuid('document_id')->nullable();
            $table->string('video_object_key', 512)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->foreign('course_module_id')->references('id')->on('course_modules')->cascadeOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
            $table->unique(['course_module_id', 'sort_order']);
        });
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('user_id');
            $table->string('enrollment_status', 32)->default('enrolled');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('assigned_by_user_id')->nullable();
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['course_id', 'user_id']);
        });
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id');
            $table->uuid('user_id');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->unique(['lesson_id', 'user_id']);
        });
        Schema::create('reward_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->bigInteger('points_balance')->default(0);
            $table->bigInteger('coins_balance')->default(0);
            $table->unsignedBigInteger('last_ledger_sequence')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['organization_id', 'user_id']);
        });
        Schema::create('reward_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('user_id')->index();
            $table->string('currency_type', 16);
            $table->bigInteger('amount_delta');
            $table->bigInteger('balance_after');
            $table->string('entry_type', 32);
            $table->string('reason_code', 64);
            $table->string('source_service', 100);
            $table->uuid('source_event_id')->nullable();
            $table->string('source_type', 64)->nullable();
            $table->uuid('source_id')->nullable();
            $table->string('rule_code', 80)->nullable();
            $table->timestamp('occurred_at');
            $table->uuid('reversal_of_entry_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['source_service', 'source_event_id', 'rule_code', 'currency_type'], 'reward_idempotency_unique');
        });
        Schema::create('reward_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('code', 80);
            $table->string('trigger_event_type', 128);
            $table->integer('points_delta')->default(0);
            $table->integer('coins_delta')->default(0);
            $table->json('eligibility_json')->nullable();
            $table->unsignedInteger('daily_limit')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->string('status', 32)->default('draft');
            $table->uuid('approved_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('period_type', 32);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('dimension_type', 32)->default('organization');
            $table->uuid('dimension_id')->nullable();
            $table->unsignedInteger('rank');
            $table->uuid('user_id');
            $table->bigInteger('points_total')->default(0);
            $table->bigInteger('coins_total')->default(0);
            $table->timestamp('generated_at')->useCurrent();
            $table->unique(['organization_id', 'period_type', 'period_start', 'dimension_type', 'dimension_id', 'rank'], 'leaderboard_rank_unique');
        });
        Schema::create('learning_content_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->uuid('reporter_user_id')->nullable();
            $table->string('source', 32)->default('user');
            $table->string('reason_code', 64);
            $table->text('detail')->nullable();
            $table->decimal('risk_score', 5, 4)->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id', 'status']);
        });
        Schema::create('learning_moderation_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->nullable();
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->string('action_type', 64);
            $table->string('actor_type', 32);
            $table->uuid('actor_user_id')->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->json('previous_state_json')->nullable();
            $table->json('new_state_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('report_id')->references('id')->on('learning_content_reports')->nullOnDelete();
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
        foreach (['processed_events', 'outbox_events', 'learning_moderation_actions', 'learning_content_reports', 'leaderboard_snapshots', 'reward_rules', 'reward_ledger_entries', 'reward_wallets', 'lesson_progress', 'course_enrollments', 'lessons', 'course_modules', 'courses', 'document_access_events', 'document_votes', 'document_follows', 'document_reviews'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
