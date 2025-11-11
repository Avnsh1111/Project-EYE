<?php

namespace App\Services;

use App\Models\ImageFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Image Service
 * 
 * Handles common image operations including:
 * - URL generation
 * - Image transformations for display
 * - Bulk operations
 * - Statistics and analytics
 */
class ImageService
{
    protected MetadataService $metadataService;

    public function __construct(MetadataService $metadataService)
    {
        $this->metadataService = $metadataService;
    }

    /**
     * Transform image model to array for display.
     *
     * @param ImageFile $image
     * @return array
     */
    public function transformForDisplay(ImageFile $image): array
    {
        return [
            'id' => $image->id,
            'url' => $this->getImageUrl($image->file_path),
            'description' => $image->description,
            'detailed_description' => $image->detailed_description ?? $image->description,
            'meta_tags' => $image->meta_tags ?? [],
            'face_count' => $image->face_count ?? 0,
            'filename' => $image->original_filename ?? basename($image->file_path),
            'created_at' => $image->created_at->format('M d, Y'),
            'date_for_display' => $this->getDisplayDate($image),
            'is_favorite' => $image->is_favorite,
            'view_count' => $image->view_count ?? 0,
            // File metadata
            'mime_type' => $image->mime_type,
            'file_size' => $image->file_size ? $this->metadataService->formatFileSize($image->file_size) : null,
            'dimensions' => $this->formatDimensions($image->width, $image->height),
            'width' => $image->width,
            'height' => $image->height,
            // Camera info
            'camera_make' => $image->camera_make,
            'camera_model' => $image->camera_model,
            'lens_model' => $image->lens_model,
            'date_taken' => $image->date_taken ? $image->date_taken->format('M d, Y g:i A') : null,
            // Exposure settings
            'exposure_time' => $image->exposure_time,
            'f_number' => $image->f_number,
            'iso' => $image->iso ? 'ISO ' . $image->iso : null,
            'focal_length' => $image->focal_length ? $image->focal_length . 'mm' : null,
            // GPS
            'gps_latitude' => $image->gps_latitude,
            'gps_longitude' => $image->gps_longitude,
            'has_gps' => $image->gps_latitude && $image->gps_longitude,
            // Status
            'is_trashed' => $image->trashed(),
        ];
    }

    /**
     * Transform collection of images for display.
     *
     * @param Collection $images
     * @return array
     */
    public function transformCollectionForDisplay(Collection $images): array
    {
        return $images->map(fn($image) => $this->transformForDisplay($image))->toArray();
    }

    /**
     * Get image URL from file path.
     *
     * @param string $filePath
     * @return string
     */
    public function getImageUrl(string $filePath): string
    {
        return asset('storage/' . str_replace('public/', '', $filePath));
    }

    /**
     * Get display date (prefer date_taken over created_at).
     *
     * @param ImageFile $image
     * @return string
     */
    protected function getDisplayDate(ImageFile $image): string
    {
        if ($image->date_taken) {
            return $image->date_taken->format('M d, Y');
        }
        return $image->created_at->format('M d, Y');
    }

    /**
     * Format dimensions for display.
     *
     * @param int|null $width
     * @param int|null $height
     * @return string|null
     */
    protected function formatDimensions(?int $width, ?int $height): ?string
    {
        if ($width && $height) {
            return "{$width} × {$height}";
        }
        return null;
    }

    /**
     * Increment view count for an image.
     *
     * @param int $imageId
     * @return void
     */
    public function incrementViewCount(int $imageId): void
    {
        ImageFile::where('id', $imageId)->update([
            'view_count' => \DB::raw('view_count + 1'),
            'last_viewed_at' => now(),
        ]);
    }

    /**
     * Toggle favorite status for an image.
     *
     * @param int $imageId
     * @return bool New favorite status
     */
    public function toggleFavorite(int $imageId): bool
    {
        $image = ImageFile::withTrashed()->find($imageId);
        
        if (!$image) {
            return false;
        }
        
        $image->is_favorite = !$image->is_favorite;
        $image->save();
        
        return $image->is_favorite;
    }

