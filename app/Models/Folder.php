<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $fillable = [
        'name',
        'path',
        'parent_id',
        'type',
        'icon',
        'description',
        'file_count',
        'total_size',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_count' => 'integer',
        'total_size' => 'integer',
    ];

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get all files in this folder.
     */
    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    /**
     * Get the user that owns the folder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the folder's breadcrumb trail.
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'path' => $current->path,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Update file count and size statistics.
     */
    public function updateStatistics(): void
    {
        $this->file_count = $this->mediaFiles()->count();
        $this->total_size = $this->mediaFiles()->sum('file_size');
        $this->save();
    }
}
