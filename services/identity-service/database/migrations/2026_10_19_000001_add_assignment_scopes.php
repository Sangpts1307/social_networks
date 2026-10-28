<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_role_assignments', function (Blueprint $table) {
            $table->string('scope_type', 32)->default('organization');
            $table->boolean('include_descendants')->default(false);
        });
        DB::table('user_role_assignments')->whereNotNull('org_unit_id')->update(['scope_type' => 'org_unit']);
    }

    public function down(): void
    {
        Schema::table('user_role_assignments', fn (Blueprint $table) => $table->dropColumn(['scope_type', 'include_descendants']));
    }
};
