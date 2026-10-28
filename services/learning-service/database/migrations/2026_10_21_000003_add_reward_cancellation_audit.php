<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('reward_awards', function (Blueprint $table) {
            $table->timestamp('canceled_at')->nullable(); $table->string('cancellation_reason', 64)->nullable();
        });
        Schema::create('reward_award_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('organization_id')->index(); $table->uuid('award_id')->index();
            $table->string('status', 16); $table->string('reason', 64); $table->integer('points_amount'); $table->integer('coins_amount'); $table->timestamp('occurred_at');
        });
    }
    public function down(): void { Schema::dropIfExists('reward_award_transitions'); Schema::table('reward_awards', function (Blueprint $table) { $table->dropColumn(['canceled_at', 'cancellation_reason']); }); }
};
