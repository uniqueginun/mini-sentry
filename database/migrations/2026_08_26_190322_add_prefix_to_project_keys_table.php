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
        Schema::table('project_keys', function (Blueprint $table) {
            $table->string('prefix', 8)->default('')->after('public_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_keys', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};
