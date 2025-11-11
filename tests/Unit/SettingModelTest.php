<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Setting Model', function () {
    
    it('can create a setting', function () {
        $setting = Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
        ]);

        expect($setting)->toBeInstanceOf(Setting::class)
            ->and($setting->key)->toBe('test_setting')
            ->and($setting->value)->toBe('test_value');
    });

    it('can get a setting value', function () {
        Setting::create([
            'key' => 'app_name',
            'value' => 'Avinash-EYE',
        ]);

        $value = Setting::get('app_name');

        expect($value)->toBe('Avinash-EYE');
    });

    it('returns default value when setting does not exist', function () {
        $value = Setting::get('non_existent', 'default_value');

        expect($value)->toBe('default_value');
    });

    it('can set a setting value', function () {
        Setting::set('theme', 'dark');

        $setting = Setting::find('theme');

        expect($setting->value)->toBe('dark');
    });

    it('updates existing setting when using set', function () {
        Setting::create([
            'key' => 'theme',
            'value' => 'light',
        ]);

        Setting::set('theme', 'dark');

        expect(Setting::get('theme'))->toBe('dark');
    });

    it('casts value as json', function () {
        Setting::set('config', ['key1' => 'value1', 'key2' => 'value2']);

        $value = Setting::get('config');

        expect($value)->toBeArray()
            ->and($value['key1'])->toBe('value1');
    });

    it('caches setting values', function () {
        Setting::set('cached_setting', 'cached_value');
        
        // Get the setting (should cache it)
        $value1 = Setting::get('cached_setting');
        
        // Change in database directly
        Setting::where('key', 'cached_setting')->update(['value' => json_encode('new_value')]);
        
        // Should still return cached value
        $value2 = Setting::get('cached_setting');

        expect($value1)->toBe('cached_value')
            ->and($value2)->toBe('cached_value'); // Still cached
    });

    it('clears cache when setting is updated', function () {
        Setting::set('cache_test', 'old_value');
        Setting::get('cache_test'); // Cache it
        
        Setting::set('cache_test', 'new_value');
        
        $value = Setting::get('cache_test');

        expect($value)->toBe('new_value');
    });

    it('stores AI model settings', function () {
        Setting::set('captioning_model', 'Salesforce/blip-image-captioning-large');
        Setting::set('embedding_model', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K');
        Setting::set('face_detection_enabled', true);

        expect(Setting::get('captioning_model'))->toBe('Salesforce/blip-image-captioning-large')
            ->and(Setting::get('embedding_model'))->toBe('laion/CLIP-ViT-B-32-laion2B-s34B-b79K')
            ->and(Setting::get('face_detection_enabled'))->toBeTrue();
    });

    it('handles boolean values', function () {
        Setting::set('feature_enabled', true);
        Setting::set('feature_disabled', false);

        expect(Setting::get('feature_enabled'))->toBeTrue()
            ->and(Setting::get('feature_disabled'))->toBeFalse();
    });

    it('handles array values', function () {
        Setting::set('options', ['option1', 'option2', 'option3']);

        $value = Setting::get('options');

        expect($value)->toBeArray()
            ->and($value)->toHaveCount(3)
            ->and($value)->toContain('option2');
    });
});

