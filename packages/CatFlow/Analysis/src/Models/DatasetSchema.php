<?php

namespace CatFlow\Analysis\Models;

use CatFlow\File\Models\Dataset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatasetSchema extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ANALYZING = 'analyzing';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'dataset_id',
        'status',
        'model',
        'sample_size_used',
        'attempts',
        'confidence',
        'raw_response',
        'error_message',
        'error_reason',
        'analyzed_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_size_used' => 'integer',
            'attempts' => 'integer',
            'confidence' => 'float',
            'raw_response' => 'array',
            'analyzed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(DatasetColumn::class)->orderBy('sort_order');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function needsReview(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVIEW;
    }
}
