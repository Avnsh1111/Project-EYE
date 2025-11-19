<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageFile;
use App\Jobs\ProcessImageAnalysis;
use App\Jobs\ProcessBatchImages;
use App\Services\FileService;
use App\Services\MetadataService;
use App\Services\NodeImageProcessorService;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;

class InstantImageUploader extends Component
{
    use WithFileUploads;

    public $images = [];
    public $uploading = false;
    public $uploaded_count = 0;
    public $total_files = 0;
    public $uploaded_images = [];
    
    /**
     * Service instances.
     */
    protected FileService $fileService;
    protected MetadataService $metadataService;
    protected ImageRepository $imageRepository;
    protected NodeImageProcessorService $nodeProcessor;
    
    /**
     * Boot the component.
     */
    public function boot(
        FileService $fileService,
        MetadataService $metadataService,
        ImageRepository $imageRepository,
        NodeImageProcessorService $nodeProcessor
    ) {
        $this->fileService = $fileService;
        $this->metadataService = $metadataService;
        $this->imageRepository = $imageRepository;
        $this->nodeProcessor = $nodeProcessor;
    }

    protected function rules(): array
    {
        return [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ];
    }

    public function updatedImages()
    {
        $this->validate();
    }

    /**
     * Upload images instantly and queue for processing
     */
    public function uploadInstantly()
    {
        $this->validate();

        if (empty($this->images)) {
            $this->addError('images', 'Please select at least one image.');
            return;
        }

        $this->uploading = true;
        $this->uploaded_count = 0;
        $this->total_files = count($this->images);
        $this->uploaded_images = [];

        $imageFileIds = [];
        $useBatchProcessing = count($this->images) > 1 && $this->nodeProcessor->isAvailable();

        foreach ($this->images as $index => $image) {
            try {
                // Use FileService to store the image
                $fileData = $this->fileService->storeUploadedImage($image);
                
                // Use MetadataService to extract quick metadata
                $metadata = $this->metadataService->extractQuickMetadata(
                    $fileData['full_path'],
                    $image
                );

                // Use Repository to create database record with "pending" status
                $imageFile = $this->imageRepository->create(array_merge($metadata, [
                    'file_path' => $fileData['path'],
                    'description' => 'Processing...', // Placeholder
                    'processing_status' => 'pending',
                ]));

                // Add to uploaded list for UI feedback
                $this->uploaded_images[] = [
                    'id' => $imageFile->id,
                    'filename' => $metadata['original_filename'],
                    'url' => $this->fileService->getPublicUrl($fileData['path']),
                    'status' => 'pending',
                ];

                $this->uploaded_count++;
                $imageFileIds[] = $imageFile->id;

                Log::info("Image uploaded instantly via services", [
                    'image_id' => $imageFile->id,
                    'filename' => $metadata['original_filename'],
                    'batch_mode' => $useBatchProcessing
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to upload image via services", [
                    'filename' => $image->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                $this->addError('upload', "Failed to upload {$image->getClientOriginalName()}: {$e->getMessage()}");
            }
        }

        // Dispatch processing jobs (all in background via queue)
        if (!empty($imageFileIds)) {
            if ($useBatchProcessing) {
                // Dispatch batch processing job to queue (Node.js handles parallelization in background)
                Log::info("Dispatching batch processing job for " . count($imageFileIds) . " images via Node.js (background)");
                ProcessBatchImages::dispatch($imageFileIds)
                    ->onQueue('image-processing');
            } else {
                // Use individual processing (fallback or single image) - use queue
                foreach ($imageFileIds as $imageId) {
                    ProcessImageAnalysis::dispatch($imageId)
                        ->onQueue('image-processing');
                }
            }
        }

        $this->uploading = false;
        
        // Clear the file input
        $this->images = [];

        // Show success message
        $this->dispatch('upload-complete', count: $this->uploaded_count);
    }

    /**
     * Clear uploaded images list
     */
    public function clearUploaded()
    {
        $this->uploaded_images = [];
        $this->uploaded_count = 0;
    }

    public function render()
    {
        return view('livewire.instant-image-uploader');
    }
}

