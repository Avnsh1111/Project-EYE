<?php

use App\Models\ImageFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ImageFile Model', function () {
    
    it('can create an image file record', function () {
        $image = ImageFile::create([
            'file_path' => 'public/images/test.jpg',
            'original_filename' => 'test.jpg',
            'description' => 'A test image',
            'embedding' => array_fill(0, 512, 0.1),
        ]);

        expect($image)->toBeInstanceOf(ImageFile::class)
            ->and($image->file_path)->toBe('public/images/test.jpg')
            ->and($image->description)->toBe('A test image');
    });

    it('can favorite an image', function () {
        $image = ImageFile::factory()->create(['is_favorite' => false]);

        $image->update(['is_favorite' => true]);

        expect($image->fresh()->is_favorite)->toBeTrue();
    });

    it('can soft delete an image', function () {
        $image = ImageFile::factory()->create();
        $imageId = $image->id;

        $image->delete();

        expect(ImageFile::find($imageId))->toBeNull()
            ->and(ImageFile::withTrashed()->find($imageId))->not->toBeNull()
            ->and(ImageFile::onlyTrashed()->count())->toBe(1);
    });

    it('can restore a soft deleted image', function () {
        $image = ImageFile::factory()->create();
        $image->delete();

        $image->restore();

        expect(ImageFile::find($image->id))->not->toBeNull()
            ->and(ImageFile::onlyTrashed()->count())->toBe(0);
    });

    it('can permanently delete an image', function () {
        $image = ImageFile::factory()->create();
        $imageId = $image->id;

        $image->forceDelete();

        expect(ImageFile::withTrashed()->find($imageId))->toBeNull();
    });

    it('casts meta_tags as array', function () {
        $image = ImageFile::factory()->create([
            'meta_tags' => ['nature', 'landscape', 'sunset'],
        ]);

        expect($image->meta_tags)->toBeArray()
            ->and($image->meta_tags)->toHaveCount(3)
            ->and($image->meta_tags)->toContain('nature');
    });

    it('casts face_encodings as array', function () {
        $image = ImageFile::factory()->create([
            'face_encodings' => [[0.1, 0.2, 0.3]],
        ]);

        expect($image->face_encodings)->toBeArray()
            ->and($image->face_encodings)->toHaveCount(1);
    });

    it('casts exif_data as array', function () {
        $image = ImageFile::factory()->create([
            'exif_data' => ['Make' => 'Canon', 'Model' => 'EOS R5'],
        ]);

        expect($image->exif_data)->toBeArray()
            ->and($image->exif_data['Make'])->toBe('Canon');
    });

    it('casts date_taken as datetime', function () {
        $image = ImageFile::factory()->create([
            'date_taken' => '2024-01-15 10:30:00',
        ]);

        expect($image->date_taken)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('increments view count', function () {
        $image = ImageFile::factory()->create(['view_count' => 5]);

        $image->increment('view_count');

        expect($image->fresh()->view_count)->toBe(6);
    });

    it('searches similar images with minimum similarity threshold', function () {
        // Create images with embeddings
        ImageFile::factory()->create([
            'embedding' => array_fill(0, 512, 0.5),
            'description' => 'Similar image',
        ]);
        
        ImageFile::factory()->create([
            'embedding' => array_fill(0, 512, 0.1),
            'description' => 'Different image',
        ]);

        $queryEmbedding = array_fill(0, 512, 0.5);
        
        $results = ImageFile::searchSimilar($queryEmbedding, 10, 0.5);

        expect($results)->toBeArray();
    });

    it('stores edit history as json', function () {
        $image = ImageFile::factory()->create([
            'edit_history' => [
                ['action' => 'rotate', 'angle' => 90, 'timestamp' => now()->toIso8601String()],
            ],
        ]);

        expect($image->edit_history)->toBeArray()
            ->and($image->edit_history[0]['action'])->toBe('rotate');
    });

    it('handles gps coordinates', function () {
        $image = ImageFile::factory()->create([
            'gps_latitude' => 37.7749,
            'gps_longitude' => -122.4194,
        ]);

        expect($image->gps_latitude)->toBe(37.7749)
            ->and($image->gps_longitude)->toBe(-122.4194);
    });

    it('stores camera metadata', function () {
        $image = ImageFile::factory()->create([
            'camera_make' => 'Canon',
            'camera_model' => 'EOS R5',
            'lens_model' => 'RF 24-70mm F2.8',
        ]);

        expect($image->camera_make)->toBe('Canon')
            ->and($image->camera_model)->toBe('EOS R5')
            ->and($image->lens_model)->toBe('RF 24-70mm F2.8');
    });
});

describe('ImageFile Scopes and Queries', function () {
    
    it('filters by favorite status', function () {
        ImageFile::factory()->count(3)->create(['is_favorite' => true]);
        ImageFile::factory()->count(2)->create(['is_favorite' => false]);

        $favorites = ImageFile::where('is_favorite', true)->get();

        expect($favorites)->toHaveCount(3);
    });

    it('filters by face count', function () {
        ImageFile::factory()->create(['face_count' => 3]);
        ImageFile::factory()->create(['face_count' => 0]);

        $withFaces = ImageFile::where('face_count', '>', 0)->get();

        expect($withFaces)->toHaveCount(1);
    });

    it('filters by meta tags', function () {
        ImageFile::factory()->create([
            'meta_tags' => ['nature', 'landscape'],
        ]);
        
        ImageFile::factory()->create([
            'meta_tags' => ['portrait', 'people'],
        ]);

        $natureImages = ImageFile::whereJsonContains('meta_tags', 'nature')->get();

        expect($natureImages)->toHaveCount(1);
    });
});

