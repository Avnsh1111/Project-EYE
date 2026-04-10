<?php

use App\Models\ImageFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('stream returns 416 for malformed Range header', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $content = str_repeat('a', 2048);
    Storage::disk('local')->put('public/test.mp4', $content);

    $media = ImageFile::factory()->create([
        'file_path'  => 'public/test.mp4',
        'media_type' => 'video',
        'mime_type'  => 'video/mp4',
    ]);

    $response = $this->actingAs($user)
        ->get(route('media.stream', $media->id), [
            'Range' => 'invalid-range-header',
        ]);

    $response->assertStatus(416);
});

test('stream returns 206 for valid Range header', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $content = str_repeat('a', 2048);
    Storage::disk('local')->put('public/test.mp4', $content);

    $media = ImageFile::factory()->create([
        'file_path'  => 'public/test.mp4',
        'media_type' => 'video',
        'mime_type'  => 'video/mp4',
    ]);

    $response = $this->actingAs($user)
        ->get(route('media.stream', $media->id), [
            'Range' => 'bytes=0-1023',
        ]);

    $response->assertStatus(206);
});
