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
    $filename = 'test-range-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    // Use DB::table to bypass STI model scoping and reliably insert media_type='video'
    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
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
    Storage::disk('local')->delete($filename);
});

test('stream returns 206 for valid Range header', function () {
    $content = str_repeat('a', 2048);
    $filename = 'test-range-valid-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
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

    Storage::disk('local')->delete($filename);
});

test('stream returns 206 for suffix Range header', function () {
    $content = str_repeat('b', 2048);
    $filename = 'test-range-suffix-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
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

    Storage::disk('local')->delete($filename);
});

test('stream returns 416 for out-of-bounds range', function () {
    $content = str_repeat('x', 2048);
    $filename = 'test-range-oob-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $media = DB::table('media_files')->insertGetId([
        'file_path'          => $filename,
        'original_filename'  => $filename,
        'media_type'         => 'video',
        'mime_type'          => 'video/mp4',
        'file_size'          => strlen($content),
        'processing_status'  => 'completed',
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $media), [
            'Range' => 'bytes=9999-99999',  // beyond the 2048-byte file
        ]);

    $response->assertStatus(416);

    Storage::disk('local')->delete($filename);
});
