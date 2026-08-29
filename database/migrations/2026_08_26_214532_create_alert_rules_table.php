<?php

use App\Models\Project;
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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('environment')->nullable();
            $table->unsignedInteger('threshold')->nullable();
            $table->unsignedInteger('window_minutes')->nullable();
            $table->unsignedInteger('cooldown_minutes')->default(15);
            $table->boolean('enabled')->default(true);

            $table->index(['project_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
