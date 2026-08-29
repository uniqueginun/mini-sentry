<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendIssueAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $alertRuleId,
        public int $issueId,
        public string $eventId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void {}
}
