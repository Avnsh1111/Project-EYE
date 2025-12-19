<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\MediaFile;
use App\Jobs\ProcessImageAnalysis;
use App\Jobs\ProcessBatchImages;
use App\Services\MediaFileService;
use App\Services\MetadataService;
use App\Services\NodeImageProcessorService;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;

class InstantImageUploader extends Component
{
    use WithFileUploads;

    public $files = [];
    public $uploading = false;
    public $uploaded_count = 0;
    public $total_files = 0;
    public $uploaded_files = [];

    // Real-time status tracking
    public $processing_status = [];
    public $upload_statistics = [
        'total_size' => 0,
        'total_count' => 0,
        'upload_start' => null,
        'upload_end' => null,
        'elapsed_time' => 0,
        'success_count' => 0,
        'failed_count' => 0,
    ];

    /**
     * Service instances.
     */
    protected MediaFileService $mediaFileService;
    protected MetadataService $metadataService;
    protected ImageRepository $imageRepository;
    protected NodeImageProcessorService $nodeProcessor;

    /**
     * Boot the component.
     */
    public function boot(
        MediaFileService $mediaFileService,
        MetadataService $metadataService,
        ImageRepository $imageRepository,
        NodeImageProcessorService $nodeProcessor
    ) {
        $this->mediaFileService = $mediaFileService;
        $this->metadataService = $metadataService;
        $this->imageRepository = $imageRepository;
        $this->nodeProcessor = $nodeProcessor;
    }

    protected function rules(): array
    {
        return [
            'files.*' => 'required|file|max:512000', // 500MB max - accept all file types
        ];
    }

    public function updatedFiles()
    {
        $this->validate();
    }

    /**
     * Get real-time processing status for uploaded files.
     * This method is polled by Livewire every 2 seconds.
     */
    public function refreshProcessingStatus()
    {
        if (empty($this->uploaded_files)) {
            return;
        }

        // Extract file IDs from uploaded_files
        $fileIds = array_column($this->uploaded_files, 'id');

        // Fetch current status from database
        $statusUpdates = MediaFile::whereIn('id', $fileIds)
            ->select([
                'id',
                'processing_status',
                'processing_stage',
                'upload_progress',
                'processing_error',
                'processing_started_at',
                'processing_completed_at',
            ])
            ->get()
            ->keyBy('id');

        // Update processing_status array for each file
        foreach ($this->uploaded_files as $index => $file) {
            $fileId = $file['id'];

            if ($statusUpdates->has($fileId)) {
                $status = $statusUpdates[$fileId];

                // Update the file status in uploaded_files array
                $this->uploaded_files[$index]['status'] = $status->processing_status;
                $this->uploaded_files[$index]['processing_stage'] = $status->processing_stage ?? 'pending';
                $this->uploaded_files[$index]['upload_progress'] = $status->upload_progress ?? 0;
                $this->uploaded_files[$index]['error'] = $status->processing_error;
                $this->uploaded_files[$index]['processing_started'] = $status->processing_started_at?->diffForHumans();
                $this->uploaded_files[$index]['processing_completed'] = $status->processing_completed_at?->diffForHumans();

                // Calculate time elapsed for processing
                if ($status->processing_started_at && !$status->processing_completed_at) {
                    $this->uploaded_files[$index]['elapsed'] = $status->processing_started_at->diffInSeconds(now()) . 's';
                } elseif ($status->processing_completed_at && $status->processing_started_at) {
                    $this->uploaded_files[$index]['elapsed'] = $status->processing_started_at->diffInSeconds($status->processing_completed_at) . 's';
                }
            }
        }

        // Update statistics
        $this->updateStatistics();
    }

    /**
     * Update upload statistics based on current file statuses.
     */
    protected function updateStatistics()
    {
        $this->upload_statistics['success_count'] = collect($this->uploaded_files)
            ->where('status', 'completed')
            ->count();

        $this->upload_statistics['failed_count'] = collect($this->uploaded_files)
            ->where('status', 'failed')
            ->count();

        if ($this->upload_statistics['upload_start'] && $this->upload_statistics['upload_end']) {
            $start = \Carbon\Carbon::parse($this->upload_statistics['upload_start']);
            $end = \Carbon\Carbon::parse($this->upload_statistics['upload_end']);
            $this->upload_statistics['elapsed_time'] = $start->diffInSeconds($end);
        }
    }

