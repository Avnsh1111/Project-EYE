<?php

use App\Livewire\EnhancedImageGallery;
use App\Models\ImageFile;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EnhancedImageGallery Component', function () {
    
    beforeEach(function () {
        // Create test images
        ImageFile::factory()->count(10)->create();
    });

    it('renders successfully', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->assertStatus(200);
    });

    it('loads and displays images', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->assertCount('images', 10);
    });

    it('can toggle selection mode', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->assertSet('selectionMode', false)
            ->call('toggleSelectionMode')
            ->assertSet('selectionMode', true)
            ->call('toggleSelectionMode')
            ->assertSet('selectionMode', false);
    });

    it('can select and deselect photos', function () {
        $image = ImageFile::first();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->call('toggleSelect', $image->id)
            ->assertSet('selectedIds', [$image->id])
            ->call('toggleSelect', $image->id)
            ->assertSet('selectedIds', []);
    });

    it('can select all photos', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->call('selectAll')
            ->assertCount('selectedIds', 10);
    });

    it('can deselect all photos', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->call('selectAll')
            ->assertCount('selectedIds', 10)
            ->call('deselectAll')
            ->assertSet('selectedIds', []);
    });

    it('can toggle favorite status', function () {
        $image = ImageFile::factory()->create(['is_favorite' => false]);

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleFavorite', $image->id);

        expect($image->fresh()->is_favorite)->toBeTrue();
    });

    it('can filter by favorites', function () {
        // Clear existing data
        ImageFile::query()->forceDelete();
        
        ImageFile::factory()->count(3)->create(['is_favorite' => true]);
        ImageFile::factory()->count(7)->create(['is_favorite' => false]);

        $component = Livewire::test(EnhancedImageGallery::class)
            ->call('toggleFavorites')
            ->assertSet('showFavorites', true);
        
        expect($component->images)->toHaveCount(3);
    });

    it('can delete a photo (soft delete)', function () {
        $image = ImageFile::first();

        Livewire::test(EnhancedImageGallery::class)
            ->call('deleteImage', $image->id);

        expect(ImageFile::find($image->id))->toBeNull()
            ->and(ImageFile::withTrashed()->find($image->id))->not->toBeNull();
    });

    it('can view trash', function () {
        $image = ImageFile::first();
        $image->delete();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleTrash')
            ->assertSet('showTrash', true)
            ->assertCount('images', 1);
    });

    it('can restore deleted photo', function () {
        $image = ImageFile::first();
        $imageId = $image->id;
        $image->delete();

        Livewire::test(EnhancedImageGallery::class)
            ->call('restoreImage', $imageId);

        expect(ImageFile::find($imageId))->not->toBeNull();
    });

    it('can permanently delete photo', function () {
        $image = ImageFile::first();
        $imageId = $image->id;
        $image->delete();

        Livewire::test(EnhancedImageGallery::class)
            ->call('permanentlyDelete', $imageId);

        expect(ImageFile::withTrashed()->find($imageId))->toBeNull();
    });

    it('can bulk delete photos', function () {
        $ids = ImageFile::take(3)->pluck('id')->toArray();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->set('selectedIds', $ids)
            ->call('bulkDelete');

        expect(ImageFile::count())->toBe(7)
            ->and(ImageFile::onlyTrashed()->count())->toBe(3);
    });

    it('can bulk favorite photos', function () {
        ImageFile::query()->update(['is_favorite' => false]); // Reset all
        $ids = ImageFile::take(3)->pluck('id')->toArray();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->set('selectedIds', $ids)
            ->call('bulkFavorite');

        expect(ImageFile::where('is_favorite', true)->count())->toBeGreaterThanOrEqual(3);
    });

    it('can bulk unfavorite photos', function () {
        ImageFile::query()->update(['is_favorite' => true]);
        $ids = ImageFile::take(3)->pluck('id')->toArray();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->set('selectedIds', $ids)
            ->call('bulkUnfavorite');

        expect(ImageFile::where('is_favorite', false)->count())->toBe(3);
    });

    it('can view photo details', function () {
        $image = ImageFile::first();

        Livewire::test(EnhancedImageGallery::class)
            ->call('viewDetails', $image->id)
            ->assertSet('selectedImage.id', $image->id);
    });

    it('increments view count when viewing details', function () {
        $image = ImageFile::factory()->create(['view_count' => 0]);

        Livewire::test(EnhancedImageGallery::class)
            ->call('viewDetails', $image->id);

        expect($image->fresh()->view_count)->toBe(1);
    });

    it('can close photo details', function () {
        $image = ImageFile::first();

        Livewire::test(EnhancedImageGallery::class)
            ->call('viewDetails', $image->id)
            ->assertSet('selectedImage.id', $image->id)
            ->call('closeDetails')
            ->assertSet('selectedImage', null);
    });

    it('can filter by meta tags', function () {
        ImageFile::query()->delete();
        ImageFile::factory()->count(3)->create(['meta_tags' => ['nature', 'landscape']]);
        ImageFile::factory()->count(2)->create(['meta_tags' => ['portrait', 'people']]);

        Livewire::test(EnhancedImageGallery::class)
            ->call('filterByTag', 'nature')
            ->assertSet('filterTag', 'nature')
            ->assertCount('images', 3);
    });

    it('can clear tag filter', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->call('filterByTag', 'nature')
            ->assertSet('filterTag', 'nature')
            ->call('clearFilter')
            ->assertSet('filterTag', '')
            ->assertCount('images', 10);
    });

    it('loads statistics correctly', function () {
        $stats = Livewire::test(EnhancedImageGallery::class)->stats;

        expect($stats)->toHaveKey('total')
            ->and($stats)->toHaveKey('favorites')
            ->and($stats)->toHaveKey('trashed')
            ->and($stats['total'])->toBeGreaterThanOrEqual(0);
    });

    it('clears selection when exiting selection mode', function () {
        $ids = ImageFile::take(3)->pluck('id')->toArray();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->set('selectedIds', $ids)
            ->assertCount('selectedIds', 3)
            ->call('toggleSelectionMode')
            ->assertSet('selectedIds', []);
    });

    it('can sort images by date', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->call('sortByDate')
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortDirection', 'asc');
    });

    it('can sort images by name', function () {
        Livewire::test(EnhancedImageGallery::class)
            ->call('sortByName')
            ->assertSet('sortBy', 'original_filename');
    });
});

describe('EnhancedImageGallery Bulk Operations', function () {
    
    it('bulk operations require selection', function () {
        ImageFile::factory()->count(5)->create();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->call('bulkDelete')
            ->assertCount('selectedIds', 0);

        expect(ImageFile::count())->toBe(5);
    });

    it('bulk download dispatches download event', function () {
        $ids = ImageFile::factory()->count(3)->create()->pluck('id')->toArray();

        Livewire::test(EnhancedImageGallery::class)
            ->call('toggleSelectionMode')
            ->set('selectedIds', $ids)
            ->call('bulkDownload')
            ->assertDispatched('download-multiple');
    });

    it('download single photo dispatches download event', function () {
        $image = ImageFile::factory()->create();

        Livewire::test(EnhancedImageGallery::class)
            ->call('downloadImage', $image->id)
            ->assertDispatched('download-image');
    });
});

