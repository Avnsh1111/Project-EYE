<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('stream returns 416 for malformed Range header', function () {
    // Write a real file to local storage so file_exists() can find it
    $content = str_repeat('v', 2048);
    Storage::disk('local')->put('test-range.mp4', $content);

    // Use DB::table to bypass STI model scoping and reliably insert media_type='video'
    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => 'test-range.mp4',
        'original_filename' => 'test-range.mp4',
        'mime_type'         => 'video/mp4',
        'file_size'         => 2048,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'invalid-range-header',
        ]);

    $response->assertStatus(416);

    // Cleanup
    Storage::disk('local')->delete('test-range.mp4');
});

test('stream returns 206 for valid Range header', function () {
    $content = str_repeat('a', 2048);
    Storage::disk('local')->put('test-range-valid.mp4', $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => 'test-range-valid.mp4',
        'original_filename' => 'test-range-valid.mp4',
        'mime_type'         => 'video/mp4',
        'file_size'         => 2048,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'bytes=0-1023',
        ]);

    $response->assertStatus(206);

    Storage::disk('local')->delete('test-range-valid.mp4');
});

test('stream returns 206 for suffix Range header', function () {
    $content = str_repeat('b', 2048);
    Storage::disk('local')->put('test-range-suffix.mp4', $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => 'test-range-suffix.mp4',
        'original_filename' => 'test-range-suffix.mp4',
        'mime_type'         => 'video/mp4',
        'file_size'         => 2048,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'bytes=-500',  // last 500 bytes
        ]);

    $response->assertStatus(206);

    Storage::disk('local')->delete('test-range-suffix.mp4');
});
