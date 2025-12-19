<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Python FastAPI AI service
    | that handles image analysis and embedding generation.
    |
    */

    'api_url' => env('AI_API_URL', 'http://python-ai:8000'),

    'timeout' => env('AI_TIMEOUT', 120), // Legacy fallback

    /**
     * Adaptive timeout configuration by operation type.
     * Different operations have different processing times:
     * - Image captioning: 5-30s
     * - Video analysis: 30-120s
     * - Document OCR: 10-60s
     * - Audio transcription: 30-120s
     * - Ollama analysis: 60-240s (significantly longer)
     */
    'timeouts' => [
        'default' => env('AI_DEFAULT_TIMEOUT', 120),

        'image' => [
            'standard' => env('AI_IMAGE_TIMEOUT', 30),
            'ollama' => env('AI_IMAGE_OLLAMA_TIMEOUT', 180),
        ],

        'video' => [
            'standard' => env('AI_VIDEO_TIMEOUT', 120),
            'ollama' => env('AI_VIDEO_OLLAMA_TIMEOUT', 240),
        ],

        'document' => [
            'standard' => env('AI_DOCUMENT_TIMEOUT', 60),
            'ollama' => env('AI_DOCUMENT_OLLAMA_TIMEOUT', 180),
        ],

        'audio' => [
            'standard' => env('AI_AUDIO_TIMEOUT', 120),
            'ollama' => env('AI_AUDIO_OLLAMA_TIMEOUT', 180),
        ],

        'embedding' => env('AI_EMBEDDING_TIMEOUT', 30),
        'health' => env('AI_HEALTH_TIMEOUT', 10),
        'model_status' => env('AI_MODEL_STATUS_TIMEOUT', 10),
        'preload' => env('AI_PRELOAD_TIMEOUT', 60),
    ],

    'endpoints' => [
        'analyze' => '/analyze-image',
        'analyze_video' => '/analyze-video',
        'analyze_document' => '/analyze-document',
        'transcribe_audio' => '/transcribe-audio',
        'embed_text' => '/embed-text',
        'health' => '/health',
    ],

    'embedding_dimension' => 512, // CLIP ViT-B/32 embedding dimension

    /**
     * Circuit breaker configuration for AI service resilience.
     * Prevents cascading failures when AI service is unavailable.
     */
    'circuit_breaker' => [
        // Number of consecutive failures before opening circuit
        // Increased to 10 for better tolerance during model loading
        'failure_threshold' => env('AI_CIRCUIT_BREAKER_THRESHOLD', 10),

        // Seconds to wait before attempting recovery (30 = 30 seconds)
        // Reduced for faster recovery
        'recovery_timeout' => env('AI_CIRCUIT_BREAKER_RECOVERY', 30),
    ],

    /**
     * Retry logic configuration with exponential backoff.
     * Handles transient failures with progressively increasing delays.
     */
    'retry' => [
        // Maximum number of retry attempts for transient failures
        'max_attempts' => env('AI_RETRY_MAX_ATTEMPTS', 3),

        // Initial delay in milliseconds before first retry
        'initial_delay_ms' => env('AI_RETRY_INITIAL_DELAY', 100),

        // Maximum delay in milliseconds between retries (10 seconds)
        'max_delay_ms' => env('AI_RETRY_MAX_DELAY', 10000),

        // Exponential backoff multiplier (2.0 = double delay each attempt)
        'multiplier' => env('AI_RETRY_MULTIPLIER', 2.0),

        // Add random jitter to prevent thundering herd problem
        'use_jitter' => env('AI_RETRY_USE_JITTER', true),
    ],

];

