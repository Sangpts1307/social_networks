<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional. A previous interrupted foundation run can
        // leave the pre-document-access portion present; the recovery migration adds
        // the remaining tables without dropping any data.
        if (Schema::hasTable('document_access_rules')) {
            return;
        }

        if (! Schema::hasTable('knowledge_topics')) {
            Schema::create('knowledge_topics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id')->index();
                $table->string('code', 80);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status', 32)->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['organization_id', 'code']);
                $table->index(['organization_id', 'status', 'sort_order']);
            });
        }

        if (! Schema::hasTable('knowledge_categories')) {
            Schema::create('knowledge_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id')->index();
                $table->uuid('topic_id');
                $table->uuid('parent_id')->nullable();
                $table->string('code', 80);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status', 32)->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('topic_id')->references('id')->on('knowledge_topics')->cascadeOnDelete();
                $table->foreign('parent_id')->references('id')->on('knowledge_categories')->nullOnDelete();
                $table->unique(['topic_id', 'code']);
                $table->index(['organization_id', 'topic_id', 'status', 'sort_order'], 'knowledge_category_browse_idx');
            });
        } else {
            Schema::table('knowledge_categories', function (Blueprint $table) {
                $table->index(['organization_id', 'topic_id', 'status', 'sort_order'], 'knowledge_category_browse_idx');
            });
        }

        Schema::create('topic_experts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('topic_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('user_id')->index();
            $table->string('expert_role', 32)->default('sme');
            $table->boolean('is_primary')->default(false);
            $table->string('assignment_status', 32)->default('active');
            $table->uuid('assigned_by_user_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->foreign('topic_id')->references('id')->on('knowledge_topics')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('knowledge_categories')->nullOnDelete();
            $table->unique(['topic_id', 'category_id', 'user_id', 'expert_role']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('topic_id');
            $table->uuid('category_id');
            $table->uuid('author_user_id')->index();
            $table->string('title', 500);
            $table->mediumText('body_markdown')->nullable();
            $table->mediumText('body_plaintext')->nullable();
            $table->string('question_status', 32)->default('open');
            $table->string('visibility', 32)->default('organization');
            $table->uuid('accepted_answer_id')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by_user_id')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('answer_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('topic_id')->references('id')->on('knowledge_topics')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('knowledge_categories')->cascadeOnDelete();
            $table->index(['organization_id', 'question_status', 'created_at']);
            $table->index(['topic_id', 'category_id', 'question_status']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('question_id');
            $table->uuid('author_user_id')->index();
            $table->text('body_markdown');
            $table->text('body_plaintext');
            $table->string('answer_status', 32)->default('published');
            $table->boolean('is_accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->uuid('accepted_by_user_id')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->index(['question_id', 'answer_status', 'created_at']);
        });

        Schema::create('question_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->uuid('assignee_user_id')->index();
            $table->uuid('assigned_by_user_id')->nullable();
            $table->string('assignment_status', 32)->default('assigned');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->unique(['question_id', 'assignee_user_id']);
        });

        Schema::create('question_follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->uuid('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->unique(['question_id', 'user_id']);
        });

        Schema::create('question_bookmarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->uuid('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->unique(['question_id', 'user_id']);
        });

        Schema::create('qa_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->uuid('user_id');
            $table->smallInteger('vote_value');
            $table->timestamps();
            $table->unique(['target_type', 'target_id', 'user_id']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('question_bounties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->uuid('sponsor_user_id')->index();
            $table->unsignedInteger('points_amount');
            $table->string('bounty_status', 32)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->uuid('awarded_answer_id')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('normalized_name', 120);
            $table->string('display_name', 120);
            $table->string('status', 32)->default('active');
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'normalized_name']);
        });

        Schema::create('knowledge_tag_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tag_id');
            $table->string('target_type', 32);
            $table->uuid('target_id');
            $table->string('assigned_source', 32)->default('author');
            $table->uuid('assigned_by_user_id')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('tag_id')->references('id')->on('knowledge_tags')->cascadeOnDelete();
            $table->unique(['tag_id', 'target_type', 'target_id']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('topic_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('owner_user_id')->index();
            $table->string('title', 500);
            $table->text('summary')->nullable();
            $table->string('document_status', 32)->default('draft');
            $table->string('confidentiality', 32)->default('internal');
            $table->uuid('current_version_id')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('topic_id')->references('id')->on('knowledge_topics')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('knowledge_categories')->nullOnDelete();
            $table->index(['organization_id', 'document_status', 'published_at']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->unsignedInteger('version_number');
            $table->string('version_label', 64)->nullable();
            $table->text('change_summary')->nullable();
            $table->uuid('created_by_user_id');
            $table->string('approval_status', 32)->default('draft');
            $table->uuid('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('supersedes_version_id')->nullable();
            $table->timestamps();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('supersedes_version_id')->references('id')->on('document_versions')->nullOnDelete();
            $table->unique(['document_id', 'version_number']);
        });

        Schema::create('document_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_version_id');
            $table->string('object_key', 512);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->string('scan_status', 32)->default('pending');
            $table->string('extraction_status', 32)->default('pending');
            $table->string('preview_status', 32)->default('pending');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->foreign('document_version_id')->references('id')->on('document_versions')->cascadeOnDelete();
            $table->unique(['document_version_id', 'sha256']);
        });

        Schema::create('document_access_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->string('principal_type', 32);
            $table->uuid('principal_id')->nullable();
            $table->string('access_level', 32)->default('view');
            $table->uuid('granted_by_user_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->index(['document_id', 'principal_type', 'principal_id'], 'doc_access_principal_idx');
        });

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
        foreach (['processed_events', 'outbox_events', 'learning_moderation_actions', 'learning_content_reports', 'leaderboard_snapshots', 'reward_rules', 'reward_ledger_entries', 'reward_wallets', 'lesson_progress', 'course_enrollments', 'lessons', 'course_modules', 'courses', 'document_access_events', 'document_votes', 'document_follows', 'document_reviews', 'document_access_rules', 'document_files', 'document_versions', 'documents', 'knowledge_tag_links', 'knowledge_tags', 'question_bounties', 'qa_votes', 'question_bookmarks', 'question_follows', 'question_assignments', 'answers', 'questions', 'topic_experts', 'knowledge_categories', 'knowledge_topics'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
