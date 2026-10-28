<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('post_id'); $table->uuid('user_id');
            $table->string('status', 16); $table->timestamps();
            $table->foreign('post_id')->references('post_id')->on('post_events')->cascadeOnDelete();
            $table->unique(['post_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('event_rsvps'); }
};
