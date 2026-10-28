<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', fn (Blueprint $table) => $table->uuid('org_unit_id')->nullable()->index());
        Schema::table('document_access_rules', fn (Blueprint $table) => $table->string('effect', 8)->default('allow'));
    }

    public function down(): void
    {
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn('org_unit_id'));
        Schema::table('document_access_rules', fn (Blueprint $table) => $table->dropColumn('effect'));
    }
};
