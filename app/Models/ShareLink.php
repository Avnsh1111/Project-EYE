<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareLink extends Model
{
    protected $fillable = [
        'user_id', 'media_file_id', 'token', 'password_hash',
        'expires_at', 'max_views', 'view_count', 'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_views'  => 'integer',
        'view_count' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
