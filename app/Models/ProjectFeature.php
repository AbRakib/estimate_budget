<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'name',
    'description',
    'category',
    'estimated_hours',
    'estimated_cost',
    'complexity',
    'sort_order',
])]
class ProjectFeature extends Model
{
    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
