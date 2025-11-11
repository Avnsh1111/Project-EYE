<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use App\Services\ImageService;
use App\Services\SearchService;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;

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

    public $images = [];
    public $selectedImage = null;
    public $filterTag = '';
    public $showFavorites = false;
    public $showTrash = false;
    
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
        
        if ($this->searchQuery) {
            $this->performSearch();
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
        $this->images = $this->imageService->transformCollectionForDisplay($images);
        $this->searchResultsCount = 0;
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
            $this->images = $this->imageService->transformCollectionForDisplay($results);
            $this->searchResultsCount = count($this->images);
            
            Log::info('Gallery search completed', [
                'query' => $this->searchQuery,
                'results' => $this->searchResultsCount
            ]);
        } catch (\Exception $e) {
            Log::error('Gallery search failed', [
                'query' => $this->searchQuery,
                'error' => $e->getMessage()
            ]);
            $this->images = [];
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
        $this->loadImages();
        
        // Redirect to gallery without search parameter
        $this->redirect(route('gallery'), navigate: true);
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
        
        $this->selectedImage = collect($this->images)->firstWhere('id', $imageId);
        
        // Use ImageService to increment view count
        $this->imageService->incrementViewCount($imageId);
    }
    
    public function closeDetails()
    {
        $this->selectedImage = null;
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
        // Clear search when toggling favorites
        if ($this->showFavorites) {
            $this->searchQuery = '';
        }
        $this->loadImages();
    }
    
    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        // Clear search when toggling trash
        if ($this->showTrash) {
            $this->searchQuery = '';
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
        $this->selectedIds = collect($this->images)->pluck('id')->toArray();
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
    
    public function render()
    {
        return view('livewire.enhanced-image-gallery')
            ->layout('layouts.app');
    }
}

