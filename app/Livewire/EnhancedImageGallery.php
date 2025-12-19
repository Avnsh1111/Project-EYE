<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MediaFile;
use App\Services\ImageService;
use App\Services\SearchService;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnhancedImageGallery extends Component
{
    /**
     * Service instances.
     */
    protected ImageService $imageService;
    protected ImageRepository $imageRepository;
    protected SearchService $searchService;
    
    /**
     * Boot the component.
     */
    public function boot(
        ImageService $imageService,
        ImageRepository $imageRepository,
        SearchService $searchService
    ) {
        $this->imageService = $imageService;
        $this->imageRepository = $imageRepository;
        $this->searchService = $searchService;
    }

    public $files = [];
    public $selectedImage = null;
    public $nextFileId = null;
    public $previousFileId = null;
    public $filterTag = '';
    public $showFavorites = false;
    public $showTrash = false;
    public $facesFilter = ''; // Face/person filter (ID or name)
    public $facesFilterName = ''; // Display name for the face filter
    
    // Search
    public $searchQuery = '';
    public $isSearching = false;
    public $searchResultsCount = 0;
    
    // Selection mode
    public $selectionMode = false;
    public $selectedIds = [];
    
    // Filter and sort
    public $sortBy = 'date_taken'; // date_taken, created_at, name, size
    public $sortDirection = 'desc';
    
    // Image editor
    public $editingImage = null;
    public $rotation = 0;
    
    // Stats
    public $stats = [
        'total' => 0,
        'favorites' => 0,
        'trashed' => 0,
    ];
    
    public function mount()
    {
        // Ensure selection mode is explicitly off on mount
        $this->selectionMode = false;
        $this->selectedIds = [];
        
        // Check for search query from URL
        $this->searchQuery = request()->query('q', '');
        
        // Check for faces filter from URL (like Google Photos)
        $this->facesFilter = request()->query('faces', '');
        
        if ($this->searchQuery) {
            $this->performSearch();
        } elseif ($this->facesFilter) {
            $this->loadImagesByFace();
        } else {
            $this->loadImages();
        }
        
        $this->loadStats();
    }
    
    public function loadImages()
    {
        // Use ImageService to load and transform images
        $filters = [
            'showTrash' => $this->showTrash,
            'showFavorites' => $this->showFavorites,
            'filterTag' => $this->filterTag,
        ];
        
        $images = $this->imageService->loadImages($filters, $this->sortBy, $this->sortDirection);
        $this->files = $this->imageService->transformCollectionForDisplay($images);
        $this->searchResultsCount = 0;
    }
    
    public function loadImagesByFace()
    {
        // Find the face cluster to get its name
        $faceCluster = null;
        if (is_numeric($this->facesFilter)) {
            $faceCluster = \App\Models\FaceCluster::find($this->facesFilter);
        } else {
            $faceCluster = \App\Models\FaceCluster::where('name', $this->facesFilter)->first();
        }
        
        // Set display name
        if ($faceCluster) {
            $this->facesFilterName = $faceCluster->name ?? 'Unknown ' . ucfirst($faceCluster->type);
        } else {
            $this->facesFilterName = $this->facesFilter;
        }
        
        // Load images filtered by face/person (like Google Photos)
        $images = MediaFile::where('media_type', 'image')
            ->where('processing_status', 'completed')
            ->when(!$this->showTrash, function ($query) {
                $query->whereNull('deleted_at');
            })
            ->when($this->showTrash, function ($query) {
                $query->whereNotNull('deleted_at');
            })
            ->whereHas('detectedFaces.faceCluster', function ($query) {
                // Check if filter is numeric (ID) or string (name)
                if (is_numeric($this->facesFilter)) {
                    $query->where('id', $this->facesFilter);
                } else {
                    $query->where('name', $this->facesFilter);
                }
            })
            ->orderBy($this->sortBy === 'date_taken' ? 'date_taken' : 'created_at', $this->sortDirection)
            ->get();
        
        $this->files = $this->imageService->transformCollectionForDisplay($images);
        $this->searchResultsCount = count($this->files);
    }
    
    public function performSearch()
    {
        if (strlen($this->searchQuery) < 3) {
            $this->loadImages();
            return;
        }
        
        // Clear filters when searching
        $this->showFavorites = false;
        $this->showTrash = false;
        $this->filterTag = '';
        
        $this->isSearching = true;
        
        try {
            // Use SearchService for semantic search
            $results = $this->searchService->search($this->searchQuery, 50);
            $this->files = $this->imageService->transformCollectionForDisplay($results);
            $this->searchResultsCount = count($this->files);

            Log::info('Gallery search completed', [
                'query' => $this->searchQuery,
                'results' => $this->searchResultsCount
            ]);
        } catch (\Exception $e) {
            Log::error('Gallery search failed', [
                'query' => $this->searchQuery,
                'error' => $e->getMessage()
            ]);
            $this->files = [];
            $this->searchResultsCount = 0;
        }
        
        $this->isSearching = false;
    }
    
    public function updatedSearchQuery()
    {
        if ($this->searchQuery === '') {
            $this->clearSearch();
        }
    }
    
    public function search()
    {
        $this->performSearch();
    }
    
    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->searchResultsCount = 0;
        $this->facesFilter = '';
        $this->loadImages();
        
        // Redirect to gallery without search parameter
        $this->redirect(route('gallery'), navigate: true);
    }
    
    public function clearFacesFilter()
    {
        $this->facesFilter = '';
        $this->facesFilterName = '';
        $this->loadImages();
    }
    
    public function loadStats()
    {
        // Use ImageRepository to get statistics
        $stats = $this->imageRepository->getStatistics();
        $this->stats = [
            'total' => $stats['total'],
            'favorites' => $stats['favorites'],
            'trashed' => $stats['trashed'],
        ];
    }
    
    public function viewDetails($imageId)
    {
        // Prevent viewing details if in selection mode
        if ($this->selectionMode) {
            return;
        }

        $this->selectedImage = collect($this->files)->firstWhere('id', $imageId);
        
        // Calculate next and previous file IDs for navigation
        $currentIndex = collect($this->files)->search(fn($file) => $file['id'] == $imageId);
        
        if ($currentIndex !== false) {
            // Get next file
            $this->nextFileId = $currentIndex < count($this->files) - 1 
                ? $this->files[$currentIndex + 1]['id'] 
                : null;
            
            // Get previous file
            $this->previousFileId = $currentIndex > 0 
                ? $this->files[$currentIndex - 1]['id'] 
                : null;
        } else {
            $this->nextFileId = null;
            $this->previousFileId = null;
        }
        
        // Use ImageService to increment view count
        $this->imageService->incrementViewCount($imageId);
    }
    
    public function closeDetails()
    {
        $this->selectedImage = null;
        $this->nextFileId = null;
        $this->previousFileId = null;
        // Don't activate selection mode when closing details
        // This ensures normal browsing behavior
    }
    
    public function filterByTag($tag)
    {
        $this->filterTag = $tag;
        $this->closeDetails();
        $this->loadImages();
    }
    
    public function clearFilter()
    {
        $this->filterTag = '';
        $this->loadImages();
    }
    
    public function toggleFavorites()
    {
        $this->showFavorites = !$this->showFavorites;
        // Clear search and faces filter when toggling favorites
        if ($this->showFavorites) {
            $this->searchQuery = '';
            $this->facesFilter = '';
        }
        $this->loadImages();
    }
    
    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        // Clear search and faces filter when toggling trash
        if ($this->showTrash) {
            $this->searchQuery = '';
            $this->facesFilter = '';
        }
        $this->loadImages();
    }
    
    public function toggleFavorite($imageId)
    {
        // Use ImageService to toggle favorite
        $newStatus = $this->imageService->toggleFavorite($imageId);
        
        if ($newStatus !== false) {
            $this->loadImages();
            $this->loadStats();
            
            // Update selected image if open
            if ($this->selectedImage && $this->selectedImage['id'] == $imageId) {
                $this->selectedImage['is_favorite'] = $newStatus;
            }
        }
    }
    
    // Selection Mode
    public function toggleSelectionMode($forceOff = null)
    {
        // If forceOff is explicitly false, turn off selection mode
        if ($forceOff === false) {
            $this->selectionMode = false;
            $this->selectedIds = [];
            return;
        }
        
        // Otherwise, toggle
        $this->selectionMode = !$this->selectionMode;
        if (!$this->selectionMode) {
            $this->selectedIds = [];
        }
    }
    
    public function exitSelectionMode()
    {
        $this->selectionMode = false;
        $this->selectedIds = [];
    }
    
    public function toggleSelect($imageId)
    {
        // Only allow toggling selection when in selection mode
        if (!$this->selectionMode) {
            return;
        }
        
        if (in_array($imageId, $this->selectedIds)) {
            $this->selectedIds = array_diff($this->selectedIds, [$imageId]);
        } else {
            $this->selectedIds[] = $imageId;
        }
    }
    
    public function selectAll()
    {
        $this->selectedIds = collect($this->files)->pluck('id')->toArray();
    }
    
    public function deselectAll()
    {
        $this->selectedIds = [];
    }
    
    // Bulk Operations
    public function bulkDelete()
    {
        if (empty($this->selectedIds)) {
            return;
        }
        
        // Use ImageService for bulk delete
        $this->imageService->bulkDelete($this->selectedIds);
        
        $this->selectedIds = [];
        $this->selectionMode = false;
        $this->loadImages();
        $this->loadStats();
    }
    
    public function bulkFavorite()
    {
        if (empty($this->selectedIds)) {
            return;
        }
        
        // Use ImageService for bulk favorite
        $this->imageService->bulkUpdateFavorite($this->selectedIds, true);
        
        $this->selectedIds = [];
        $this->loadImages();
        $this->loadStats();
    }
    
    public function bulkUnfavorite()
    {
        if (empty($this->selectedIds)) {
            return;
        }
        
        // Use ImageService for bulk unfavorite
        $this->imageService->bulkUpdateFavorite($this->selectedIds, false);
        
        $this->selectedIds = [];
        $this->loadImages();
        $this->loadStats();
    }
    
    public function bulkDownload()
    {
        if (empty($this->selectedIds)) {
            return;
        }
        
        // Use ImageService to get download URLs
        $urls = $this->imageService->getBulkDownloadUrls($this->selectedIds);
        
        $this->dispatch('download-multiple', urls: $urls);
    }
    
    // Single Image Operations
    public function deleteImage($imageId)
    {
        // Use ImageService to delete
        if ($this->imageService->deleteImage($imageId)) {
            if ($this->selectedImage && $this->selectedImage['id'] == $imageId) {
                $this->closeDetails();
            }
            
            $this->loadImages();
            $this->loadStats();
        }
    }
    
    public function restoreImage($imageId)
    {
        // Use ImageService to restore
        if ($this->imageService->restoreImage($imageId)) {
            $this->loadImages();
            $this->loadStats();
        }
    }
    
    public function permanentlyDelete($imageId)
    {
        // Use ImageService to permanently delete
        if ($this->imageService->permanentlyDeleteImage($imageId)) {
            if ($this->selectedImage && $this->selectedImage['id'] == $imageId) {
                $this->closeDetails();
            }
            
            $this->loadImages();
            $this->loadStats();
        }
    }
    
    public function downloadImage($imageId)
    {
        $image = $this->imageRepository->findById($imageId, true);
        if ($image) {
            $url = $this->imageService->getImageUrl($image->file_path);
            $filename = $image->original_filename ?? basename($image->file_path);
            $this->dispatch('download-image', url: $url, filename: $filename);
        }
    }
    
    // Sorting
    public function updatedSortBy()
    {
        // Automatically reload images when sort changes
        if ($this->searchQuery) {
            $this->performSearch();
        } elseif ($this->facesFilter) {
            $this->loadImagesByFace();
        } else {
            $this->loadImages();
        }
    }

    public function sortByDate()
    {
        $this->sortBy = 'created_at';
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->loadImages();
    }
    
    public function sortByName()
    {
        $this->sortBy = 'original_filename';
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->loadImages();
    }

    public function editFile($imageId)
    {
        // Placeholder for future image editing functionality
        $this->editingImage = $imageId;
        
        // Log for debugging
        Log::info('Edit button clicked', ['image_id' => $imageId]);
        
        // Show message (will appear at top of gallery)
        $this->dispatch('notify', 
            message: 'Edit functionality coming soon!',
            type: 'info'
        );
    }

    public function reanalyze($imageId)
    {
        $image = MediaFile::find($imageId);
        if ($image) {
            // Reset processing status to rerun analysis
            $image->update([
                'processing_status' => 'pending',
                'processing_error' => null,
                'processing_started_at' => null,
                'processing_completed_at' => null,
            ]);

            // Dispatch job for reanalysis
            \App\Jobs\ProcessImageAnalysis::dispatch($image->id)
                ->onQueue('image-processing');

            $this->loadImages();
            $this->loadStats();
            $this->closeDetails();

            // Show success message
            session()->flash('message', 'Image queued for AI re-analysis! It will be processed shortly.');
        }
    }

    public function downloadFile($fileId)
    {
        $file = MediaFile::find($fileId);
        if ($file) {
            // Determine the appropriate download URL based on media type
            $url = match($file->media_type) {
                'image' => $this->imageService->getImageUrl($file->file_path),
                'document', 'code', 'other' => route('documents.download', $file->id),
                'video', 'audio', 'archive' => route('media.download', $file->id),
                default => $this->imageService->getImageUrl($file->file_path),
            };

            $filename = $file->original_filename ?? basename($file->file_path);
            $this->dispatch('download-image', url: $url, filename: $filename);
        }
    }

    public function render()
    {
        return view('livewire.enhanced-image-gallery')
            ->layout('layouts.app');
    }
}