    /**
     * Retry processing for a failed file.
     */
    public function retryFile($fileId)
    {
        try {
            $mediaFile = MediaFile::find($fileId);

            if (!$mediaFile) {
                $this->addError('retry', 'File not found.');
                return;
            }

            // Reset processing status
            $mediaFile->update([
                'processing_status' => 'pending',
                'processing_error' => null,
                'processing_stage' => null,
            ]);

            // Redispatch job
            ProcessImageAnalysis::dispatch($fileId)
                ->onQueue('image-processing');

            // Update local state
            foreach ($this->uploaded_files as $index => $file) {
                if ($file['id'] == $fileId) {
                    $this->uploaded_files[$index]['status'] = 'pending';
                    $this->uploaded_files[$index]['error'] = null;
                    break;
                }
            }

            $this->dispatch('file-retried', fileId: $fileId);

        } catch (\Exception $e) {
            Log::error("Failed to retry file processing", [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            $this->addError('retry', 'Failed to retry file: ' . $e->getMessage());
        }
    }

    /**
     * Remove a file from the uploaded files list.
     */
    public function removeUploadedFile($fileId)
    {
        $this->uploaded_files = array_filter($this->uploaded_files, function($file) use ($fileId) {
            return $file['id'] !== $fileId;
        });

        // Re-index array
        $this->uploaded_files = array_values($this->uploaded_files);
    }

    /**
     * Upload images instantly and queue for processing
     */
    public function uploadInstantly()
    {
        $this->validate();

        if (empty($this->files)) {
            $this->addError('files', 'Please select at least one file.');
            return;
        }

        $this->uploading = true;
        $this->uploaded_count = 0;
        $this->total_files = count($this->files);
        $this->uploaded_files = [];

        // Initialize statistics
        $this->upload_statistics = [
            'total_size' => 0,
            'total_count' => $this->total_files,
            'upload_start' => now()->toDateTimeString(),
            'upload_end' => null,
            'elapsed_time' => 0,
            'success_count' => 0,
            'failed_count' => 0,
        ];

        // Group uploaded files by media type
        $mediaFilesByType = [
            'image' => [],
            'video' => [],
            'document' => [],
            'audio' => [],
        ];

        $useBatchProcessing = count($this->files) > 1 && $this->nodeProcessor->isAvailable();

        foreach ($this->files as $index => $image) {
            try {
                // Track upload start time
                $uploadStartTime = microtime(true);

                // Use MediaFileService to store the file with proper media type detection
                $fileData = $this->mediaFileService->storeUploadedMedia($image);

                // Use MetadataService to extract quick metadata
                $metadata = $this->metadataService->extractQuickMetadata(
                    $fileData['full_path'],
                    $image
                );

                // Create media file record with correct media_type
                $mediaFile = $this->mediaFileService->createMediaFileRecord($fileData, $image);

                // Update with additional metadata and upload tracking
                $mediaFile->update(array_merge($metadata, [
                    'upload_started_at' => now(),
                    'upload_completed_at' => now(),
                    'upload_progress' => 100,
                ]));

                // Calculate upload time
                $uploadTime = round((microtime(true) - $uploadStartTime) * 1000); // milliseconds

                // Update statistics
                $this->upload_statistics['total_size'] += $image->getSize();

                // Add to uploaded list for UI feedback
                $this->uploaded_files[] = [
                    'id' => $mediaFile->id,
                    'filename' => $metadata['original_filename'],
                    'url' => $this->mediaFileService->getPublicUrl($fileData['path']),
                    'status' => 'pending',
                    'processing_stage' => 'pending',
                    'upload_progress' => 100,
                    'media_type' => $fileData['media_type'],
                    'file_size' => $image->getSize(),
                    'file_size_human' => $this->formatBytes($image->getSize()),
                    'upload_time' => $uploadTime,
                    'error' => null,
                ];

                $this->uploaded_count++;

                // Group by media type for proper routing
                $mediaType = $fileData['media_type'];
                if (isset($mediaFilesByType[$mediaType])) {
                    $mediaFilesByType[$mediaType][] = $mediaFile->id;
                } else {
                    // Unknown media type - process individually
                    $mediaFilesByType['document'][] = $mediaFile->id;
                }

                Log::info("Media file uploaded instantly via services", [
                    'file_id' => $mediaFile->id,
                    'filename' => $metadata['original_filename'],
                    'media_type' => $fileData['media_type'],
                    'upload_time' => $uploadTime . 'ms',
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to upload media file via services", [
                    'filename' => $image->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                $this->addError('upload', "Failed to upload {$image->getClientOriginalName()}: {$e->getMessage()}");

                // Add to uploaded files list with failed status
                $this->uploaded_files[] = [
                    'id' => null,
                    'filename' => $image->getClientOriginalName(),
                    'url' => null,
                    'status' => 'failed',
                    'processing_stage' => 'upload_failed',
                    'media_type' => 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Mark upload end time
        $this->upload_statistics['upload_end'] = now()->toDateTimeString();

        // Dispatch processing jobs based on media type
        $imageIds = $mediaFilesByType['image'];

        // Images: Use batch processing if multiple images and Node.js available
        if (!empty($imageIds)) {
            if (count($imageIds) > 1 && $useBatchProcessing) {
                // Dispatch batch processing job to queue (Node.js handles parallelization)
                Log::info("Dispatching batch processing job for " . count($imageIds) . " images via Node.js (background)");
                ProcessBatchImages::dispatch($imageIds)
                    ->onQueue('image-processing');
            } else {
                // Single image or Node.js unavailable - use individual processing
                foreach ($imageIds as $imageId) {
                    ProcessImageAnalysis::dispatch($imageId)
                        ->onQueue('image-processing');
                }
            }
        }

        // Videos, Documents, Audio: Always use individual processing (Python AI)
        $nonImageTypes = ['video', 'document', 'audio'];
        foreach ($nonImageTypes as $type) {
            if (!empty($mediaFilesByType[$type])) {
                Log::info("Dispatching individual processing for " . count($mediaFilesByType[$type]) . " {$type} files via Python AI");
                foreach ($mediaFilesByType[$type] as $mediaId) {
                    ProcessImageAnalysis::dispatch($mediaId)
                        ->onQueue('image-processing');
                }
            }
        }

        $this->uploading = false;

        // Clear the file input
        $this->files = [];

        // Show success message
        $this->dispatch('upload-complete', count: $this->uploaded_count);
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Clear uploaded images list
     */
    public function clearUploaded()
    {
        $this->uploaded_files = [];
        $this->uploaded_count = 0;
        $this->upload_statistics = [
            'total_size' => 0,
            'total_count' => 0,
            'upload_start' => null,
            'upload_end' => null,
            'elapsed_time' => 0,
            'success_count' => 0,
            'failed_count' => 0,
        ];
    }

    public function render()
    {
        // Refresh status on each render if files are uploaded
        if (!empty($this->uploaded_files)) {
            $this->refreshProcessingStatus();
        }

        return view('livewire.instant-image-uploader');
    }
}
