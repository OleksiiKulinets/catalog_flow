<?php

namespace CatFlow\File\Models;

use CatFlow\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dataset extends Model
{
    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_GOOGLE_SHEET = 'google_sheet';

    protected $fillable = [
        'user_id',
        'name',
        'source_type',
        'source_format',
        'original_filename',
        'mime_type',
        'extension',
        'storage_path',
        'file_size',
        'external_url',
        'spreadsheet_id',
        'sheet_gid',
        'synced_at',
        'rows_count',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'rows_count' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUpload(): bool
    {
        return $this->source_type === self::SOURCE_UPLOAD;
    }

    public function isGoogleSheet(): bool
    {
        return $this->source_type === self::SOURCE_GOOGLE_SHEET;
    }
}
