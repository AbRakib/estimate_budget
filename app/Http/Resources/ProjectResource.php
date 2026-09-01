<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'hourly_rate' => (float) $this->hourly_rate,
            'country' => $this->country,
            'country_name' => data_get(config('estimator.countries', []), "{$this->country}.name"),
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_formatted' => $this->created_at?->format('M j, Y'),
            'features' => ProjectFeatureResource::collection($this->whenLoaded('features')),
            'estimate' => $this->whenLoaded('estimate', fn () => $this->estimate ? [
                'total_hours' => (float) $this->estimate->total_hours,
                'total_days' => round((float) $this->estimate->total_hours / (float) config('estimator.hours_per_day', 8), 2),
                'total_cost' => (float) $this->estimate->total_cost,
                'currency' => $this->estimate->currency,
                'ai_notes' => $this->estimate->ai_notes,
            ] : null),
        ];
    }
}
