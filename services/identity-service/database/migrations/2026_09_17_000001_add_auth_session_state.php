<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('password_version')->default(1);
            $table->timestamp('locked_at')->nullable();
        });
        Schema::table('user_sessions', function (Blueprint $table) {
            // Zero invalidates any legacy sessions lacking a password version.
            $table->unsignedInteger('password_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('user_sessions', fn (Blueprint $table) => $table->dropColumn('password_version'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['password_version', 'locked_at']));
    }
};
