<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->index(['organization_id', 'publication_status', 'published_at', 'id'], 'posts_feed_cursor');
            $table->index(['channel_id', 'publication_status', 'published_at', 'id'], 'posts_channel_cursor');
        });
        Schema::table('post_comments', fn (Blueprint $table) => $table->index(['post_id', 'status', 'created_at', 'id'], 'comments_cursor'));
        Schema::create('api_idempotency_keys', function (Blueprint $table): void {
            $table->char('scope_hash', 64)->primary();
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->mediumText('response_body')->nullable();
            $table->string('response_location', 512)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
        Schema::table('post_comments', fn (Blueprint $table) => $table->dropIndex('comments_cursor'));
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_feed_cursor');
            $table->dropIndex('posts_channel_cursor');
        });
    }
};
