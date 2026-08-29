<?php

namespace App\Http\Requests\Projects;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Project::class, $this->team()]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Project::class)->where('team_id', $this->team()->id),
            ],
        ];
    }

    /**
     * Get the team associated with the request.
     */
    private function team(): Team
    {
        $team = $this->route('current_team');

        abort_if(! $team instanceof Team, 404);

        return $team;
    }
}
