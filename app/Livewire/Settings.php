<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use App\Services\AiService;

class Settings extends Component
{
    // Captioning Models
    public $captioning_model;
    public $available_captioning_models = [
        'Salesforce/blip-image-captioning-large' => 'BLIP Large (Default, Best Quality)',
        'Salesforce/blip-image-captioning-base' => 'BLIP Base (Faster, Good Quality)',
        'Salesforce/blip2-opt-2.7b' => 'BLIP-2 (Advanced, Requires More Memory)',
        'nlpconnect/vit-gpt2-image-captioning' => 'ViT-GPT2 (Fast, Creative Captions)',
    ];

    // Embedding Models
    public $embedding_model;
    public $available_embedding_models = [
        'laion/CLIP-ViT-B-32-laion2B-s34B-b79K' => 'CLIP ViT-B/32 (Default, Best for Search)',
        'openai/clip-vit-large-patch14' => 'CLIP ViT-L/14 (Higher Quality, Slower)',
        'openai/clip-vit-base-patch32' => 'CLIP ViT-B/32 OpenAI (Fast, Good Quality)',
        'facebook/dinov2-base' => 'DINOv2 Base (Self-Supervised, No Text)',
    ];

    // Face Detection
    public $face_detection_enabled;

    // Ollama
    public $ollama_enabled;
    public $ollama_model;
    public $available_ollama_models = [
        'llama2' => 'Llama 2 (Default)',
        'llama2:13b' => 'Llama 2 13B (Better Quality)',
        'mistral' => 'Mistral 7B (Fast, Efficient)',
        'mixtral' => 'Mixtral 8x7B (Highest Quality)',
        'codellama' => 'Code Llama (Technical Descriptions)',
        'llava' => 'LLaVA (Vision-Language Model)',
    ];

    // Status
    public $saved = false;
    public $error = null;
    public $ai_service_status = null;
    public $model_status = [];
    public $preloading = false;

    public function mount()
    {
        $this->loadSettings();
        $this->checkAiServiceStatus();
        $this->loadModelStatus();
    }

    public function loadSettings()
    {
        $this->captioning_model = Setting::get('captioning_model', 'Salesforce/blip-image-captioning-large');
        $this->embedding_model = Setting::get('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
        
        // Load boolean settings and ensure they are actual booleans
        $faceDetection = Setting::get('face_detection_enabled', true);
        $this->face_detection_enabled = is_bool($faceDetection) ? $faceDetection : ($faceDetection === 'true' || $faceDetection === true);
        
        $ollamaEnabled = Setting::get('ollama_enabled', false);
        $this->ollama_enabled = is_bool($ollamaEnabled) ? $ollamaEnabled : ($ollamaEnabled === 'true' || $ollamaEnabled === true);
        
        $this->ollama_model = Setting::get('ollama_model', 'llava');
    }

    public function checkAiServiceStatus()
    {
        try {
            $aiService = app(AiService::class);
            $this->ai_service_status = $aiService->isHealthy() ? 'online' : 'offline';
        } catch (\Exception $e) {
            $this->ai_service_status = 'error';
            $this->error = $e->getMessage();
        }
    }

    public function save()
    {
        try {
            // Save all settings - the Setting model handles JSON encoding
            Setting::set('captioning_model', $this->captioning_model);
            Setting::set('embedding_model', $this->embedding_model);
            Setting::set('face_detection_enabled', $this->face_detection_enabled); // Save as boolean
            Setting::set('ollama_enabled', $this->ollama_enabled); // Save as boolean
            Setting::set('ollama_model', $this->ollama_model);

            $this->saved = true;
            $this->error = null;

            // Reset saved message after 3 seconds
            $this->dispatch('setting-saved');
        } catch (\Exception $e) {
            $this->error = 'Failed to save settings: ' . $e->getMessage();
            $this->saved = false;
        }
    }

    public function loadModelStatus()
    {
        try {
            $aiService = app(AiService::class);
            $this->model_status = $aiService->getModelStatus();
        } catch (\Exception $e) {
            $this->model_status = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    public function testConnection()
    {
        $this->checkAiServiceStatus();
        $this->loadModelStatus();
    }
    
    public function preloadModels()
    {
        $this->preloading = true;
        
        try {
            $aiService = app(AiService::class);
            $success = $aiService->preloadModels();
            
            if ($success) {
                $this->saved = true;
                $this->error = null;
                $this->dispatch('models-preloaded');
            } else {
                $this->error = 'Failed to preload models. Check if AI service is running.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error preloading models: ' . $e->getMessage();
        }
        
        $this->preloading = false;
        $this->loadModelStatus();
    }

    public function render()
    {
        return view('livewire.settings')
            ->layout('layouts.app');
    }
}

