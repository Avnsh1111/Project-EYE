<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageSearch extends Component
{
    /**
     * Search service instance.
     */
    protected SearchService $searchService;
    
    /**
     * Boot the component.
     */
    public function boot(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search query.
     *
     * @var string
     */
    public $query = '';

    /**
     * Search results.
     *
     * @var array
     */
    public $results = [];

    /**
     * Searching status.
     *
     * @var bool
     */
    public $searching = false;

    /**
     * Error message.
     *
     * @var string|null
     */
    public $error = null;

    /**
     * Number of results to return.
     *
     * @var int
     */
    public $limit = 30;
    
    /**
     * Minimum similarity threshold (0-1).
     *
     * @var float
     */
    public $minSimilarity = 0.35;

    /**
     * Whether to show similarity scores.
     *
     * @var bool
     */
    public $showScores = true;

    /**
     * Search statistics.
     *
     * @var array
     */
    public $stats = [
        'total_images' => 0,
        'search_time' => 0
    ];

    /**
     * Mount component.
     */
    public function mount()
    {
        $stats = $this->searchService->getStats();
        $this->stats['total_images'] = $stats['total_images'];
    }

    /**
     * Perform database search.
     */
    public function search()
    {
        $this->validate([
            'query' => 'required|string|min:3|max:500'
        ]);

        $this->searching = true;
        $this->error = null;
        $this->results = [];

        $startTime = microtime(true);

        try {
            // Use SearchService for clean, optimized search
            $results = $this->searchService->search($this->query, $this->limit);
            
            // Transform ImageFile models to display arrays
            $this->results = $results->map(function ($image) {
                return [
                    'id' => $image->id,
                    'file_path' => $image->file_path,
                    'url' => asset('storage/' . str_replace('public/', '', $image->file_path)),
                    'description' => $image->description,
                    'similarity' => $image->search_similarity ?? 0,
                    'filename' => $image->original_filename ?? basename($image->file_path),
                    'match_type' => $image->search_match_type ?? 'keyword'
                ];
            })->toArray();

            $this->stats['search_time'] = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Search completed via service', [
                'query' => $this->query,
                'results_count' => count($this->results),
                'search_time_ms' => $this->stats['search_time']
            ]);

        } catch (Exception $e) {
            Log::error('Search failed', [
                'query' => $this->query,
                'error' => $e->getMessage()
            ]);

            $this->error = $e->getMessage();
        }

        $this->searching = false;
    }

    /**
     * Clear search results.
     */
    public function clear()
    {
        $this->reset(['query', 'results', 'error', 'searching']);
        $stats = $this->searchService->getStats();
        $this->stats['total_images'] = $stats['total_images'];
        $this->stats['search_time'] = 0;
    }

    /**
     * Toggle similarity scores visibility.
     */
    public function toggleScores()
    {
        $this->showScores = !$this->showScores;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.image-search')
            ->layout('layouts.app');
    }
}

