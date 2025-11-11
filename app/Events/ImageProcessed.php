<?php

namespace App\Events;

use App\Models\ImageFile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ImageFile $imageFile
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('image-processing');
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->imageFile->id,
            'filename' => $this->imageFile->original_filename,
            'status' => $this->imageFile->processing_status,
            'description' => $this->imageFile->description,
            'url' => asset('storage/' . str_replace('public/', '', $this->imageFile->file_path)),
        ];
    }
}

