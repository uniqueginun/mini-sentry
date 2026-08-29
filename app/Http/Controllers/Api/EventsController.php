<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Jobs\ProcessErrorEventJob;

class EventsController extends Controller
{
    public function __invoke(EventRequest $request)
    {
        $projectKey = request()->user('project-key')->load('project');

        ProcessErrorEventJob::dispatchSync(
            $projectKey->project->getKey(),
            $request->validated()
        );

        return response()->noContent();
    }
}
