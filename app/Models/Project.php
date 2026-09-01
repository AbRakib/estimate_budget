<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'title',
    'file_path',
    'requirements_text',
    'status',
    'hourly_rate',
    'country',
    'raw_ai_response',
    'failure_reason',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ProjectFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(ProjectFeature::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<ProjectEstimate, $this>
     */
    public function estimate(): HasOne
    {
        return $this->hasOne(ProjectEstimate::class);
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'raw_ai_response' => 'array',
        ];
    }
}
