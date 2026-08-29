<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProject
{
    /**
     * Create a project for the team and generate its first key.
     */
    public function handle(User $user, Team $team, string $name): Project
    {
        return DB::transaction(function () use ($user, $team, $name): Project {
            $project = $team->projects()->create([
                'name' => $name,
                'user_id' => $user->id,
            ]);

            $key = $project->projectKeys()->create();
            $project->setRelation('projectKeys', collect([$key]));

            return $project;
        });
    }
}
