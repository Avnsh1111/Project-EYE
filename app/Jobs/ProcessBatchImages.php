<?php

namespace App\Jobs;

use App\Models\ImageFile;
use App\Services\NodeImageProcessorService;
use App\Services\FileService;
use App\Repositories\ImageRepository;
use App\Services\FaceClusteringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBatchImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for batch
    public $tries = 2; // Retry 2 times on failure

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $imageFileIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        NodeImageProcessorService $nodeProcessor,
        FileService $fileService,
        ImageRepository $imageRepository
    ): void {
        if (empty($this->imageFileIds)) {
            Log::warning("ProcessBatchImages: No image IDs provided");
            return;
        }

        // Check if Node.js processor is available
        if (!$nodeProcessor->isAvailable()) {
            Log::warning("Node.js processor not available, falling back to individual processing");
            // Fallback to individual processing
            foreach ($this->imageFileIds as $imageId) {
                ProcessImageAnalysis::dispatch($imageId);
            }
            return;
        }

        try {
            Log::info("Starting batch processing for " . count($this->imageFileIds) . " images via Node.js");

            // Get all image files
            $imageFiles = [];
            $imagePaths = [];
            
            foreach ($this->imageFileIds as $imageId) {
                $imageFile = $imageRepository->findById($imageId);
                if (!$imageFile) {
                    Log::warning("Image file not found: {$imageId}");
                    continue;
                }

                $fullPath = $fileService->getFullPath($imageFile->file_path);
                if (!file_exists($fullPath)) {
                    Log::warning("Image file path not found: {$fullPath}");
                    continue;
                }

                $imageFiles[$imageId] = $imageFile;
                $imagePaths[] = $fullPath;

                // Update status to processing
                $imageRepository->update($imageId, [
                    'processing_status' => 'processing',
                    'processing_started_at' => now(),
                ]);
            }

            if (empty($imagePaths)) {
                Log::warning("No valid image paths found for batch processing");
                return;
            }

            // Create filename to image ID mapping for result processing
            $filenameToImageId = [];
            foreach ($imageFiles as $imageId => $imageFile) {
                $filename = basename($imageFile->file_path);
                $filenameToImageId[$filename] = $imageId;
            }

            // Process batch via Node.js
            $results = $nodeProcessor->processBatch($imagePaths);

            // Process results - map by filename
            foreach ($results as $result) {
                if (!isset($result['success']) || !$result['success']) {
                    Log::warning("Batch processing result failed", ['result' => $result]);
                    continue;
                }

                $filename = $result['filename'] ?? null;
                if (!$filename || !isset($filenameToImageId[$filename])) {
                    Log::warning("Could not map result to image", [
                        'filename' => $filename,
                        'available_filenames' => array_keys($filenameToImageId)
                    ]);
                    continue;
                }

                $imageId = $filenameToImageId[$filename];
                $imageFile = $imageFiles[$imageId];

                try {
                    // Extract data from Node.js result
                    $metadata = $result['metadata'] ?? [];
                    $aiAnalysis = $result['ai_analysis'] ?? null;

                    $updateData = array_merge($metadata, [
                        'description' => $this->sanitizeForPostgres($aiAnalysis['description'] ?? $metadata['description'] ?? null),
                        'detailed_description' => $this->sanitizeForPostgres($aiAnalysis['detailed_description'] ?? null),
                        'meta_tags' => $aiAnalysis['meta_tags'] ?? [],
                        'embedding' => $aiAnalysis['embedding'] ?? null,
                        'face_count' => $aiAnalysis['face_count'] ?? 0,
                        'face_encodings' => $aiAnalysis['face_encodings'] ?? [],
                        'processing_status' => 'completed',
                        'processing_completed_at' => now(),
                        'processing_error' => null,
                    ]);

                    $imageRepository->update($imageId, $updateData);

                    // Process faces if detected
                    if (!empty($aiAnalysis['faces'])) {
                        try {
                            $faceClusteringService = app(FaceClusteringService::class);
                            $faceClusteringService->processFaces($imageFile, $aiAnalysis['faces']);
                        } catch (\Exception $e) {
                            Log::error("Face clustering failed for image {$imageId}: {$e->getMessage()}");
                        }
                    }

                    Log::info("Batch processed image: {$imageId}");
                } catch (\Exception $e) {
                    Log::error("Error processing batch result for image {$imageId}: {$e->getMessage()}");
                    $imageRepository->update($imageId, [
                        'processing_status' => 'failed',
                        'processing_error' => $e->getMessage(),
                        'processing_completed_at' => now(),
                    ]);
                }
            }

            Log::info("Batch processing completed for " . count($results) . " images");

        } catch (\Exception $e) {
            Log::error("Batch processing failed: {$e->getMessage()}");
            
            // Mark all as failed
            foreach ($this->imageFileIds as $imageId) {
                $imageRepository->update($imageId, [
                    'processing_status' => 'failed',
                    'processing_error' => $e->getMessage(),
                    'processing_completed_at' => now(),
                ]);
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $imageRepository = app(ImageRepository::class);
        
        foreach ($this->imageFileIds as $imageId) {
            $imageRepository->update($imageId, [
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage(),
                'processing_completed_at' => now(),
            ]);
        }

        Log::error("Batch image processing job failed permanently: {$exception->getMessage()}");
    }

    /**
     * Sanitize string for PostgreSQL.
     */
    private function sanitizeForPostgres(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = str_replace("\0", '', $text);
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        
        return $text;
    }
}
