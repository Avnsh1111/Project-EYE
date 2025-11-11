<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use Livewire\Attributes\On;

class ProcessingStatus extends Component
{
    public $processing_images = [];
    public $completed_images = [];
    public $failed_images = [];
    public $stats = [];

    public function mount()
    {
        $this->loadStatus();
    }

    #[On('echo:image-processing,ImageProcessed')]
    public function imageProcessed($event)
    {
        // Reload status when an image is processed
        $this->loadStatus();
    }

    public function loadStatus()
    {
        // Get images by status
        $this->processing_images = ImageFile::where('processing_status', 'processing')
            ->orderBy('processing_started_at', 'desc')
            ->take(20)
            ->get()
            ->map(fn($img) => $this->formatImage($img))
            ->toArray();

        $this->completed_images = ImageFile::where('processing_status', 'completed')
            ->whereDate('processing_completed_at', '>=', now()->subHours(24))
            ->orderBy('processing_completed_at', 'desc')
            ->take(20)
            ->get()
            ->map(fn($img) => $this->formatImage($img))
            ->toArray();

        $this->failed_images = ImageFile::where('processing_status', 'failed')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($img) => $this->formatImage($img))
            ->toArray();

        // Calculate stats
        $this->stats = [
            'pending' => ImageFile::where('processing_status', 'pending')->count(),
            'processing' => ImageFile::where('processing_status', 'processing')->count(),
            'completed' => ImageFile::where('processing_status', 'completed')->count(),
            'failed' => ImageFile::where('processing_status', 'failed')->count(),
            'total' => ImageFile::count(),
        ];
    }

    protected function formatImage($image)
    {
        return [
            'id' => $image->id,
            'filename' => $image->original_filename ?? basename($image->file_path),
            'url' => asset('storage/' . str_replace('public/', '', $image->file_path)),
            'status' => $image->processing_status,
            'description' => $image->description,
            'processing_time' => $image->processing_started_at && $image->processing_completed_at 
                ? $image->processing_started_at->diffInSeconds($image->processing_completed_at) . 's'
                : null,
            'started_at' => $image->processing_started_at?->diffForHumans(),
            'completed_at' => $image->processing_completed_at?->diffForHumans(),
            'error' => $image->processing_error,
        ];
    }

    public function retryFailed($imageId)
    {
        $image = ImageFile::find($imageId);
        if ($image && $image->processing_status === 'failed') {
            $image->update([
                'processing_status' => 'pending',
                'processing_error' => null,
            ]);
            
            \App\Jobs\ProcessImageAnalysis::dispatch($image->id)
                ->onQueue('image-processing');
            
            $this->loadStatus();
        }
    }

    public function render()
    {
        return view('livewire.processing-status')
            ->layout('layouts.app');
    }
}

