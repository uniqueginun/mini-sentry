<?php

use App\Models\Issue;
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
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Issue::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('event_id');
            $table->string('exception_type')->nullable();
            $table->text('message')->nullable();
            $table->string('environment')->nullable();
            $table->string('release')->nullable();
            $table->timestamp('occurred_at');
            $table->jsonb('payload');

            $table->unique(['project_id', 'event_id']);
            $table->index(['issue_id', 'occurred_at']);
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
