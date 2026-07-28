<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'provider',
        'encrypted_key',
        'last_four',
    ];

    protected $hidden = [
        'encrypted_key',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_key' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
