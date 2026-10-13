<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('parent_channel_id')->nullable();
            $table->uuid('org_unit_id')->nullable()->index();
            $table->string('code', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('channel_type', 32)->default('custom');
            $table->string('visibility', 32)->default('members');
            $table->string('membership_policy', 32)->default('open');
            $table->uuid('owner_user_id')->nullable()->index();
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('parent_channel_id')->references('id')->on('social_channels')->nullOnDelete();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'channel_type', 'status']);
        });

        Schema::create('channel_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('channel_id');
            $table->uuid('user_id');
            $table->string('member_role', 32)->default('member');
            $table->string('membership_status', 32)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('notification_level', 32)->default('all');
            $table->timestamps();
            $table->foreign('channel_id')->references('id')->on('social_channels')->cascadeOnDelete();
            $table->unique(['channel_id', 'user_id']);
            $table->index(['user_id', 'membership_status']);
        });

        Schema::create('social_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 80);
            $table->string('name');
            $table->string('category_type', 32)->default('post');
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('parent_id')->references('id')->on('social_categories')->nullOnDelete();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('user_follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('follower_user_id');
            $table->uuid('followed_user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['follower_user_id', 'followed_user_id']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('channel_id')->nullable();
            $table->uuid('author_user_id')->index();
            $table->string('post_type', 32)->default('update');
            $table->string('title', 500)->nullable();
            $table->mediumText('body_markdown')->nullable();
            $table->mediumText('body_plaintext')->nullable();
            $table->string('visibility', 32)->default('channel');
            $table->string('publication_status', 32)->default('draft');
            $table->string('moderation_status', 32)->default('clear');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('comments_locked_at')->nullable();
            $table->uuid('created_by_user_id')->index();
            $table->uuid('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('channel_id')->references('id')->on('social_channels')->nullOnDelete();
            $table->index(['organization_id', 'publication_status', 'published_at']);
            $table->index(['channel_id', 'publication_status', 'published_at']);
            $table->index(['author_user_id', 'publication_status', 'published_at']);
        });

        Schema::create('post_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->string('attachment_type', 32);
            $table->string('object_key', 512)->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->string('scan_status', 32)->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->index(['post_id', 'sort_order']);
        });

        Schema::create('post_events', function (Blueprint $table) {
            $table->uuid('post_id')->primary();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('location_text')->nullable();
            $table->string('attendance_url', 1000)->nullable();
            $table->boolean('rsvp_enabled')->default(false);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamp('registration_deadline_at')->nullable();
            $table->timestamps();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('post_id');
            $table->uuid('parent_comment_id')->nullable();
            $table->uuid('author_user_id')->index();
            $table->text('body_markdown');
            $table->text('body_plaintext');
            $table->string('status', 32)->default('published');
            $table->string('moderation_status', 32)->default('clear');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('parent_comment_id')->references('id')->on('post_comments')->nullOnDelete();
            $table->index(['post_id', 'status', 'created_at']);
            $table->index(['parent_comment_id', 'status', 'created_at']);
        });

        Schema::create('social_post_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('category_id');
            $table->uuid('assigned_by_user_id')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('social_categories')->cascadeOnDelete();
            $table->unique(['post_id', 'category_id']);
        });

        Schema::create('post_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('user_id');
            $table->string('reaction_type', 32);
            $table->timestamps();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_saves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('post_id');
            $table->uuid('channel_id')->nullable();
            $table->uuid('pinned_by_user_id');
            $table->timestamp('pinned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->string('unpin_reason')->nullable();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('channel_id')->references('id')->on('social_channels')->nullOnDelete();
            $table->index(['channel_id', 'expires_at', 'pinned_at']);
        });

        Schema::create('post_stats', function (Blueprint $table) {
            $table->uuid('post_id')->primary();
            $table->unsignedInteger('reaction_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('save_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        Schema::create('announcement_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('user_id');
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('social_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('normalized_name', 120);
            $table->string('display_name', 120);
            $table->string('status', 32)->default('active');
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'normalized_name']);
        });

        Schema::create('post_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('tag_id');
            $table->string('assigned_source', 32)->default('author');
            $table->uuid('assigned_by_user_id')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('social_tags')->cascadeOnDelete();
            $table->unique(['post_id', 'tag_id']);
        });

        Schema::create('post_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->uuid('reporter_user_id')->nullable()->index();
            $table->string('report_reason_code', 64);
            $table->text('report_detail')->nullable();
            $table->string('source', 32)->default('user');
            $table->decimal('risk_score', 5, 4)->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id', 'status']);
            $table->index(['organization_id', 'status', 'opened_at']);
        });

        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->nullable();
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->string('action_type', 64);
            $table->string('reason_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->string('actor_type', 32);
            $table->uuid('actor_user_id')->nullable()->index();
            $table->json('previous_state_json')->nullable();
            $table->json('new_state_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('report_id')->references('id')->on('post_reports')->nullOnDelete();
            $table->index(['target_type', 'target_id', 'created_at']);
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
            $table->uuid('correlation_id')->nullable()->index();
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
        foreach (['processed_events', 'outbox_events', 'moderation_actions', 'post_reports', 'post_tags', 'social_tags', 'announcement_receipts', 'post_stats', 'post_pins', 'post_saves', 'post_reactions', 'social_post_categories', 'post_comments', 'post_events', 'post_attachments', 'posts', 'user_follows', 'social_categories', 'channel_members', 'social_channels'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
