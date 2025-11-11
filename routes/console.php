<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule intelligent image reprocessing to run every 30 minutes
// This keeps improving image metadata when the system is idle
Schedule::command('images:reprocess --batch=20')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('Image reprocessing completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Image reprocessing failed');
    });
