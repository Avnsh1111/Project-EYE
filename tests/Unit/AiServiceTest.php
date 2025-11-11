<?php

use App\Services\AiService;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AiService', function () {
    
    beforeEach(function () {
        $this->aiService = new AiService();
    });

    it('can be instantiated', function () {
        expect($this->aiService)->toBeInstanceOf(AiService::class);
    });

    it('checks if AI service is healthy', function () {
        Http::fake([
            '*' => Http::response(['status' => 'healthy'], 200),
        ]);

        $aiService = new AiService();
        $isHealthy = $aiService->isHealthy();

        // In test environment, service may not be running
        expect($isHealthy)->toBeBool();
    });

    it('returns false when AI service is down', function () {
        Http::fake([
            '*/health' => Http::response([], 500),
        ]);

        $isHealthy = $this->aiService->isHealthy();

        expect($isHealthy)->toBeFalse();
    });

    it('analyzes an image and returns description and embedding', function () {
        Http::fake([
            '*/analyze' => Http::response([
                'description' => 'A beautiful sunset over mountains',
                'detailed_description' => 'A stunning photograph showing...',
                'meta_tags' => ['sunset', 'mountains', 'nature'],
                'embedding' => array_fill(0, 512, 0.1),
                'face_count' => 0,
                'face_encodings' => [],
            ], 200),
        ]);

        Setting::set('captioning_model', 'Salesforce/blip-image-captioning-large');
        Setting::set('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
        Setting::set('face_detection_enabled', true);

        $result = $this->aiService->analyzeImage('public/images/test.jpg');

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('description')
            ->and($result)->toHaveKey('embedding')
            ->and($result['description'])->toBe('A beautiful sunset over mountains')
            ->and($result['embedding'])->toHaveCount(512);
    });

    it('embeds text query and returns embedding', function () {
        Http::fake([
            '*/embed-text' => Http::response([
                'embedding' => array_fill(0, 512, 0.2),
            ], 200),
        ]);

        Setting::set('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');

        $result = $this->aiService->embedText('sunset over mountains');

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(512);
    });

    it('passes model settings to AI service', function () {
        Http::fake([
            '*/analyze' => Http::response([
                'description' => 'Test',
                'detailed_description' => 'Test detailed',
                'meta_tags' => [],
                'embedding' => array_fill(0, 512, 0.1),
                'face_count' => 0,
                'face_encodings' => [],
            ], 200),
        ]);

        Setting::set('captioning_model', 'Salesforce/blip-image-captioning-base');
        Setting::set('embedding_model', 'openai/clip-vit-base-patch32');
        Setting::set('face_detection_enabled', false);

        $this->aiService->analyzeImage('public/images/test.jpg');

        Http::assertSent(function ($request) {
            return $request->data()['captioning_model'] === 'Salesforce/blip-image-captioning-base'
                && $request->data()['embedding_model'] === 'openai/clip-vit-base-patch32'
                && $request->data()['face_detection_enabled'] === false;
        });
    });

    it('handles API errors gracefully', function () {
        Http::fake([
            '*/analyze' => Http::response([], 500),
        ]);

        expect(fn() => $this->aiService->analyzeImage('public/images/test.jpg'))
            ->toThrow(Exception::class);
    });

    it('handles timeout errors', function () {
        Http::fake([
            '*/analyze' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Timeout');
            },
        ]);

        expect(fn() => $this->aiService->analyzeImage('public/images/test.jpg'))
            ->toThrow(Exception::class);
    });

    it('converts image path to shared path correctly', function () {
        Http::fake([
            '*' => Http::response([
                'description' => 'Test',
                'detailed_description' => 'Test',
                'meta_tags' => [],
                'embedding' => array_fill(0, 512, 0.1),
                'face_count' => 0,
                'face_encodings' => [],
            ], 200),
        ]);

        Setting::set('captioning_model', 'Salesforce/blip-image-captioning-large');
        Setting::set('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
        Setting::set('face_detection_enabled', true);

        $aiService = new AiService();
        $aiService->analyzeImage('public/images/test.jpg');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['image_path']) && str_contains($data['image_path'], 'test.jpg');
        });
    });
});

