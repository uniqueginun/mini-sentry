<?php

use App\Models\AlertRule;
use App\Models\Event;
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
        Schema::create('alert_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AlertRule::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Issue::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Event::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamp('fired_at');

            $table->index(['alert_rule_id', 'issue_id', 'fired_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_histories');
    }
};
