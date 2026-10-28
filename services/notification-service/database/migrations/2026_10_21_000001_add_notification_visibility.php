<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('notifications', fn (Blueprint $t) => $t->boolean('in_app_enabled')->default(true)); }
    public function down(): void { Schema::table('notifications', fn (Blueprint $t) => $t->dropColumn('in_app_enabled')); }
};
