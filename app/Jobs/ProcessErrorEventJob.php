<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\ErrorEventProcessor;
use App\Services\FingerPrintCalculator;
use App\Services\IssueService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessErrorEventJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $eventPayload
     */
    public function __construct(public int $projectId, public array $eventPayload) {}

    /**
     * Execute the job.
     */
    public function handle(
        FingerPrintCalculator $fingerPrintCalculator,
        IssueService $issueService,
        ErrorEventProcessor $errorEventProcessor,
    ): void {
        $fingerprint = $fingerPrintCalculator->calculate($this->projectId, $this->eventPayload);
        
        $issue = $issueService->findOrCreateIssue($this->getProject(), $fingerprint, $this->eventPayload);

        $errorEventProcessor->process($issue, $this->eventPayload);
    }

    private function getProject(): Project
    {
        return Project::findOrFail($this->projectId);
    }
}
