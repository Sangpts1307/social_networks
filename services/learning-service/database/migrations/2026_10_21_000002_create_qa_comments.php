<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('qa_comments', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('organization_id');
            $table->uuid('question_id'); $table->uuid('answer_id')->nullable(); $table->uuid('author_user_id');
            $table->text('body'); $table->timestamps(); $table->softDeletes();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->foreign('answer_id')->references('id')->on('answers')->cascadeOnDelete();
            $table->index(['question_id', 'answer_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('qa_comments'); }
};
