<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueSort;
use App\Enums\IssueStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('view', $project) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'environment' => ['nullable', 'string', 'max:255'],
            'release' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['all', ...array_column(IssueStatus::cases(), 'value')])],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['required', Rule::enum(IssueSort::class)],
        ];
    }

    /**
     * @return array{environment: string|null, release: string|null, status: string, search: string|null, sort: string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'environment' => $validated['environment'] ?? null,
            'release' => $validated['release'] ?? null,
            'status' => $validated['status'],
            'search' => $validated['search'] ?? null,
            'sort' => $validated['sort'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', IssueStatus::Unresolved->value),
            'sort' => $this->input('sort', IssueSort::LastSeen->value),
            'environment' => $this->filled('environment') ? $this->string('environment')->toString() : null,
            'release' => $this->filled('release') ? $this->string('release')->toString() : null,
            'search' => $this->filled('search') ? $this->string('search')->trim()->toString() : null,
        ]);
    }
}
