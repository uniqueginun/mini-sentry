<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'array'],
            'event.event_id' => ['required', 'string'],
            'event.timestamp' => ['required', 'date'],
            'event.platform' => ['required', 'string', 'max:64'],
            'event.logger' => ['required', 'nullable', 'string', 'max:128'],
            'event.environment' => ['required', 'string', 'max:64'],
            'event.release' => ['required', 'string', 'max:250'],
            'event.level' => ['required', 'string', Rule::in(['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'fatal'])],
            'event.handled' => ['sometimes', 'boolean:strict'],
            'event.culprit' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'event.fingerprint' => ['sometimes', 'nullable', 'array', 'list'],
            'event.fingerprint.*' => ['string', 'max:1024'],
            'event.exception' => ['sometimes', 'array'],
            'event.request' => ['sometimes', 'nullable', 'array'],
            'event.user' => ['sometimes', 'nullable', 'array'],
            'event.tags' => ['sometimes', 'array'],
            'event.contexts' => ['sometimes', 'array'],
            'event.breadcrumbs' => ['sometimes', 'array', 'list'],
        ];
    }
}
