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
        Schema::create('processed_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_service', 100);
            $table->uuid('event_id');
            $table->string('event_type', 128);
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['source_service', 'event_id']);
            $table->index(['event_type', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_events');
    }
};
