<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use Illuminate\Support\Facades\Storage;

class ImageGallery extends Component
{
    public $images = [];
    public $selectedImage = null;
    public $filterTag = '';
    
    public function mount()
    {
        $this->loadImages();
    }
    
    public function loadImages()
    {
        $query = ImageFile::orderBy('created_at', 'desc');
        
        if ($this->filterTag) {
            $query->whereJsonContains('meta_tags', $this->filterTag);
        }
        
        $this->images = $query->get()->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => asset('storage/' . str_replace('public/', '', $image->file_path)),
                'description' => $image->description,
                'detailed_description' => $image->detailed_description ?? $image->description,
                'meta_tags' => $image->meta_tags ?? [],
                'face_count' => $image->face_count ?? 0,
                'filename' => $image->original_filename ?? basename($image->file_path),
                'created_at' => $image->created_at->format('M d, Y'),
                // File metadata
                'mime_type' => $image->mime_type,
                'file_size' => $image->file_size ? $this->formatFileSize($image->file_size) : null,
                'dimensions' => $image->width && $image->height ? "{$image->width} × {$image->height}" : null,
                'width' => $image->width,
                'height' => $image->height,
                // Camera info
                'camera_make' => $image->camera_make,
                'camera_model' => $image->camera_model,
                'lens_model' => $image->lens_model,
                'date_taken' => $image->date_taken ? $image->date_taken->format('M d, Y g:i A') : null,
                // Exposure settings
                'exposure_time' => $image->exposure_time,
                'f_number' => $image->f_number,
                'iso' => $image->iso ? 'ISO ' . $image->iso : null,
                'focal_length' => $image->focal_length ? $image->focal_length . 'mm' : null,
                // GPS
                'gps_latitude' => $image->gps_latitude,
                'gps_longitude' => $image->gps_longitude,
                'has_gps' => $image->gps_latitude && $image->gps_longitude,
            ];
        })->toArray();
    }
    
    /**
     * Format file size in human-readable format.
     */
    protected function formatFileSize($bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
    
    public function viewDetails($imageId)
    {
        $this->selectedImage = collect($this->images)->firstWhere('id', $imageId);
    }
    
    public function closeDetails()
    {
        $this->selectedImage = null;
    }
    
    public function filterByTag($tag)
    {
        $this->filterTag = $tag;
        $this->loadImages();
    }
    
    public function clearFilter()
    {
        $this->filterTag = '';
        $this->loadImages();
    }
    
    public function render()
    {
        return view('livewire.image-gallery')
            ->layout('layouts.app');
    }
}

