<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Exception;

class AiService
{
    /**
     * Base URL for the AI service.
     */
    protected string $baseUrl;

    /**
     * Timeout for API requests in seconds.
     */
    protected int $timeout;
    
    /**
     * File service for path conversions.
     */
    protected FileService $fileService;

    /**
     * Create a new AiService instance.
     */
    public function __construct(FileService $fileService)
    {
        $this->baseUrl = config('ai.api_url');
        $this->timeout = config('ai.timeout');
        $this->fileService = $fileService;
    }

    /**
     * Check if the AI service is healthy and ready.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . config('ai.endpoints.health'));
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['models_loaded']) && $data['models_loaded'] === true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('AI service health check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the status of AI models including download progress.
     *
     * @return array
     */
    public function getModelStatus(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/api/model-status');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [
                'status' => 'offline',
                'models' => [],
                'downloading' => []
            ];
        } catch (Exception $e) {
            Log::error('Failed to get model status: ' . $e->getMessage());
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'models' => [],
                'downloading' => []
            ];
        }
    }
    
    /**
     * Trigger model preload for configured models.
     *
     * @return bool
     */
    public function preloadModels(): bool
    {
        try {
            $captioningModel = Setting::get('captioning_model', 'Salesforce/blip-image-captioning-large');
            $embeddingModel = Setting::get('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
            
            $response = Http::timeout(60)->post($this->baseUrl . '/api/preload-models', [
                'captioning_model' => $captioningModel,
                'embedding_model' => $embeddingModel,
            ]);
            
            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to preload models: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Analyze an image and get detailed description and embedding.
     *
     * @param string $imagePath Full path to the image file
     * @return array{description: string, embedding: array}
     * @throws Exception
     */
    public function analyzeImage(string $imagePath): array
    {
        try {
            // Convert Laravel storage path to shared volume path using FileService
            $sharedPath = $this->fileService->convertToSharedPath($imagePath);
            
            // Get model settings
            $captioningModel = Setting::get('captioning_model', 'Salesforce/blip-image-captioning-large');
            $embeddingModel = Setting::get('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
            
            // Handle boolean settings (could be boolean or string)
            $faceDetectionRaw = Setting::get('face_detection_enabled', true);
            $faceDetectionEnabled = is_bool($faceDetectionRaw) ? $faceDetectionRaw : ($faceDetectionRaw === 'true' || $faceDetectionRaw === true);
            
            $ollamaEnabledRaw = Setting::get('ollama_enabled', false);
            $ollamaEnabled = is_bool($ollamaEnabledRaw) ? $ollamaEnabledRaw : ($ollamaEnabledRaw === 'true' || $ollamaEnabledRaw === true);
            
            $ollamaModel = Setting::get('ollama_model', 'llava');
            
            Log::info('Analyzing image via AI service', [
                'original_path' => $imagePath,
                'shared_path' => $sharedPath,
                'captioning_model' => $captioningModel,
                'embedding_model' => $embeddingModel,
                'face_detection' => $faceDetectionEnabled,
                'ollama_enabled' => $ollamaEnabled,
                'ollama_model' => $ollamaModel
            ]);

            $requestData = [
                'image_path' => $sharedPath,
                'captioning_model' => $captioningModel,
                'embedding_model' => $embeddingModel,
                'face_detection_enabled' => $faceDetectionEnabled,
            ];
            
            // Add Ollama settings if enabled
            if ($ollamaEnabled) {
                $requestData['ollama_enabled'] = true;
                $requestData['ollama_model'] = $ollamaModel;
            }

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . config('ai.endpoints.analyze'), $requestData);

            if (!$response->successful()) {
                throw new Exception('AI service returned error: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['description']) || !isset($data['embedding'])) {
                throw new Exception('Invalid response from AI service: missing description or embedding.');
            }

            Log::info('Image analysis completed', [
                'description_length' => strlen($data['description']),
                'detailed_description_length' => isset($data['detailed_description']) ? strlen($data['detailed_description']) : 0,
                'meta_tags_count' => isset($data['meta_tags']) ? count($data['meta_tags']) : 0,
                'face_count' => $data['face_count'] ?? 0,
                'embedding_size' => count($data['embedding'])
            ]);

            return [
                'description' => $data['description'],
                'detailed_description' => $data['detailed_description'] ?? null,
                'meta_tags' => $data['meta_tags'] ?? [],
                'embedding' => $data['embedding'],
                'face_count' => $data['face_count'] ?? 0,
                'face_encodings' => $data['face_encodings'] ?? [],  // Legacy support
                'faces' => $data['faces'] ?? [],  // New: detailed face data with locations
            ];
        } catch (Exception $e) {
            Log::error('Failed to analyze image', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate embedding for text query.
     *
     * @param string $query Text query
     * @return array The embedding vector
     * @throws Exception
     */
    public function embedText(string $query): array
    {
        try {
            // Get embedding model setting
            $embeddingModel = Setting::get('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
            
            Log::info('Generating text embedding', [
                'query' => $query,
                'embedding_model' => $embeddingModel
            ]);

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . config('ai.endpoints.embed_text'), [
                    'query' => $query,
                    'embedding_model' => $embeddingModel,
                ]);

            if (!$response->successful()) {
                throw new Exception('AI service returned error: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['embedding'])) {
                throw new Exception('Invalid response from AI service');
            }

            Log::info('Text embedding generated', [
                'embedding_size' => count($data['embedding']),
                'model_used' => $data['model_used'] ?? $embeddingModel
            ]);

            return $data['embedding'];
        } catch (Exception $e) {
            Log::error('Failed to generate text embedding', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

