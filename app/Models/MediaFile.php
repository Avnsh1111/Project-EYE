<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\HasNeighbors;

class MediaFile extends Model
{
    use HasFactory, HasNeighbors, SoftDeletes, Searchable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_files';

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
        'media_type',
        'batch_id',
        'file_path',
        'original_filename',
        'thumbnail_path',
        'description',
        'detailed_description',
        'meta_tags',
        'face_count',
        'face_encodings',
        'embedding',
        // Analysis coverage fields
        'objects_detected',
        'scene_classification',
        'dominant_colors',
        'image_quality',
        'quality_tier',
        'phash',
        'dhash',
        // Multi-pass VLM analysis fields
        'content_analysis',
        'people_analysis',
        'quality_analysis',
        'context_analysis',
        'combined_analysis',
        'analysis_mode',
        'analysis_passes_completed',
        'analysis_passes_failed',
        'analysis_duration_seconds',
        'analysis_completed_at',
        // File metadata
        'mime_type',
        'file_size',
        'width',
        'height',
        'exif_data',
        // EXIF fields (images)
        'camera_make',
        'camera_model',
        'lens_model',
        'date_taken',
        'exposure_time',
        'f_number',
        'iso',
        'focal_length',
        // Video/Audio fields
        'duration_seconds',
        'video_codec',
        'audio_codec',
        'bitrate',
        'fps',
        'resolution',
        // Document fields
        'page_count',
        'extracted_text',
        'document_type',
        'classification_confidence',
        'entities',
        // GPS data
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
        // Upload progress tracking
        'upload_progress',
        'upload_started_at',
        'upload_completed_at',
        'processing_stage',
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
        'edit_history' => 'array',
        'entities' => 'array',
        // Analysis coverage fields
        'objects_detected' => 'array',
        'scene_classification' => 'array',
        'dominant_colors' => 'array',
        'image_quality' => 'array',
        // Multi-pass VLM analysis casts
        'content_analysis' => 'array',
        'people_analysis' => 'array',
        'quality_analysis' => 'array',
        'context_analysis' => 'array',
        'combined_analysis' => 'array',
        'analysis_completed_at' => 'datetime',
        // Date/time casts
        'date_taken' => 'datetime',
        'is_favorite' => 'boolean',
        'last_viewed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'upload_started_at' => 'datetime',
        'upload_completed_at' => 'datetime',
        'upload_progress' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        // Automatically set media_type on creation
        static::creating(function ($media) {
            if (!$media->media_type) {
                $media->media_type = $media->getMediaType();
            }
        });