    /**
     * Soft delete an image.
     *
     * @param int $imageId
     * @return bool
     */
    public function deleteImage(int $imageId): bool
    {
        $image = ImageFile::find($imageId);
        
        if (!$image) {
            return false;
        }
        
        return $image->delete();
    }

    /**
     * Restore a soft-deleted image.
     *
     * @param int $imageId
     * @return bool
     */
    public function restoreImage(int $imageId): bool
    {
        $image = ImageFile::withTrashed()->find($imageId);
        
        if (!$image || !$image->trashed()) {
            return false;
        }
        
        return $image->restore();
    }

    /**
     * Permanently delete an image.
     *
     * @param int $imageId
     * @return bool
     */
    public function permanentlyDeleteImage(int $imageId): bool
    {
        $image = ImageFile::withTrashed()->find($imageId);
        
        if (!$image) {
            return false;
        }
        
        try {
            // Delete actual file
            Storage::delete($image->file_path);
            
            // Permanently delete from database
            return $image->forceDelete();
        } catch (\Exception $e) {
            Log::error('Failed to permanently delete image', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Bulk update favorite status.
     *
     * @param array $imageIds
     * @param bool $isFavorite
     * @return int Number of images updated
     */
    public function bulkUpdateFavorite(array $imageIds, bool $isFavorite): int
    {
        return ImageFile::whereIn('id', $imageIds)->update(['is_favorite' => $isFavorite]);
    }

    /**
     * Bulk soft delete images.
     *
     * @param array $imageIds
     * @return int Number of images deleted
     */
    public function bulkDelete(array $imageIds): int
    {
        $count = 0;
        
        foreach ($imageIds as $imageId) {
            if ($this->deleteImage($imageId)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get download URLs for multiple images.
     *
     * @param array $imageIds
     * @return array
     */
    public function getBulkDownloadUrls(array $imageIds): array
    {
        return ImageFile::whereIn('id', $imageIds)
            ->get()
            ->map(fn($image) => $this->getImageUrl($image->file_path))
            ->toArray();
    }

    /**
     * Get gallery statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => ImageFile::count(),
            'favorites' => ImageFile::where('is_favorite', true)->count(),
            'trashed' => ImageFile::onlyTrashed()->count(),
            'completed' => ImageFile::where('processing_status', 'completed')->count(),
            'pending' => ImageFile::where('processing_status', 'pending')->count(),
            'processing' => ImageFile::where('processing_status', 'processing')->count(),
            'failed' => ImageFile::where('processing_status', 'failed')->count(),
        ];
    }

    /**
     * Load images with filters and sorting.
     *
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @return Collection
     */
    public function loadImages(array $filters = [], string $sortBy = 'date_taken', string $sortDirection = 'desc'): Collection
    {
        $query = ImageFile::query();
        
        // Apply filters
        if ($filters['showTrash'] ?? false) {
            $query->onlyTrashed();
        }
        
        if ($filters['showFavorites'] ?? false) {
            $query->where('is_favorite', true);
        }
        
        if (!empty($filters['filterTag'])) {
            $query->whereJsonContains('meta_tags', $filters['filterTag']);
        }
        
        // Apply sorting
        $this->applySorting($query, $sortBy, $sortDirection);
        
        return $query->get();
    }

    /**
     * Apply sorting to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy
     * @param string $sortDirection
     * @return void
     */
    protected function applySorting($query, string $sortBy, string $sortDirection): void
    {
        if ($sortBy === 'date_taken') {
            $query->orderByRaw('COALESCE(date_taken, created_at) ' . $sortDirection);
        } elseif ($sortBy === 'is_favorite') {
            $query->orderBy('is_favorite', 'desc')
                  ->orderByRaw('COALESCE(date_taken, created_at) desc');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}

