<?php

namespace CatFlow\Batch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Output extends Model
{
    public const TYPE_RAW_OUTPUT = 'raw_output';

    public const TYPE_RAW_ERROR = 'raw_error';

    protected $fillable = [
        'batch_id',
        'type',
        'storage_path',
        'external_url',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
