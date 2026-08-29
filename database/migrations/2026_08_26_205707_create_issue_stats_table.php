<?php

use App\Models\Issue;
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
        Schema::create('issue_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Issue::class)->constrained()->cascadeOnDelete();
            $table->timestamp('bucket');
            $table->unsignedBigInteger('count')->default(0);

            $table->unique(['issue_id', 'bucket']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_stats');
    }
};
