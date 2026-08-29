<?php

namespace App\Http\Requests\Projects;

use App\Models\ProjectKey;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class RevokeProjectKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectKey = $this->route('projectKey');

        return $projectKey instanceof ProjectKey
            && Gate::allows('update', $projectKey);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $projectKey = $this->route('projectKey');

                if ($projectKey instanceof ProjectKey && ! $projectKey->is_active) {
                    $validator->errors()->add('projectKey', __('This key has already been revoked.'));
                }
            },
        ];
    }
}
