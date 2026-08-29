<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Issue
 */
class IssueDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new IssueResource($this->resource))->toArray($request),
            'latest_event' => $this->when(
                $this->relationLoaded('latestEvent'),
                fn (): ?array => $this->latestEvent === null
                    ? null
                    : (new EventDetailResource($this->latestEvent))->resolve(),
            ),
        ];
    }
}
