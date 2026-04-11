<?php

use App\Models\User;
use App\Services\ResumableUploadService;
use Illuminate\Support\Facades\Storage;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('initUpload returns upload_id and chunk_size', function () {
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $result = $service->initUpload($user, 'photo.jpg', 15 * 1024 * 1024);

    expect($result)->toHaveKeys(['upload_id', 'chunk_size']);
    expect($result['upload_id'])->toBeString()->toHaveLength(36); // UUID
    expect($result['chunk_size'])->toBe(5 * 1024 * 1024);
});

test('appendChunk stores data and returns bytes received', function () {
    Storage::fake('local');
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $init   = $service->initUpload($user, 'photo.jpg', 100);
    $result = $service->appendChunk($init['upload_id'], 0, 'hello world');

    expect($result)->toHaveKey('received');
    expect($result['received'])->toBe(11); // strlen('hello world')
});

test('finalise assembles chunks and creates MediaFile', function () {
    Storage::fake('local');
    $user    = User::factory()->create();
    $service = new ResumableUploadService();

    $content  = str_repeat('a', 100);
    $init     = $service->initUpload($user, 'photo.jpg', 100);

    // Upload in 3 chunks
    $service->appendChunk($init['upload_id'], 0,  substr($content, 0, 40));
    $service->appendChunk($init['upload_id'], 40, substr($content, 40, 40));
    $service->appendChunk($init['upload_id'], 80, substr($content, 80, 20));

    $mediaFile = $service->finalise($init['upload_id'], $user);

    expect($mediaFile)->toBeInstanceOf(\App\Models\MediaFile::class);
    expect($mediaFile->original_filename)->toBe('photo.jpg');
    expect($mediaFile->user_id)->toBe($user->id);
    expect($mediaFile->processing_status)->toBe('pending');
});
