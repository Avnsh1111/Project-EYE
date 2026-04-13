<?php

use App\Models\User;
use App\Services\ResumableUploadService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Ensure the local disk is not faked (could bleed from other tests)
    Storage::forgetDisk('local');
});

afterEach(function () {
    $dirs = [
        storage_path('app/resumable_uploads'),
        storage_path('app/uploads'),
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
});

test('initUpload returns upload_id and chunk_size', function () {
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $result = $service->initUpload($user, 'photo.jpg', 15 * 1024 * 1024);

    expect($result)->toHaveKeys(['upload_id', 'chunk_size']);
    expect($result['upload_id'])->toBeString()->toHaveLength(36); // UUID
    expect($result['chunk_size'])->toBe(5 * 1024 * 1024);
});

test('appendChunk stores data and returns bytes received', function () {
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $init   = $service->initUpload($user, 'photo.jpg', 100);
    $result = $service->appendChunk($init['upload_id'], 0, 'hello world');

    expect($result)->toHaveKey('received');
    expect($result['received'])->toBe(11); // high-water mark after writing 11 bytes at offset 0

    // Verify the chunk was actually written to the temp file on disk
    $tempPath = storage_path("app/resumable_uploads/{$init['upload_id']}.tmp");
    expect(file_exists($tempPath))->toBeTrue();
    expect(filesize($tempPath))->toBe(11);
    expect(file_get_contents($tempPath))->toBe('hello world');
});

test('finalise assembles chunks and creates MediaFile', function () {
    Queue::fake();
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $content  = str_repeat('a', 100);
    $init     = $service->initUpload($user, 'photo.jpg', 100);

    // Upload in 3 chunks
    $service->appendChunk($init['upload_id'], 0,  substr($content, 0, 40));
    $service->appendChunk($init['upload_id'], 40, substr($content, 40, 40));
    $service->appendChunk($init['upload_id'], 80, substr($content, 80, 20));

    $mediaFile = $service->finalise($init['upload_id']);

    expect($mediaFile)->toBeInstanceOf(\App\Models\MediaFile::class);
    expect($mediaFile->original_filename)->toBe('photo.jpg');
    expect($mediaFile->user_id)->toBe($user->id);
    expect($mediaFile->processing_status)->toBe('pending');
    expect($mediaFile->file_size)->toBe(100);

    // Verify the assembled file on disk has the correct content
    $destPath = storage_path("app/{$mediaFile->file_path}");
    expect(file_exists($destPath))->toBeTrue();
    expect(file_get_contents($destPath))->toBe($content);

    // Verify the processing job was dispatched
    $pushed = Queue::pushed(\App\Jobs\ProcessImageAnalysis::class);
    expect($pushed)->toHaveCount(1);
    expect($pushed->first()->imageFileId)->toBe($mediaFile->id);
});
