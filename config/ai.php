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
    
    'timeout' => env('AI_TIMEOUT', 120),
    
    'endpoints' => [
        'analyze' => '/analyze',
        'embed_text' => '/embed-text',
        'health' => '/health',
    ],
    
    'embedding_dimension' => 512, // CLIP ViT-B/32 embedding dimension

];

