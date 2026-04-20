<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageQuota extends Model
{
    protected $fillable = ['user_id', 'quota_bytes', 'used_bytes'];

    protected $casts = [
        'quota_bytes' => 'integer',
        'used_bytes'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
