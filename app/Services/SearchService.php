<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Search Service
 * 
 * Handles all search-related operations for images.
 * Uses AI-powered semantic search with vector embeddings.
 */
class SearchService
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }
    /**
     * Minimum keyword length for searching.
     */
    const MIN_KEYWORD_LENGTH = 3;

    /**
     * Relevance score thresholds.
     */
    const SCORE_EXACT_DESCRIPTION = 100;
    const SCORE_EXACT_DETAILED = 95;
    const SCORE_EXACT_FILENAME = 90;
    const SCORE_EXACT_TAG = 85;
    const SCORE_BASE_KEYWORD = 40;
    const SCORE_KEYWORD_INCREMENT = 10;
    const SCORE_MAX_KEYWORD = 80;
    
    /**
     * Semantic similarity threshold (0-1 scale).
     * Results below this threshold are filtered out.
     */
    const SEMANTIC_SIMILARITY_THRESHOLD = 0.15;

    /**
     * Perform Elasticsearch-powered search for media files.
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return Collection
     */
    public function search(string $query, int $limit = 30): Collection
    {
        $startTime = microtime(true);
        $query = trim($query);
        
        if (strlen($query) < self::MIN_KEYWORD_LENGTH) {
            return collect([]);
        }

        Log::info('Performing Elasticsearch search', ['query' => $query, 'limit' => $limit]);

        try {
            // Use Laravel Scout with Elasticsearch for full-text search
            $results = MediaFile::search($query)
                ->where('processing_status', 'completed')
                ->take($limit)
                ->get();

            // Add search metadata
            $results->each(function ($model) {
                $model->similarity = 0.85; // High relevance from Elasticsearch
                $model->match_type = 'elasticsearch';
                $model->search_similarity = $model->similarity;
                $model->search_match_type = $model->match_type;
            });

            $searchTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Elasticsearch search completed', [
                'query' => $query,
                'results' => $results->count(),
                'search_time_ms' => $searchTime
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed, falling back to database search', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            // Fallback to simple database keyword search
            $results = MediaFile::where('processing_status', 'completed')
                ->where(function ($q) use ($query) {
                    $q->where('description', 'ILIKE', '%' . $query . '%')
                      ->orWhere('detailed_description', 'ILIKE', '%' . $query . '%')
                      ->orWhere('original_filename', 'ILIKE', '%' . $query . '%');
                })
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Add search metadata
            $results->each(function ($model) {
                $model->similarity = 0.70;
                $model->match_type = 'keyword';
                $model->search_similarity = $model->similarity;
                $model->search_match_type = $model->match_type;
            });

            $searchTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Fallback search completed', [
                'query' => $query,
                'results' => $results->count(),
                'search_time_ms' => $searchTime
            ]);

            return $results;
        }
    }

    /**
     * Optimized search: separate text and vector queries, then merge.
     * Much faster than combined query.
     *
     * @param string $query
     * @param array $queryEmbedding
     * @param int $limit
     * @return Collection
     */
    protected function optimizedSearch(string $query, array $queryEmbedding, int $limit): Collection
    {
        $embeddingString = '[' . implode(',', $queryEmbedding) . ']';
        $queryLower = strtolower($query);
        
        // Fast text search (uses indexes, limited results) - ONLY IMAGES
        $textResults = MediaFile::where('media_type', 'image')
            ->where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query, $queryLower) {
                $q->where('description', 'ilike', $query . '%')  // Prefix match is faster
                  ->orWhere('description', 'ilike', '% ' . $query . '%')
                  ->orWhere('original_filename', 'ilike', $query . '%');
            })
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $item->similarity = 1.0; // Text matches get 100% similarity
                $item->match_type = 'text';
                return $item;
            });
        
        // Fast vector search (uses HNSW index, optimized query) - ONLY IMAGES
        $vectorResults = \DB::select("
            SELECT 
                id,
                1 - (embedding <=> ?::vector) as similarity
            FROM media_files
            WHERE media_type = 'image'
              AND embedding IS NOT NULL
              AND deleted_at IS NULL
              AND processing_status = 'completed'
              AND (1 - (embedding <=> ?::vector)) >= ?
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ", [$embeddingString, $embeddingString, self::SEMANTIC_SIMILARITY_THRESHOLD, $embeddingString, $limit + 10]);
        
        // Get IDs and load models efficiently (single query)
        $ids = collect($vectorResults)->pluck('id')->toArray();
        if (empty($ids)) {
            return $textResults->take($limit);
        }
        
        $vectorModels = MediaFile::whereIn('id', $ids)
            ->get()
            ->keyBy('id');
        
        // Map similarity scores to models
        $vectorCollection = collect($vectorResults)->map(function ($item) use ($vectorModels) {
            $model = $vectorModels->get($item->id);
            if ($model) {
                $model->similarity = (float)$item->similarity;
                $model->match_type = 'semantic';
                return $model;
            }
            return null;
        })->filter();
        
        // Merge results, remove duplicates, sort by similarity
        $merged = $textResults->concat($vectorCollection)
            ->unique('id')
            ->sortByDesc('similarity')
            ->take($limit);
        
        return $merged->values();
    }
    
    /**
     * Fast keyword-only search (no AI embedding needed).
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    protected function fastKeywordSearch(string $query, int $limit): Collection
    {
        return MediaFile::where('media_type', 'image')
            ->where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('description', 'ilike', $query . '%')  // Prefix match
                  ->orWhere('description', 'ilike', '% ' . $query . '%')
                  ->orWhere('original_filename', 'ilike', $query . '%');
            })
            ->orderByRaw("
                CASE 
                    WHEN description ILIKE ? THEN 1
                    WHEN original_filename ILIKE ? THEN 2
                    ELSE 3
                END
            ", [$query . '%', $query . '%'])
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->similarity = 0.8; // Keyword matches get 80% similarity
                $item->match_type = 'keyword';
                return $item;
            });
    }

    /**
     * Fallback keyword search (original method).
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    protected function keywordSearch(string $query, int $limit): Collection
    {
        return MediaFile::where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $this->applySearchConditions($q, $query);
            })
            ->orderByRaw($this->getSortingClause(), $this->getSortingBindings($query))
            ->limit($limit)
            ->get();
    }

    /**
     * Apply search conditions to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return void
     */
    protected function applySearchConditions($query, string $searchTerm): void
    {
        $keywords = $this->extractKeywords($searchTerm);
        
        $query->where(function ($q) use ($searchTerm, $keywords) {
            // Exact phrase match (highest priority)
            $this->addExactPhraseConditions($q, $searchTerm);
            
            // Individual keyword matches
            $this->addKeywordConditions($q, $keywords);
        });
    }

    /**
     * Add exact phrase matching conditions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return void
     */
    protected function addExactPhraseConditions($query, string $searchTerm): void
    {
        // Search for the exact term
        $query->where('description', 'ilike', '%' . $searchTerm . '%')
              ->orWhere('detailed_description', 'ilike', '%' . $searchTerm . '%')
              ->orWhere('original_filename', 'ilike', '%' . $searchTerm . '%')
              ->orWhereJsonContains('meta_tags', $searchTerm);
        
        // Also search for word variations if it's a single word
        $words = explode(' ', trim($searchTerm));
        if (count($words) === 1) {
            $variations = $this->getWordVariations($searchTerm);
            foreach ($variations as $variation) {
                if ($variation !== strtolower($searchTerm)) {
                    $query->orWhere('description', 'ilike', '%' . $variation . '%')
                          ->orWhere('detailed_description', 'ilike', '%' . $variation . '%')
                          ->orWhere('original_filename', 'ilike', '%' . $variation . '%')
                          ->orWhereJsonContains('meta_tags', $variation);
                }
            }
        }
    }

    /**
     * Add individual keyword matching conditions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $keywords
     * @return void
     */
    protected function addKeywordConditions($query, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            if (strlen($keyword) >= self::MIN_KEYWORD_LENGTH) {
                // Search for the keyword
                $query->orWhere('description', 'ilike', '%' . $keyword . '%')
                      ->orWhere('detailed_description', 'ilike', '%' . $keyword . '%')
                      ->orWhereJsonContains('meta_tags', $keyword);
                
                // Also search for word variations
                $variations = $this->getWordVariations($keyword);
                foreach ($variations as $variation) {
                    if ($variation !== $keyword && strlen($variation) >= self::MIN_KEYWORD_LENGTH) {
                        $query->orWhere('description', 'ilike', '%' . $variation . '%')
                              ->orWhere('detailed_description', 'ilike', '%' . $variation . '%')
                              ->orWhereJsonContains('meta_tags', $variation);
                    }
                }
            }
        }
    }

    /**
     * Word variations for better matching (plural/singular, etc.)
     */
    const WORD_VARIATIONS = [
        'women' => ['woman', 'women'],
        'woman' => ['woman', 'women'],
        'men' => ['man', 'men'],
        'man' => ['man', 'men'],
        'children' => ['child', 'children'],
        'child' => ['child', 'children'],
        'people' => ['person', 'people'],
        'person' => ['person', 'people'],
    ];

    /**
     * Extract keywords from search term.
     *
     * @param string $searchTerm
     * @return array
     */
    protected function extractKeywords(string $searchTerm): array
    {
        return array_filter(explode(' ', strtolower($searchTerm)));
    }

    /**
     * Get all variations of a word (plural/singular).
     *
     * @param string $word
     * @return array
     */
    protected function getWordVariations(string $word): array
    {
        $word = strtolower($word);
        
        // Check if we have predefined variations
        if (isset(self::WORD_VARIATIONS[$word])) {
            return self::WORD_VARIATIONS[$word];
        }
        
        // Otherwise return the word itself
        return [$word];
    }

    /**
     * Get sorting clause for ORDER BY.
     *
     * @return string
     */
    protected function getSortingClause(): string
    {
        return "
            CASE
                WHEN description ILIKE ? THEN 1
                WHEN detailed_description ILIKE ? THEN 2
                WHEN original_filename ILIKE ? THEN 3
                ELSE 4
            END
        ";
    }

    /**
     * Get bindings for sorting clause.
     *
     * @param string $query
     * @return array
     */
    protected function getSortingBindings(string $query): array
    {
        $pattern = '%' . $query . '%';
        return [$pattern, $pattern, $pattern];
    }

    /**
     * Fast transform results (simplified, no expensive calculations).
     *
     * @param Collection $searchResults
     * @param string $query
     * @return Collection
     */
    protected function fastTransformResults($searchResults, string $query): Collection
    {
        // Results already have similarity and match_type from optimized search
        // Just ensure they're sorted and add search metadata
        return $searchResults->map(function ($result) use ($query) {
            // Convert similarity to percentage if needed
            if (!isset($result->similarity)) {
                $result->similarity = $result->search_similarity ?? 0.5;
            }
            
            // Ensure match type is set
            if (!isset($result->match_type)) {
                $result->match_type = $result->search_match_type ?? 'semantic';
            }
            
            // Add search metadata
            $result->search_similarity = (float)$result->similarity;
            $result->search_match_type = $result->match_type;
            
            return $result;
        })->sortByDesc('similarity')->values();
    }

    /**
     * Calculate relevance score based on match quality.
     *
     * @param MediaFile $image
     * @param string $query
     * @return int Score from 0-100
     */
    public function calculateRelevanceScore(MediaFile $image, string $query): int
    {
        $queryLower = strtolower($query);
        
        // Exact phrase matches (highest scores)
        if (stripos($image->description, $query) !== false) {
            return self::SCORE_EXACT_DESCRIPTION;
        }
        
        if ($image->detailed_description && stripos($image->detailed_description, $query) !== false) {
            return self::SCORE_EXACT_DETAILED;
        }
        
        if ($image->original_filename && stripos($image->original_filename, $query) !== false) {
            return self::SCORE_EXACT_FILENAME;
        }
        
        if ($image->meta_tags && $this->hasExactTag($image->meta_tags, $queryLower)) {
            return self::SCORE_EXACT_TAG;
        }
        
        // Check word variations (e.g., women → woman, men → man)
        // These should get high scores since they're the same concept
        $variations = $this->getWordVariations($queryLower);
        foreach ($variations as $variation) {
            if ($variation !== $queryLower) {
                if (stripos($image->description, $variation) !== false) {
                    return 95; // Almost as good as exact match
                }
                if ($image->detailed_description && stripos($image->detailed_description, $variation) !== false) {
                    return 90;
                }
                if ($image->meta_tags && $this->hasExactTag($image->meta_tags, $variation)) {
                    return 85;
                }
            }
        }
        
        // Keyword matching (lower scores)
        return $this->calculateKeywordScore($image, $queryLower);
    }

    /**
     * Check if tags contain exact match.
     *
     * @param array $tags
     * @param string $query
     * @return bool
     */
    protected function hasExactTag(array $tags, string $query): bool
    {
        return in_array($query, array_map('strtolower', $tags));
    }

    /**
     * Calculate score based on keyword matches.
     *
     * @param MediaFile $image
     * @param string $queryLower
     * @return int
     */
    protected function calculateKeywordScore(MediaFile $image, string $queryLower): int
    {
        $keywords = $this->extractKeywords($queryLower);
        $matchCount = 0;
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) >= self::MIN_KEYWORD_LENGTH) {
                if (stripos($image->description, $keyword) !== false) {
                    $matchCount++;
                }
                if ($image->detailed_description && stripos($image->detailed_description, $keyword) !== false) {
                    $matchCount++;
                }
                if ($image->meta_tags && $this->hasExactTag($image->meta_tags, $keyword)) {
                    $matchCount++;
                }
            }
        }
        
        return min(self::SCORE_MAX_KEYWORD, self::SCORE_BASE_KEYWORD + ($matchCount * self::SCORE_KEYWORD_INCREMENT));
    }

    /**
     * Generate image URL from file path.
     *
     * @param string $filePath
     * @return string
     */
    protected function generateImageUrl(string $filePath): string
    {
        return asset('storage/' . str_replace('public/', '', $filePath));
    }

    /**
     * Get search statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_images' => MediaFile::count(),
            'completed_images' => MediaFile::where('processing_status', 'completed')->count(),
            'pending_images' => MediaFile::where('processing_status', 'pending')->count(),
        ];
    }
}

