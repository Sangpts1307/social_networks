<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reward_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('organization_id')->index();
            $table->string('period_type', 16); $table->timestamp('period_start'); $table->timestamp('period_end');
            $table->string('status', 16)->default('draft'); $table->uuid('created_by_user_id'); $table->uuid('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable(); $table->timestamps();
        });
        Schema::create('reward_awards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('rule_id');
            $table->uuid('recipient_user_id');
            $table->uuid('actor_user_id');
            $table->uuid('source_id');
            $table->string('source_type', 32);
            $table->uuid('event_id');
            $table->integer('points_amount')->default(0);
            $table->integer('coins_amount')->default(0);
            $table->string('status', 16)->default('pending');
            $table->uuid('settlement_id')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at');
            $table->unique(['organization_id', 'rule_id', 'actor_user_id', 'source_type', 'source_id'], 'reward_unique_interaction');
            $table->index(['organization_id', 'recipient_user_id', 'rule_id', 'created_at'], 'reward_daily_count');
        });
    }
    public function down(): void { Schema::dropIfExists('reward_awards'); Schema::dropIfExists('reward_settlements'); }
};
