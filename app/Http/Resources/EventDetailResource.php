<?php

namespace App\Http\Resources;

use App\Models\EventTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'occurred_at' => $this->occurred_at->toISOString(),
            'environment' => $this->environment,
            'release' => $this->release,
            'exception_type' => $this->exception_type,
            'message' => $this->message,
            'level' => $this->level(),
            'stacktrace' => $this->stackFrames(),
            'breadcrumbs' => $this->breadcrumbs(),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => $this->tags
                    ->map(fn (EventTag $tag): array => [
                        'key' => $tag->key,
                        'value' => $tag->value,
                    ])
                    ->values()
                    ->all(),
                [],
            ),
        ];
    }
}
