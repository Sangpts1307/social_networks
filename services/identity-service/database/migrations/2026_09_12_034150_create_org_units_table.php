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
        Schema::create('org_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('parent_id')->nullable();
            // Kept as an internal UUID reference to avoid the users/org_units creation cycle.
            $table->uuid('manager_user_id')->nullable()->index();
            $table->string('code', 64);
            $table->string('name');
            $table->string('unit_type', 32)->default('department');
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('org_units')->nullOnDelete();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
