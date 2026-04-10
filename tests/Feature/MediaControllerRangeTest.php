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
    $size = strlen($content);
    $filename = 'test-range-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    // Use DB::table to bypass STI model scoping and reliably insert media_type='video'
    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
        'mime_type'         => 'video/mp4',
        'file_size'         => $size,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'invalid-range-header',
        ]);

    $response->assertStatus(416);
    $response->assertHeader('Content-Range', "bytes */{$size}");

    // Cleanup
    Storage::disk('local')->delete($filename);
});

test('stream returns 206 for valid Range header', function () {
    $content = str_repeat('a', 2048);
    $size = strlen($content);
    $filename = 'test-range-valid-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
        'mime_type'         => 'video/mp4',
        'file_size'         => $size,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'bytes=0-1023',
        ]);

    $actualEnd = min(1023, $size - 1);
    $expectedLength = $actualEnd - 0 + 1;

    $response->assertStatus(206);
    $response->assertHeader('Content-Range', "bytes 0-{$actualEnd}/{$size}");
    $response->assertHeader('Content-Length', (string) $expectedLength);

    Storage::disk('local')->delete($filename);
});

test('stream returns 206 for suffix Range header', function () {
    $content = str_repeat('b', 2048);
    $size = strlen($content);
    $filename = 'test-range-suffix-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $id = DB::table('media_files')->insertGetId([
        'media_type'        => 'video',
        'file_path'         => $filename,
        'original_filename' => $filename,
        'mime_type'         => 'video/mp4',
        'file_size'         => $size,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $id), [
            'Range' => 'bytes=-500',  // last 500 bytes
        ]);

    // For bytes=-500 on a 2048-byte file: start=2048-500=1548, end=2047, length=500
    $expectedStart = $size - 500;
    $expectedEnd   = $size - 1;
    $expectedLength = 500;

    $response->assertStatus(206);
    $response->assertHeader('Content-Range', "bytes {$expectedStart}-{$expectedEnd}/{$size}");
    $response->assertHeader('Content-Length', (string) $expectedLength);

    Storage::disk('local')->delete($filename);
});

test('stream returns 416 for out-of-bounds range', function () {
    $content = str_repeat('x', 2048);
    $size = strlen($content);
    $filename = 'test-range-oob-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $media = DB::table('media_files')->insertGetId([
        'file_path'          => $filename,
        'original_filename'  => $filename,
        'media_type'         => 'video',
        'mime_type'          => 'video/mp4',
        'file_size'          => $size,
        'processing_status'  => 'completed',
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $media), [
            'Range' => 'bytes=9999-99999',  // beyond the 2048-byte file
        ]);

    $response->assertStatus(416);
    $response->assertHeader('Content-Range', "bytes */{$size}");

    Storage::disk('local')->delete($filename);
});

test('stream returns 416 for inverted Range header', function () {
    $content = str_repeat('z', 2048);
    $size = strlen($content);
    $filename = 'test-range-inverted-' . uniqid() . '.mp4';
    Storage::disk('local')->put($filename, $content);

    $media = DB::table('media_files')->insertGetId([
        'file_path'          => $filename,
        'original_filename'  => $filename,
        'media_type'         => 'video',
        'mime_type'          => 'video/mp4',
        'file_size'          => $size,
        'processing_status'  => 'completed',
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('media.stream', $media), [
            'Range' => 'bytes=5-3',  // inverted: start > end
        ]);

    $response->assertStatus(416);
    $response->assertHeader('Content-Range', "bytes */{$size}");

    Storage::disk('local')->delete($filename);
});
