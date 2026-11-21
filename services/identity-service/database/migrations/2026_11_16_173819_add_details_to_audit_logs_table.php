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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_name', 255)->nullable()->after('actor_user_id');
            $table->string('actor_email', 255)->nullable()->after('actor_name');
            $table->string('module', 64)->nullable()->after('event_type')->index();
            $table->string('ip_address', 64)->nullable()->after('ip_hash');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('risk_level', 32)->default('normal')->after('user_agent')->index();
            $table->string('status', 32)->default('success')->after('risk_level')->index();

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'risk_level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['organization_id', 'risk_level', 'created_at']);
            $table->dropColumn([
                'actor_name',
                'actor_email',
                'module',
                'ip_address',
                'user_agent',
                'risk_level',
                'status',
            ]);
        });
    }
};
