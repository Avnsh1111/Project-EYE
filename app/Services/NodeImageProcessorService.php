<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NodeImageProcessorService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.node_processor.url', env('NODE_PROCESSOR_URL', 'http://node-processor:3000'));
        $this->timeout = (int) env('NODE_PROCESSOR_TIMEOUT', 600); // 10 minutes for batch
    }

    /**
     * Process a single image via Node.js processor
     *
     * @param string $imagePath Full path to the image file
     * @return array|null
     */
    public function processImage(string $imagePath): ?array
    {
        try {
            if (!file_exists($imagePath)) {
                Log::error("Image file not found: {$imagePath}");
                return null;
            }

            $response = Http::timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->baseUrl}/process");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Node processor failed for {$imagePath}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Error calling Node processor: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process multiple images in parallel via Node.js processor
     *
     * @param array $imagePaths Array of full paths to image files
     * @return array
     */
    public function processBatch(array $imagePaths): array
    {
        try {
            if (empty($imagePaths)) {
                return [];
            }

            // Prepare multipart form data with original filenames
            $multipart = [];
            foreach ($imagePaths as $index => $imagePath) {
                if (!file_exists($imagePath)) {
                    Log::warning("Image file not found: {$imagePath}");
                    continue;
                }

                // Try to get original filename from path (Laravel storage path format)
                $filename = basename($imagePath);
                
                $multipart[] = [
                    'name' => 'images[]',
                    'contents' => file_get_contents($imagePath),
                    'filename' => $filename,
                ];
            }

            if (empty($multipart)) {
                return [];
            }

            $response = Http::timeout($this->timeout)
                ->asMultipart()
                ->post("{$this->baseUrl}/process/batch", $multipart);

            if ($response->successful()) {
                $data = $response->json();
                return $data['results'] ?? [];
            }

            Log::error("Node processor batch failed: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error calling Node processor batch: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract metadata only (fast, no AI processing)
     *
     * @param string $imagePath Full path to the image file
     * @return array|null
     */
    public function extractMetadata(string $imagePath): ?array
    {
        try {
            if (!file_exists($imagePath)) {
                Log::error("Image file not found: {$imagePath}");
                return null;
            }

            $response = Http::timeout(30)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->baseUrl}/metadata");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Node processor metadata extraction failed for {$imagePath}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Error calling Node processor metadata: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract metadata for multiple images
     *
     * @param array $imagePaths Array of full paths to image files
     * @return array
     */
    public function extractMetadataBatch(array $imagePaths): array
    {
        try {
            if (empty($imagePaths)) {
                return [];
            }

            $multipart = [];
            foreach ($imagePaths as $imagePath) {
                if (!file_exists($imagePath)) {
                    continue;
                }

                $multipart[] = [
                    'name' => 'images[]',
                    'contents' => file_get_contents($imagePath),
                    'filename' => basename($imagePath),
                ];
            }

            if (empty($multipart)) {
                return [];
            }

            $response = Http::timeout(60)
                ->asMultipart()
                ->post("{$this->baseUrl}/metadata/batch", $multipart);

            if ($response->successful()) {
                $data = $response->json();
                return $data['results'] ?? [];
            }

            Log::error("Node processor metadata batch failed: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error calling Node processor metadata batch: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if Node.js processor is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get processor status
     *
     * @return array|null
     */
    public function getStatus(): ?array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            if ($response->successful()) {
                return $response->json();
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

