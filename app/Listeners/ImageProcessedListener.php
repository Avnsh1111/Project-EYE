<?php

namespace App\Listeners;

use App\Events\ImageProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ImageProcessedListener implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(ImageProcessed $event): void
    {
        Log::info('Media processed', [
            'id'       => $event->imageFile->id,
            'filename' => $event->imageFile->original_filename,
            'status'   => $event->imageFile->processing_status,
        ]);
    }
}
