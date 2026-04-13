<?php

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Multi-user isolation', function () {
    it('user A cannot see user B media in index', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Create media for user A
        $userAMediaId = DB::table('media_files')->insertGetId([
            'user_id' => $userA->id,
            'original_filename' => 'mine.jpg',
            'file_path' => 'media/mine.jpg',
            'media_type' => 'image',
            'file_size' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create media for user B using DB::table to bypass global scope
        $mediaId = DB::table('media_files')->insertGetId([
            'user_id' => $userB->id,
            'original_filename' => 'secret.jpg',
            'file_path' => 'media/secret.jpg',
            'media_type' => 'image',
            'file_size' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v2/media');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        // Assert userA sees their own but not userB's
        expect($ids)->toContain($userAMediaId);
        expect($ids)->not->toContain($mediaId);
    });

    it('user A cannot access user B media by ID', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $mediaId = DB::table('media_files')->insertGetId([
            'user_id' => $userB->id,
            'original_filename' => 'secret.jpg',
            'file_path' => 'media/secret.jpg',
            'media_type' => 'image',
            'file_size' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v2/media/{$mediaId}");

        $response->assertNotFound();
    });

    it('user A cannot delete user B media', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $mediaId = DB::table('media_files')->insertGetId([
            'user_id' => $userB->id,
            'original_filename' => 'secret.jpg',
            'file_path' => 'media/secret.jpg',
            'media_type' => 'image',
            'file_size' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v2/media/{$mediaId}");

        $response->assertNotFound();

        // Verify it still exists
        $this->assertDatabaseHas('media_files', ['id' => $mediaId]);
    });

    it('unauthenticated request returns 401', function () {
        $response = $this->getJson('/api/v2/media');
        $response->assertUnauthorized();
    });
});
