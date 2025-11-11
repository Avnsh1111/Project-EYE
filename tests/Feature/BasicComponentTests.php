<?php

use App\Livewire\ImageUploader;
use App\Livewire\ImageSearch;
use App\Livewire\EnhancedImageGallery;
use App\Livewire\Settings;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Basic Component Tests', function () {
    
    it('ImageUploader renders successfully', function () {
        Livewire::test(ImageUploader::class)
            ->assertStatus(200)
            ->assertSee('Upload');
    });

    it('ImageSearch renders successfully', function () {
        Livewire::test(ImageSearch::class)
            ->assertStatus(200)
            ->assertSee('Search');
    });

    it('EnhancedImageGallery renders successfully', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->assertStatus(200);
    });

    it('Settings renders successfully', function () {
        Livewire::test(Settings::class)
            ->assertStatus(200);
    });
});

