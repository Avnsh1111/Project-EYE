<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\HasNeighbors;

class ImageFile extends Model
{
    use HasFactory, HasNeighbors, SoftDeletes;

    /**
     * Minimum similarity threshold for search results (0-1 scale).
     */
    const MIN_SIMILARITY = 0.35; // 35% - only return meaningful matches

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'original_filename',
        'description',
        'detailed_description',
        'meta_tags',
        'face_count',
        'face_encodings',
        'embedding',
        // File metadata
        'mime_type',
        'file_size',
        'width',
        'height',
        'exif_data',
        // EXIF fields
        'camera_make',
        'camera_model',
        'lens_model',
        'date_taken',
        'exposure_time',
        'f_number',
        'iso',
        'focal_length',
        'gps_latitude',
        'gps_longitude',
        'gps_location_name',
        // Gallery features
        'is_favorite',
        'view_count',
        'last_viewed_at',
        'edit_history',
        'album',
        // Processing status
        'processing_status',
        'processing_started_at',
        'processing_completed_at',
        'processing_error',
        'processing_attempts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'embedding' => Vector::class,
        'meta_tags' => 'array',
        'face_encodings' => 'array',
        'exif_data' => 'array',
        'date_taken' => 'datetime',
        'is_favorite' => 'boolean',
        'last_viewed_at' => 'datetime',
        'edit_history' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    /**
     * Search for similar images using vector similarity.
     *
     * @param array $queryEmbedding The query embedding vector
     * @param int $limit Number of results to return
     * @param float|null $minSimilarity Minimum similarity threshold
     * @return \Illuminate\Support\Collection
     */
    public static function searchSimilar(array $queryEmbedding, int $limit = 30, ?float $minSimilarity = null)
    {
        // Use class constant if not provided
        $minSimilarity = $minSimilarity ?? self::MIN_SIMILARITY;
        
        // Convert array to pgvector format
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';
        
        return DB::select("
            SELECT 
                id,
                file_path,
                description,
                detailed_description,
                meta_tags,
                face_count,
                1 - (embedding <=> ?::vector) AS similarity
            FROM image_files
            WHERE embedding IS NOT NULL
              AND deleted_at IS NULL
              AND (1 - (embedding <=> ?::vector)) >= ?
              AND processing_status = 'completed'
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ", [$vectorString, $vectorString, $minSimilarity, $vectorString, $limit]);
    }

    /**
     * Get the full URL for the image.
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . str_replace('public/', '', $this->file_path));
    }

    /**
     * Get the filename without path.
     *
     * @return string
     */
    public function getFilenameAttribute(): string
    {
        return basename($this->file_path);
    }
}

