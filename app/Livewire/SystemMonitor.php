<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use App\Services\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SystemMonitor extends Component
{
    public $systemStats = [];
    public $queueStats = [];
    public $databaseStats = [];
    public $aiServiceStats = [];
    public $processingHistory = [];
    public $diskUsage = [];
    
    public function mount()
    {
        // Load lightweight stats first for fast initial render
        $this->systemStats = $this->getSystemStatsFromCache();
        $this->queueStats = $this->getQueueStats();
        $this->databaseStats = $this->getDatabaseStatsFromCache();
        $this->aiServiceStats = $this->getAiServiceStatsFromCache();
        $this->processingHistory = $this->getProcessingHistoryFromCache();
        $this->diskUsage = $this->getDiskUsageFromCache();
    }
    
    public function loadAllStats()
    {
        // Use cached values where possible to speed up updates
        $this->systemStats = $this->getSystemStats();
        $this->queueStats = $this->getQueueStats();
        $this->databaseStats = $this->getDatabaseStats();
        $this->aiServiceStats = $this->getAiServiceStats();
        $this->processingHistory = $this->getProcessingHistory();
        $this->diskUsage = $this->getDiskUsage();
    }
    
    protected function getSystemStats()
    {
        $stats = [
            'uptime' => $this->getUptime(),
            'load_average' => $this->getLoadAverage(),
            'memory' => $this->getMemoryStats(),
            'cpu' => $this->getCpuStats(),
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Cache for historical tracking
        $this->cacheStats('system', $stats);
        
        return $stats;
    }
    
    protected function getUptime()
    {
        // Cache uptime for 30 seconds (doesn't change often)
        return Cache::remember('system_uptime', now()->addSeconds(30), function () {
            if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
                $uptime = @shell_exec('timeout 1 uptime 2>/dev/null');
                if ($uptime) {
                    if (preg_match('/up\s+(.+?),\s+\d+\s+user/', $uptime, $matches)) {
                        return trim($matches[1]);
                    }
                }
            }
            return 'N/A';
        });
    }
    
    protected function getLoadAverage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        }
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }
    
    protected function getMemoryStats()
    {
        $stats = [
            'used' => 0,
            'free' => 0,
            'total' => 0,
            'percent' => 0,
        ];
        
        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS - use cached result for speed
            $cached = Cache::get('memory_stats_macos');
            if ($cached) {
                return $cached;
            }
            
            $vm_stat = @shell_exec('timeout 1 vm_stat 2>/dev/null');
            if ($vm_stat) {
                preg_match('/Pages free:\s+(\d+)/', $vm_stat, $free);
                preg_match('/Pages active:\s+(\d+)/', $vm_stat, $active);
                preg_match('/Pages inactive:\s+(\d+)/', $vm_stat, $inactive);
                preg_match('/Pages wired down:\s+(\d+)/', $vm_stat, $wired);
                
                $pageSize = 4096; // bytes
                $free_pages = isset($free[1]) ? (int)$free[1] : 0;
                $active_pages = isset($active[1]) ? (int)$active[1] : 0;
                $inactive_pages = isset($inactive[1]) ? (int)$inactive[1] : 0;
                $wired_pages = isset($wired[1]) ? (int)$wired[1] : 0;
                
                $stats['free'] = round(($free_pages * $pageSize) / 1024 / 1024 / 1024, 2); // GB
                $stats['used'] = round((($active_pages + $wired_pages) * $pageSize) / 1024 / 1024 / 1024, 2); // GB
                $stats['total'] = $stats['used'] + $stats['free'] + round(($inactive_pages * $pageSize) / 1024 / 1024 / 1024, 2);
                
                if ($stats['total'] > 0) {
                    $stats['percent'] = round(($stats['used'] / $stats['total']) * 100, 1);
                }
                
                Cache::put('memory_stats_macos', $stats, now()->addSeconds(2));
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
                
                if (isset($total[1]) && isset($available[1])) {
                    $stats['total'] = round($total[1] / 1024 / 1024, 2); // GB
                    $stats['free'] = round($available[1] / 1024 / 1024, 2); // GB
                    $stats['used'] = round($stats['total'] - $stats['free'], 2);
                    $stats['percent'] = round(($stats['used'] / $stats['total']) * 100, 1);
                }
            }
        }
        
        // Fallback: PHP memory
        if ($stats['total'] == 0) {
            $memory_limit = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                $stats['total'] = $matches[1];
                if ($matches[2] == 'G') {
                    $stats['total'] = $matches[1];
                } elseif ($matches[2] == 'M') {
                    $stats['total'] = round($matches[1] / 1024, 2);
                }
            }
            $stats['used'] = round(memory_get_usage(true) / 1024 / 1024 / 1024, 2);
            $stats['percent'] = $stats['total'] > 0 ? round(($stats['used'] / $stats['total']) * 100, 1) : 0;
        }
        
        return $stats;
    }
    
    protected function getCpuStats()
    {
        $stats = [
            'usage' => 0,
            'cores' => 1,
        ];
        
        // Get CPU core count (cache for 1 hour - doesn't change)
        $stats['cores'] = Cache::remember('cpu_cores', now()->addHour(), function () {
            if (PHP_OS_FAMILY === 'Darwin') {
                $cores = @shell_exec('sysctl -n hw.ncpu 2>/dev/null');
                return $cores ? (int)trim($cores) : 1;
            } elseif (PHP_OS_FAMILY === 'Linux') {
                $cores = @shell_exec('nproc 2>/dev/null');
                return $cores ? (int)trim($cores) : 1;
            }
            return 1;
        });
        
        // Get CPU usage with timeout
        if (PHP_OS_FAMILY === 'Darwin') {
            // Use cached CPU usage (expensive call)
            $cached = Cache::get('cpu_usage_macos');
            if ($cached !== null) {
                $stats['usage'] = $cached;
            } else {
                $cpu = @shell_exec('timeout 1 top -l 1 2>/dev/null | grep "CPU usage"');
                if ($cpu && preg_match('/(\d+\.\d+)% user/', $cpu, $matches)) {
                    $stats['usage'] = round((float)$matches[1], 1);
                    Cache::put('cpu_usage_macos', $stats['usage'], now()->addSeconds(2));
                }
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Get CPU usage from /proc/stat
            $stats['usage'] = $this->getLinuxCpuUsage();
        }
        
        return $stats;
    }
    
    protected function getLinuxCpuUsage()
    {
        static $last_stat = null;
        
        $stat = @file_get_contents('/proc/stat');
        if (!$stat) return 0;
        
        preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat, $matches);
        if (!$matches) return 0;
        
        $current = [
            'user' => $matches[1],
            'nice' => $matches[2],
            'system' => $matches[3],
            'idle' => $matches[4],
        ];
        
        if ($last_stat === null) {
            $last_stat = $current;
            return 0;
        }
        
        $diff_idle = $current['idle'] - $last_stat['idle'];
        $diff_total = ($current['user'] + $current['nice'] + $current['system'] + $current['idle']) -
                      ($last_stat['user'] + $last_stat['nice'] + $last_stat['system'] + $last_stat['idle']);
        
        $last_stat = $current;
        
        return $diff_total > 0 ? round((($diff_total - $diff_idle) / $diff_total) * 100, 1) : 0;
    }
    
    protected function getQueueStats()
    {
        // Get queue jobs stats from database
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        
        // Get processing rate from cache
        $processed_1min = Cache::get('jobs_processed_1min', 0);
        $processed_5min = Cache::get('jobs_processed_5min', 0);
        
        return [
            'pending' => $pending,
            'failed' => $failed,
            'processed_1min' => $processed_1min,
            'processed_5min' => $processed_5min,
            'queue_worker_running' => $this->isQueueWorkerRunning(),
            'scheduler_running' => $this->isSchedulerRunning(),
        ];
    }
    
    protected function isQueueWorkerRunning()
    {
        // Check if queue worker has processed jobs recently (within last 2 minutes)
        $lastProcessed = Cache::get('last_queue_worker_activity');
        if ($lastProcessed) {
            return now()->diffInSeconds($lastProcessed) < 120;
        }
        return false;
    }
    
    protected function isSchedulerRunning()
    {
        // Check if scheduler has run recently (within last 2 minutes)
        $lastRun = Cache::get('last_scheduler_run');
        if ($lastRun) {
            return now()->diffInSeconds($lastRun) < 120;
        }
        return false;
    }
    
    protected function getDatabaseStats()
    {
        $stats = [
            'total_images' => ImageFile::count(),
            'processing' => ImageFile::where('processing_status', 'processing')->count(),
            'completed' => ImageFile::where('processing_status', 'completed')->count(),
            'failed' => ImageFile::where('processing_status', 'failed')->count(),
            'pending' => ImageFile::where('processing_status', 'pending')->count(),
            'with_faces' => ImageFile::whereNotNull('face_count')->where('face_count', '>', 0)->count(),
            'with_ollama_desc' => ImageFile::whereNotNull('detailed_description')->where('detailed_description', '!=', '')->count(),
            'database_size' => $this->getDatabaseSize(),
        ];
        
        return $stats;
    }
    
    protected function getDatabaseSize()
    {
        try {
            $result = DB::select("SELECT pg_size_pretty(pg_database_size(?)) as size", [config('database.connections.pgsql.database')]);
            return $result[0]->size ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
    
    protected function getAiServiceStats()
    {
        // Use cached AI stats to avoid slow HTTP calls on every load
        return Cache::remember('ai_service_stats_cache', now()->addSeconds(10), function () {
            try {
                $aiService = app(AiService::class);
                
                // Quick health check with timeout
                $health = false;
                $modelStatus = [];
                
                try {
                    $modelStatus = $aiService->getModelStatus();
                    $health = isset($modelStatus['status']) && $modelStatus['status'] === 'online';
                } catch (\Exception $e) {
                    // Service unavailable
                }
                
                return [
                    'status' => $health ? 'online' : 'offline',
                    'models_loaded' => $modelStatus['loaded_models'] ?? [],
                    'ollama_available' => $modelStatus['ollama_available'] ?? false,
                    'face_recognition_available' => $modelStatus['face_recognition_available'] ?? false,
                    'response_time' => $health ? $this->measureAiResponseTime() : 'N/A',
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'models_loaded' => [],
                    'ollama_available' => false,
                    'face_recognition_available' => false,
                    'response_time' => 'N/A',
                ];
            }
        });
    }
    
    protected function measureAiResponseTime()
    {
        try {
            $start = microtime(true);
            $aiService = app(AiService::class);
            $aiService->isHealthy();
            $end = microtime(true);
            return round(($end - $start) * 1000, 0) . 'ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
    
    protected function getProcessingHistory()
    {
        // Get processing history from last 24 hours
        $history = ImageFile::selectRaw('DATE_TRUNC(\'hour\', created_at) as hour, COUNT(*) as count')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour,
                    'count' => $item->count,
                ];
            });
        
        return $history;
    }
    
    protected function getDiskUsage()
    {
        $storagePath = storage_path('app/public/images');
        
        $stats = [
            'images_size' => 0,
            'images_count' => 0,
            'disk_free' => 0,
            'disk_total' => 0,
            'disk_used_percent' => 0,
        ];
        
        // Get images directory size
        if (is_dir($storagePath)) {
            $stats['images_size'] = $this->getDirectorySize($storagePath);
            $stats['images_count'] = ImageFile::count();
        }
        
        // Get disk space
        $stats['disk_free'] = round(@disk_free_space($storagePath) / 1024 / 1024 / 1024, 2); // GB
        $stats['disk_total'] = round(@disk_total_space($storagePath) / 1024 / 1024 / 1024, 2); // GB
        
        if ($stats['disk_total'] > 0) {
            $disk_used = $stats['disk_total'] - $stats['disk_free'];
            $stats['disk_used_percent'] = round(($disk_used / $stats['disk_total']) * 100, 1);
        }
        
        return $stats;
    }
    
    protected function getDirectorySize($path)
    {
        // Cache directory size for 5 minutes (it changes slowly)
        return Cache::remember('dir_size_' . md5($path), now()->addMinutes(5), function () use ($path) {
            $size = 0;
            try {
                if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
                    $output = @shell_exec("du -sb " . escapeshellarg($path));
                    if ($output && preg_match('/^(\d+)/', $output, $matches)) {
                        $size = $matches[1];
                    }
                }
            } catch (\Exception $e) {
                // Skip fallback for speed - return 0 if command fails
                return 0;
            }
            
            // Convert to GB
            return round($size / 1024 / 1024 / 1024, 2);
        });
    }
    
    protected function cacheStats($key, $data)
    {
        // Cache stats for historical tracking
        $cacheKey = 'system_monitor_' . $key . '_history';
        $history = Cache::get($cacheKey, []);
        
        $history[] = array_merge($data, ['timestamp' => now()->timestamp]);
        
        // Keep only last 60 entries (for charts)
        if (count($history) > 60) {
            $history = array_slice($history, -60);
        }
        
        Cache::put($cacheKey, $history, now()->addHours(2));
    }
    
    public function getHistoricalData($type)
    {
        $cacheKey = 'system_monitor_' . $type . '_history';
        return Cache::get($cacheKey, []);
    }
    
    // Cache helper methods for faster initial load
    protected function getSystemStatsFromCache()
    {
        return Cache::remember('system_stats_quick', now()->addSeconds(3), function () {
            return $this->getSystemStats();
        });
    }
    
    protected function getDatabaseStatsFromCache()
    {
        return Cache::remember('database_stats_quick', now()->addSeconds(10), function () {
            return $this->getDatabaseStats();
        });
    }
    
    protected function getAiServiceStatsFromCache()
    {
        return Cache::remember('ai_service_stats_quick', now()->addSeconds(5), function () {
            return $this->getAiServiceStats();
        });
    }
    
    protected function getProcessingHistoryFromCache()
    {
        return Cache::remember('processing_history_quick', now()->addMinutes(1), function () {
            return $this->getProcessingHistory();
        });
    }
    
    protected function getDiskUsageFromCache()
    {
        return Cache::remember('disk_usage_quick', now()->addMinutes(1), function () {
            return $this->getDiskUsage();
        });
    }
    
    public function render()
    {
        return view('livewire.system-monitor')->layout('layouts.app');
    }
}
