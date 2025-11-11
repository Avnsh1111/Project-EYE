<?php

namespace App\Jobs;

use App\Models\ImageFile;
use App\Services\AiService;
use App\Services\MetadataService;
use App\Services\FileService;
use App\Services\SystemMonitorService;
use App\Services\FaceClusteringService;
use App\Repositories\ImageRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImageAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $imageFileId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AiService $aiService,
        MetadataService $metadataService,
        FileService $fileService,
        ImageRepository $imageRepository
    ): void {
        // Record queue worker activity for monitoring
        SystemMonitorService::recordQueueActivity();
        
        $imageFile = $imageRepository->findById($this->imageFileId);
        
        if (!$imageFile) {
            Log::warning("Image file not found: {$this->imageFileId}");
            return;
        }

        try {
            Log::info("Starting deep analysis via services for image: {$imageFile->id}");
            
            // Update status to processing using repository
            $imageRepository->update($imageFile->id, [
                'processing_status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Extract comprehensive metadata using MetadataService
            $fullPath = $fileService->getFullPath($imageFile->file_path);
            $metadata = $metadataService->extractComprehensiveMetadata($fullPath);

            // Perform deep AI analysis
            $analysis = $aiService->analyzeImage($imageFile->file_path);

            // Merge metadata and analysis results, then update
            $updateData = array_merge($metadata, [
                'description' => $this->sanitizeForPostgres($analysis['description']),
                'detailed_description' => isset($analysis['detailed_description']) ? $this->sanitizeForPostgres($analysis['detailed_description']) : null,
                'meta_tags' => $analysis['meta_tags'] ?? [],
                'embedding' => $analysis['embedding'],
                'face_count' => $analysis['face_count'] ?? 0,
                'face_encodings' => $analysis['face_encodings'] ?? [],
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
                'processing_error' => null,
            ]);

            $imageRepository->update($imageFile->id, $updateData);

            // Process and cluster detected faces
            Log::info("Face detection check for image {$imageFile->id}", [
                'faces_array_exists' => isset($analysis['faces']),
                'faces_count' => isset($analysis['faces']) ? count($analysis['faces']) : 0,
                'face_count' => $analysis['face_count'] ?? 0
            ]);
            
            if (!empty($analysis['faces'])) {
                try {
                    $faceClusteringService = app(FaceClusteringService::class);
                    $faceClusteringService->processFaces($imageFile, $analysis['faces']);
                    Log::info("Clustered {$analysis['face_count']} face(s) for image: {$imageFile->id}");
                } catch (\Exception $e) {
                    Log::error("Face clustering failed for image {$imageFile->id}: {$e->getMessage()}");
                }
            } else {
                Log::info("No faces to cluster for image {$imageFile->id}");
            }

            Log::info("Deep analysis completed via services for image: {$imageFile->id}");

            // Dispatch event for real-time updates
            $updatedImage = $imageRepository->findById($imageFile->id);
            event(new \App\Events\ImageProcessed($updatedImage));

        } catch (\Exception $e) {
            Log::error("Failed to analyze image via services {$imageFile->id}: {$e->getMessage()}");
            
            $imageRepository->update($imageFile->id, [
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
                'processing_completed_at' => now(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $imageRepository = app(ImageRepository::class);
        $imageFile = $imageRepository->findById($this->imageFileId);
        
        if ($imageFile) {
            $imageRepository->update($imageFile->id, [
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage(),
                'processing_completed_at' => now(),
            ]);
        }

        Log::error("Image analysis job failed permanently for image {$this->imageFileId}: {$exception->getMessage()}");
    }

    /**
     * Sanitize string for PostgreSQL to avoid Unicode escape sequence errors.
     *
     * @param string|null $text
     * @return string|null
     */
    private function sanitizeForPostgres(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Remove NULL bytes and other problematic characters
        $text = str_replace("\0", '', $text);
        
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove any remaining control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        
        return $text;
    }
}

