<?php

namespace App\Services;

use App\Models\ImageFile;
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
     * Perform AI-powered semantic search for images.
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return Collection
     */
    public function search(string $query, int $limit = 30): Collection
    {
        $startTime = microtime(true);

        Log::info('Performing AI-powered semantic search', ['query' => $query]);

        try {
            // Generate embedding for the search query
            $queryEmbedding = $this->aiService->embedText($query);
            
            if ($queryEmbedding && is_array($queryEmbedding) && count($queryEmbedding) > 0) {
                // Use semantic search with vector similarity
                $searchResults = $this->semanticSearch($query, $queryEmbedding, $limit);
            } else {
                // Fallback to keyword search if embedding generation fails
                Log::warning('Embedding generation failed, falling back to keyword search');
                $searchResults = $this->keywordSearch($query, $limit);
            }
        } catch (\Exception $e) {
            Log::error('Semantic search failed, falling back to keyword search', [
                'error' => $e->getMessage()
            ]);
            $searchResults = $this->keywordSearch($query, $limit);
        }

        // Transform results with relevance scores
        $results = $this->transformResults($searchResults, $query);

        $searchTime = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Search completed', [
            'query' => $query,
            'results_count' => $results->count(),
            'search_time_ms' => $searchTime
        ]);

        return $results;
    }

    /**
     * Perform semantic search using vector similarity.
     *
     * @param string $query
     * @param array $queryEmbedding
     * @param int $limit
     * @return Collection
     */
    protected function semanticSearch(string $query, array $queryEmbedding, int $limit): Collection
    {
        $embeddingString = '[' . implode(',', $queryEmbedding) . ']';
        
        // Use pgvector's cosine similarity for semantic search
        // Higher similarity = better match (range 0-1)
        return ImageFile::where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->whereNotNull('embedding')
            ->selectRaw('*, 1 - (embedding <=> ?::vector) as similarity', [$embeddingString])
            ->where(function ($q) use ($query) {
                // Also include keyword matches to boost exact matches
                $q->where('description', 'ilike', '%' . $query . '%')
                  ->orWhere('detailed_description', 'ilike', '%' . $query . '%')
                  ->orWhere('original_filename', 'ilike', '%' . $query . '%')
                  ->orWhereJsonContains('meta_tags', strtolower($query))
                  // OR semantic match (similarity handled by ordering)
                  ->orWhereRaw('1 - (embedding <=> ?::vector) > ?', [
                      $embeddingString,
                      self::SEMANTIC_SIMILARITY_THRESHOLD
                  ]);
            })
            ->orderByRaw('similarity DESC')
            ->limit($limit * 2) // Get more results for ranking
            ->get();
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
        return ImageFile::where('processing_status', 'completed')
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
     * Transform search results with relevance scores.
     *
     * @param Collection $searchResults
     * @param string $query
     * @return Collection
     */
    protected function transformResults($searchResults, string $query): Collection
    {
        // Add relevance score to each model without transforming to array
        return $searchResults->map(function ($result) use ($query) {
            $similarity = $this->calculateRelevanceScore($result, $query);
            
            // Add search metadata to the model
            $result->search_similarity = $similarity;
            $result->search_match_type = $similarity >= self::SCORE_EXACT_FILENAME ? 'exact' : 'keyword';
            
            return $result;
        })->sortByDesc('search_similarity')->values();
    }

    /**
     * Calculate relevance score based on match quality.
     *
     * @param ImageFile $image
     * @param string $query
     * @return int Score from 0-100
     */
    public function calculateRelevanceScore(ImageFile $image, string $query): int
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
     * @param ImageFile $image
     * @param string $queryLower
     * @return int
     */
    protected function calculateKeywordScore(ImageFile $image, string $queryLower): int
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
            'total_images' => ImageFile::count(),
            'completed_images' => ImageFile::where('processing_status', 'completed')->count(),
            'pending_images' => ImageFile::where('processing_status', 'pending')->count(),
        ];
    }
}

