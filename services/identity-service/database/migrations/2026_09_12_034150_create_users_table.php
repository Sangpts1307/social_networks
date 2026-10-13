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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            // The foreign key is intentionally omitted: org_units.manager_user_id and this field form a cycle.
            $table->uuid('primary_org_unit_id')->nullable()->index();
            $table->string('email', 255);
            $table->string('employee_code', 64)->nullable();
            $table->string('display_name');
            $table->string('password_hash');
            $table->string('status', 32)->default('active');
            $table->timestamp('must_change_password_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedTinyInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'email']);
            $table->unique(['organization_id', 'employee_code']);
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
