<?php

use App\Enums\IssueStatus;
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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('fingerprint');
            $table->string('title');
            $table->string('culprit')->nullable();
            $table->string('status')->default(IssueStatus::Unresolved->value);
            $table->timestamp('first_seen');
            $table->timestamp('last_seen');
            $table->unsignedBigInteger('event_count')->default(0);
            $table->unsignedBigInteger('user_count')->default(0);

            $table->unique(['project_id', 'fingerprint']);
            $table->index(['project_id', 'status', 'last_seen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
