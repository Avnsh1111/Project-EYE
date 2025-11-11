<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SystemMonitorService
{
    /**
     * Record queue worker activity
     */
    public static function recordQueueActivity()
    {
        Cache::put('last_queue_worker_activity', now(), now()->addMinutes(10));
        
        // Increment processed counter for 1 minute window
        $key1min = 'jobs_processed_1min';
        $count1min = Cache::get($key1min, 0);
        Cache::put($key1min, $count1min + 1, now()->addMinute());
        
        // Increment processed counter for 5 minute window
        $key5min = 'jobs_processed_5min';
        $count5min = Cache::get($key5min, 0);
        Cache::put($key5min, $count5min + 1, now()->addMinutes(5));
    }
    
    /**
     * Record scheduler activity
     */
    public static function recordSchedulerActivity()
    {
        Cache::put('last_scheduler_run', now(), now()->addMinutes(10));
    }
    
    /**
     * Get system statistics
     */
    public static function getStats()
    {
        return [
            'queue_worker_active' => self::isQueueWorkerActive(),
            'scheduler_active' => self::isSchedulerActive(),
            'jobs_processed_1min' => Cache::get('jobs_processed_1min', 0),
            'jobs_processed_5min' => Cache::get('jobs_processed_5min', 0),
        ];
    }
    
    /**
     * Check if queue worker is active
     */
    public static function isQueueWorkerActive()
    {
        $lastActivity = Cache::get('last_queue_worker_activity');
        if ($lastActivity) {
            return now()->diffInSeconds($lastActivity) < 120;
        }
        return false;
    }
    
    /**
     * Check if scheduler is active
     */
    public static function isSchedulerActive()
    {
        $lastRun = Cache::get('last_scheduler_run');
        if ($lastRun) {
            return now()->diffInSeconds($lastRun) < 120;
        }
        return false;
    }
}