        // Apply global scope to filter by media type (only for concrete subclasses)
        static::addGlobalScope('media_type', function ($builder) {
            $class = get_called_class();

            // Don't apply scope if querying the base MediaFile class directly
            // This allows MediaFile::query() to return all media types
            if ($class === MediaFile::class) {
                return;
            }

            $instance = new static;
            $mediaType = $instance->getMediaType();
            $builder->where('media_type', $mediaType);
        });
    }

    /**
     * Get the media type for this model.
     * Must be overridden by subclasses.
     *
     * @return string
     */
    protected function getMediaType(): string
    {
        // Default implementation for querying all types
        // Subclasses must override this method
        return $this->media_type ?? 'unknown';
    }

    /**
     * Search for similar media using vector similarity.
     * Can search across all types or filter by specific types.
     *
     * @param array $queryEmbedding The query embedding vector
     * @param int $limit Number of results to return
     * @param array|null $mediaTypes Filter by media types (null = all types)
     * @param float|null $minSimilarity Minimum similarity threshold
     * @return \Illuminate\Support\Collection
     */
    public static function searchSimilar(
        array $queryEmbedding,
        int $limit = 30,
        ?array $mediaTypes = null,
        ?float $minSimilarity = null
    ) {
        // Use class constant if not provided
        $minSimilarity = $minSimilarity ?? self::MIN_SIMILARITY;

        // Convert array to pgvector format
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';

        // Build media type filter with proper parameter binding
        $mediaTypeFilter = '';
        $bindings = [$vectorString, $vectorString, $minSimilarity, $vectorString, $limit];

        if ($mediaTypes !== null && count($mediaTypes) > 0) {
            // Create placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($mediaTypes), '?'));
            $mediaTypeFilter = "AND media_type IN ($placeholders)";

            // Insert media types into bindings array before the last element (limit)
            array_splice($bindings, -1, 0, $mediaTypes);
        }

        return DB::select("
            SELECT
                id,
                media_type,
                file_path,
                original_filename,
                description,
                detailed_description,
                meta_tags,
                face_count,
                mime_type,
                width,
                height,
                duration_seconds,
                page_count,
                1 - (embedding <=> ?::vector) AS similarity
            FROM media_files
            WHERE embedding IS NOT NULL
              AND deleted_at IS NULL
              AND (1 - (embedding <=> ?::vector)) >= ?
              AND processing_status = 'completed'
              $mediaTypeFilter
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ", $bindings);
    }

    /**
     * Get the full URL for the media file.
     * Priority: thumbnail -> original file (for images) -> media type icon
     *
     * @return string
     */
    public function getFileUrlAttribute(): string
    {
        // Use thumbnail if available
        if ($this->thumbnail_path) {
            return asset('storage/' . str_replace('public/', '', $this->thumbnail_path));
        }

        // For images, use original file
        if ($this->media_type === 'image') {
            return asset('storage/' . str_replace('public/', '', $this->file_path));
        }

        // Return default icon URL based on media type
        return $this->getMediaTypeIcon();
    }

    /**
     * Get SVG data URI icon for media type.
     *
     * @return string
     */
    protected function getMediaTypeIcon(): string
    {
        // Return data URI SVG icons for different media types
        $icons = [
            'document' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%234285f4"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>'),
            'video' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ea4335"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>'),
            'audio' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23fbbc04"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>'),
            'archive' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%2334a853"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM8 14H6v-2h2v2zm0-3H6V9h2v2zm0-3H6V6h2v2zm7 6h-5v-2h5v2zm3-3h-8V9h8v2zm0-3h-8V6h8v2z"/></svg>'),
            'code' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%239e9e9e"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>'),
            'email' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%234285f4"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>'),
            'other' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%239e9e9e"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>'),
        ];

        // Default file icon
        return $icons[$this->media_type] ?? 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%239e9e9e"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>');
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

    /**
     * Get human-readable file size.
     *
     * @return string
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope to filter by processing status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessingStatus($query, string $status)
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Scope to get favorites.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope to get by album.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $album
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInAlbum($query, string $album)
    {
        return $query->where('album', $album);
    }

    /**
     * Get detected faces for this media file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function detectedFaces()
    {
        return $this->hasMany(DetectedFace::class, 'image_file_id');
    }

    /**
     * Get the folder that owns the media file.
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Mark processing as started.
     *
     * @return void
     */
    public function markProcessingStarted(): void
    {
        $this->update([
            'processing_status' => 'processing',
            'processing_started_at' => now(),
            'processing_attempts' => $this->processing_attempts + 1,
        ]);
    }

    /**
     * Mark processing as completed.
     *
     * @return void
     */
    public function markProcessingCompleted(): void
    {
        $this->update([
            'processing_status' => 'completed',
            'processing_completed_at' => now(),
            'processing_error' => null,
        ]);
    }

    /**
     * Mark processing as failed.
     *
     * @param string $error
     * @return void
     */
    public function markProcessingFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_completed_at' => now(),
            'processing_error' => $error,
        ]);
    }

    /**
     * Get the indexable data array for Elasticsearch.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename ?? '',
            'description' => $this->description ?? '',
            'detailed_description' => $this->detailed_description ?? '',
            'tags' => $this->meta_tags ? (is_array($this->meta_tags) ? implode(', ', $this->meta_tags) : $this->meta_tags) : '',
            'objects_detected' => $this->objects_detected ? (is_array($this->objects_detected) ? implode(', ', $this->objects_detected) : (string)$this->objects_detected) : '',
            'scene_classification' => $this->scene_classification ? (is_array($this->scene_classification) ? implode(', ', $this->scene_classification) : (string)$this->scene_classification) : '',
            'media_type' => $this->media_type ?? '',
            'mime_type' => $this->mime_type ?? '',
            'date_taken' => $this->date_taken ? $this->date_taken->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
            'is_favorite' => (bool)$this->is_favorite,
        ];
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable(): bool
    {
        // Only index completed files with descriptions
        return $this->processing_status === 'completed' 
            && !$this->trashed()
            && !empty($this->description);
    }

    /**
     * Get the Scout index name for the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'media_files';
    }
}
