<?php

use App\Livewire\Settings;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Settings Component', function () {
    
    beforeEach(function () {
        Http::fake([
            '*/health' => Http::response(['status' => 'healthy'], 200),
        ]);
    });

    it('renders successfully', function () {
        Livewire::test(Settings::class)
            ->assertStatus(200);
    });

    it('loads default settings on mount', function () {
        // Ensure settings exist
        Setting::set('captioning_model', 'Salesforce/blip-image-captioning-large');
        Setting::set('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
        
        $component = Livewire::test(Settings::class);

        expect($component->captioning_model)->not->toBeNull()
            ->and($component->embedding_model)->not->toBeNull();
    });

    it('can save settings', function () {
        Livewire::test(Settings::class)
            ->set('captioning_model', 'Salesforce/blip-image-captioning-base')
            ->set('embedding_model', 'openai/clip-vit-base-patch32')
            ->set('face_detection_enabled', false)
            ->call('save')
            ->assertSet('saved', true);

        // Verify settings were saved
        $captioning = Setting::get('captioning_model');
        $embedding = Setting::get('embedding_model');
        
        expect($captioning)->toBe('Salesforce/blip-image-captioning-base')
            ->and($embedding)->toBe('openai/clip-vit-base-patch32');
    });

    it('checks AI service status', function () {
        Livewire::test(Settings::class)
            ->call('checkAiServiceStatus')
            ->assertSet('ai_service_status', function ($status) {
                return $status !== null;
            });
    });

    it('can toggle Ollama integration', function () {
        Livewire::test(Settings::class)
            ->set('ollama_enabled', true)
            ->assertSet('ollama_enabled', true)
            ->set('ollama_enabled', false)
            ->assertSet('ollama_enabled', false);
    });

    it('provides list of captioning models', function () {
        $component = Livewire::test(Settings::class);

        expect($component->available_captioning_models)->toBeArray()
            ->and($component->available_captioning_models)->not->toBeEmpty();
    });

    it('provides list of embedding models', function () {
        $component = Livewire::test(Settings::class);

        expect($component->available_embedding_models)->toBeArray()
            ->and($component->available_embedding_models)->not->toBeEmpty();
    });

    it('provides list of Ollama models', function () {
        $component = Livewire::test(Settings::class);

        expect($component->available_ollama_models)->toBeArray()
            ->and($component->available_ollama_models)->not->toBeEmpty();
    });

    it('displays success message after saving', function () {
        Livewire::test(Settings::class)
            ->call('save')
            ->assertSet('saved', true);
    });
});
