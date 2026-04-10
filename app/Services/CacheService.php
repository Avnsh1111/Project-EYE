<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Centralized cache management for AI analysis results.
 *
 * Implements content-based caching using SHA256 hashing of file path + modification time
 * to ensure cache invalidation when files are updated.
 */
class CacheService
{
    /**
     * Default TTL for AI analysis results (24 hours in seconds).
     */
    protected int $defaultTtl = 86400;

    /**
     * Cache key prefix for AI analysis results.
     */
    protected string $prefix = 'ai_analysis';

    /**
     * Cache key for tracking hit counter.
     */
    protected string $hitsKey = 'cache_stats:hits';

    /**
     * Cache key for tracking miss counter.
     */
    protected string $missesKey = 'cache_stats:misses';

    /**
     * Generate cache key for a file's AI analysis result.
     *
     * @param string $filePath Full or relative path to the file
     * @return string Cache key (SHA256 hash)
     */
    public function generateKey(string $filePath): string
    {
        // Convert relative Laravel storage paths to absolute paths
        if (!str_starts_with($filePath, '/')) {
            $filePath = Storage::path($filePath);
        }

        // Get file modification time to ensure cache invalidation on file updates
        $mtime = file_exists($filePath) ? filemtime($filePath) : time();

        // Generate content-based hash using absolute path for consistency
        $contentHash = hash('sha256', $filePath . '|' . $mtime);

        return "{$this->prefix}:{$contentHash}";
    }

    /**
     * Get cached AI analysis result for a file.
     *
     * @param string $filePath Full path to the file
     * @return array|null Cached analysis data or null if not found
     */
    public function get(string $filePath): ?array
    {
        $key = $this->generateKey($filePath);

        $result = Cache::get($key);

        if ($result !== null) {
            Cache::increment($this->hitsKey);
            Log::info('Cache hit for AI analysis', [
                'file_path' => $filePath,
                'cache_key' => $key,
            ]);
        } else {
            Cache::increment($this->missesKey);
        }

        return $result;
    }

    /**
     * Store AI analysis result in cache.
     *
     * @param string $filePath Full path to the file
     * @param array $data Analysis data to cache
     * @param int|null $ttl Time to live in seconds (null = use default 24h)
     * @return bool Success status
     */
    public function put(string $filePath, array $data, ?int $ttl = null): bool
    {
        $key = $this->generateKey($filePath);
        $ttl = $ttl ?? $this->defaultTtl;

        $success = Cache::put($key, $data, $ttl);

        if ($success) {
            Log::info('Cached AI analysis result', [
                'file_path' => $filePath,
                'cache_key' => $key,
                'ttl_seconds' => $ttl,
                'data_size' => strlen(json_encode($data)),
            ]);
        } else {
            Log::warning('Failed to cache AI analysis result', [
                'file_path' => $filePath,
                'cache_key' => $key,
            ]);
        }

        return $success;
    }

    /**
     * Invalidate cached AI analysis for a file.
     *
     * @param string $filePath Full path to the file
     * @return bool Success status
     */
    public function forget(string $filePath): bool
    {
        $key = $this->generateKey($filePath);

        $success = Cache::forget($key);

        Log::info('Invalidated cached AI analysis', [
            'file_path' => $filePath,
            'cache_key' => $key,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * Clear all AI analysis caches.
     *
     * @return bool Success status
     */
    public function flush(): bool
    {
        // This will clear all cache entries with our prefix
        // Note: Laravel's Cache::flush() clears ALL cache, so we use tags if available
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags([$this->prefix])->flush();
            Log::info('Flushed all AI analysis caches using tags');
            return true;
        }

        Log::warning('Cache store does not support tags, cannot flush AI analysis caches selectively');
        return false;
    }

    /**
     * Reset hit/miss counters to zero.
     *
     * @return void
     */
    public function resetStats(): void
    {
        Cache::put($this->hitsKey, 0, 86400 * 30);
        Cache::put($this->missesKey, 0, 86400 * 30);
    }

    /**
     * Get cache statistics for monitoring.
     *
     * @return array Cache hit/miss statistics
     */
    public function getStats(): array
    {
        $hits   = (int) Cache::get($this->hitsKey, 0);
        $misses = (int) Cache::get($this->missesKey, 0);
        $total  = $hits + $misses;

        return [
            'prefix'      => $this->prefix,
            'default_ttl' => $this->defaultTtl,
            'store'       => config('cache.default'),
            'hits'        => $hits,
            'misses'      => $misses,
            'hit_rate'    => $total > 0 ? round(($hits / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Check if a file's analysis is cached.
     *
     * @param string $filePath Full path to the file
     * @return bool True if cached, false otherwise
     */
    public function has(string $filePath): bool
    {
        $key = $this->generateKey($filePath);
        return Cache::has($key);
    }

    /**
     * Set custom TTL for cache entries.
     *
     * @param int $ttl Time to live in seconds
     * @return self
     */
    public function setTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }

    /**
     * Get current TTL setting.
     *
     * @return int TTL in seconds
     */
    public function getTtl(): int
    {
        return $this->defaultTtl;
    }
}
