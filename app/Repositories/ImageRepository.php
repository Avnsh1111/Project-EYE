<?php

namespace App\Repositories;

use App\Models\MediaFile;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Image Repository
 * 
 * Handles complex database queries and data access for images.
 * Provides a clean abstraction layer between services and models.
 */
class ImageRepository
{
    /**
     * Find image by ID.
     *
     * @param int $id
     * @param bool $withTrashed
     * @return MediaFile|null
     */
    public function findById(int $id, bool $withTrashed = false): ?MediaFile
    {
        $query = $withTrashed ? MediaFile::withTrashed() : MediaFile::query();
        return $query->find($id);
    }

    /**
     * Find multiple images by IDs.
     *
     * @param array $ids
     * @param bool $withTrashed
     * @return Collection
     */
    public function findByIds(array $ids, bool $withTrashed = false): Collection
    {
        $query = $withTrashed ? MediaFile::withTrashed() : MediaFile::query();
        return $query->whereIn('id', $ids)->get();
    }

    /**
     * Get all images with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection
    {
        $query = MediaFile::query();
        
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);
        
        return $query->get();
    }

    /**
     * Get paginated images.
     *
     * @param int $perPage
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 30, array $filters = [])
    {
        $query = MediaFile::query();
        
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);
        
        return $query->paginate($perPage);
    }

    /**
     * Count images matching filters.
     *
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int
    {
        $query = MediaFile::query();
        $this->applyFilters($query, $filters);
        return $query->count();
    }

    /**
     * Get images by status.
     *
     * @param string $status
     * @param int|null $limit
     * @return Collection
     */
    public function getByStatus(string $status, ?int $limit = null): Collection
    {
        $query = MediaFile::where('processing_status', $status);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get pending images for processing.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPendingImages(int $limit = 10): Collection
    {
        return MediaFile::where('processing_status', 'pending')
            ->whereNull('processing_started_at')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed images.
     *
     * @return Collection
     */
    public function getFailedImages(): Collection
    {
        return MediaFile::where('processing_status', 'failed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get favorite images.
     *
     * @return Collection
     */
    public function getFavorites(): Collection
    {
        return MediaFile::where('is_favorite', true)
            ->orderByRaw('COALESCE(date_taken, created_at) desc')
            ->get();
    }

    /**
     * Get trashed images.
     *
     * @return Collection
     */
    public function getTrashed(): Collection
    {
        return MediaFile::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();
    }

    /**
     * Get images by tag.
     *
     * @param string $tag
     * @return Collection
     */
    public function getByTag(string $tag): Collection
    {
        return MediaFile::whereJsonContains('meta_tags', $tag)
            ->orderByRaw('COALESCE(date_taken, created_at) desc')
            ->get();
    }

    /**
     * Get recently viewed images.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentlyViewed(int $limit = 20): Collection
    {
        return MediaFile::whereNotNull('last_viewed_at')
            ->orderBy('last_viewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search images by text.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchByText(string $query, int $limit = 30): Collection
    {
        return MediaFile::where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('description', 'ilike', '%' . $query . '%')
                  ->orWhere('detailed_description', 'ilike', '%' . $query . '%')
                  ->orWhere('original_filename', 'ilike', '%' . $query . '%')
                  ->orWhereJsonContains('meta_tags', $query);
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get images with faces.
     *
     * @return Collection
     */
    public function getImagesWithFaces(): Collection
    {
        return MediaFile::where('face_count', '>', 0)
            ->whereNotNull('face_encodings')
            ->orderByRaw('COALESCE(date_taken, created_at) desc')
            ->get();
    }

    /**
     * Get images by date range.
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return Collection
     */
    public function getByDateRange($startDate, $endDate): Collection
    {
        return MediaFile::whereBetween('date_taken', [$startDate, $endDate])
            ->orWhereBetween('created_at', [$startDate, $endDate])
            ->orderByRaw('COALESCE(date_taken, created_at) desc')
            ->get();
    }

    /**
     * Get images by camera.
     *
     * @param string $cameraMake
     * @param string|null $cameraModel
     * @return Collection
     */
    public function getByCamera(string $cameraMake, ?string $cameraModel = null): Collection
    {
        $query = MediaFile::where('camera_make', $cameraMake);
        
        if ($cameraModel) {
            $query->where('camera_model', $cameraModel);
        }
        
        return $query->orderByRaw('COALESCE(date_taken, created_at) desc')->get();
    }

    /**
     * Get images with GPS data.
     *
     * @return Collection
     */
    public function getImagesWithGps(): Collection
    {
        return MediaFile::whereNotNull('gps_latitude')
            ->whereNotNull('gps_longitude')
            ->orderByRaw('COALESCE(date_taken, created_at) desc')
            ->get();
    }

    /**
     * Get all unique tags.
     *
     * @return array
     */
    public function getAllTags(): array
    {
        $images = MediaFile::whereNotNull('meta_tags')->get();
        $tags = [];
        
        foreach ($images as $image) {
            if ($image->meta_tags) {
                $tags = array_merge($tags, $image->meta_tags);
            }
        }
        
        return array_values(array_unique($tags));
    }

    /**
     * Get all unique camera makes.
     *
     * @return Collection
     */
    public function getAllCameraMakes(): Collection
    {
        return MediaFile::whereNotNull('camera_make')
            ->distinct()
            ->pluck('camera_make');
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => MediaFile::where('media_type', 'image')->count(),
            'favorites' => MediaFile::where('media_type', 'image')->where('is_favorite', true)->count(),
            'trashed' => MediaFile::where('media_type', 'image')->onlyTrashed()->count(),
            'completed' => MediaFile::where('media_type', 'image')->where('processing_status', 'completed')->count(),
            'pending' => MediaFile::where('media_type', 'image')->where('processing_status', 'pending')->count(),
            'processing' => MediaFile::where('media_type', 'image')->where('processing_status', 'processing')->count(),
            'failed' => MediaFile::where('media_type', 'image')->where('processing_status', 'failed')->count(),
            'with_faces' => MediaFile::where('media_type', 'image')->where('face_count', '>', 0)->count(),
            'with_gps' => MediaFile::where('media_type', 'image')->whereNotNull('gps_latitude')->whereNotNull('gps_longitude')->count(),
        ];
    }

    /**
     * Apply filters to query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Show trashed
        if ($filters['showTrash'] ?? false) {
            $query->onlyTrashed();
        }
        
        // Show favorites
        if ($filters['showFavorites'] ?? false) {
            $query->where('is_favorite', true);
        }
        
        // Filter by tag
        if (!empty($filters['filterTag'])) {
            $query->whereJsonContains('meta_tags', $filters['filterTag']);
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('processing_status', $filters['status']);
        }
        
        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->where('date_taken', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date_taken', '<=', $filters['date_to']);
        }
        
        // Filter images with faces
        if ($filters['withFaces'] ?? false) {
            $query->where('face_count', '>', 0);
        }
        
        // Filter images with GPS
        if ($filters['withGps'] ?? false) {
            $query->whereNotNull('gps_latitude')->whereNotNull('gps_longitude');
        }
    }

    /**
     * Apply sorting to query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sortBy'] ?? 'date_taken';
        $sortDirection = $filters['sortDirection'] ?? 'desc';
        
        if ($sortBy === 'date_taken') {
            $query->orderByRaw('COALESCE(date_taken, created_at) ' . $sortDirection);
        } elseif ($sortBy === 'is_favorite') {
            $query->orderBy('is_favorite', 'desc')
                  ->orderByRaw('COALESCE(date_taken, created_at) desc');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Create a new image record.
     *
     * @param array $data
     * @return MediaFile
     */
    public function create(array $data): MediaFile
    {
        return MediaFile::create($data);
    }

    /**
     * Update an image record.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $image = $this->findById($id);
        
        if (!$image) {
            return false;
        }
        
        return $image->update($data);
    }

    /**
     * Delete an image (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $image = $this->findById($id);
        
        if (!$image) {
            return false;
        }
        
        return $image->delete();
    }

    /**
     * Force delete an image (permanent).
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool
    {
        $image = $this->findById($id, true);
        
        if (!$image) {
            return false;
        }
        
        return $image->forceDelete();
    }

    /**
     * Restore a soft-deleted image.
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        $image = MediaFile::withTrashed()->find($id);
        
        if (!$image || !$image->trashed()) {
            return false;
        }
        
        return $image->restore();
    }
}

