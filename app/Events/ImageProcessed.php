<?php

namespace App\Events;

use App\Models\MediaFile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
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
        public MediaFile $imageFile
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('image-processing.' . $this->imageFile->user_id),
        ];
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

