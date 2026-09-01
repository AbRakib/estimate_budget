<?php

namespace App\Http\Resources;

use App\Models\ProjectFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectFeature
 */
class ProjectFeatureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'estimated_hours' => (float) $this->estimated_hours,
            'estimated_cost' => (float) $this->estimated_cost,
            'complexity' => $this->complexity,
            'sort_order' => $this->sort_order,
        ];
    }
}
