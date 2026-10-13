<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable()->index();
            $table->uuid('actor_user_id')->nullable()->index();
            $table->string('event_type', 128);
            $table->string('entity_type', 128)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('action', 64);
            $table->uuid('request_id')->nullable()->index();
            $table->string('ip_hash', 128)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'entity_type', 'entity_id']);
            $table->index(['organization_id', 'event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
