<?php

use App\Models\MediaFile;
use App\Models\User;
use App\Services\DeduplicationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('hashFile returns SHA256 of file contents', function () {
    Storage::fake('local');
    $content = 'test file content';
    $path = 'test/hashable.txt';
    Storage::disk('local')->put($path, $content);

    $service  = new DeduplicationService();
    $fullPath = Storage::disk('local')->path($path);
    $hash     = $service->hashFile($fullPath);

    expect($hash)->toBe(hash('sha256', $content));
});

test('isDuplicate returns false when no matching file exists', function () {
    $user    = User::factory()->create();
    $service = new DeduplicationService();

    expect($service->isDuplicate('nonexistenthash123', $user->id))->toBeFalse();
});

test('isDuplicate returns true when file with same hash exists for same user', function () {
    $user = User::factory()->create();

    // Insert a media_file row with a known hash (bypass STI scope with DB)
    \Illuminate\Support\Facades\DB::table('media_files')->insert([
        'user_id'           => $user->id,
        'original_filename' => 'dup.jpg',
        'file_path'         => 'uploads/dup.jpg',
        'media_type'        => 'image',
        'mime_type'         => 'image/jpeg',
        'file_size'         => 1024,
        'file_hash'         => 'abc123hash',
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $service = new DeduplicationService();
    expect($service->isDuplicate('abc123hash', $user->id))->toBeTrue();
});

test('isDuplicate returns false for same hash but different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    \Illuminate\Support\Facades\DB::table('media_files')->insert([
        'user_id'           => $user1->id,
        'original_filename' => 'shared.jpg',
        'file_path'         => 'uploads/shared.jpg',
        'media_type'        => 'image',
        'mime_type'         => 'image/jpeg',
        'file_size'         => 1024,
        'file_hash'         => 'sharedhash',
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $service = new DeduplicationService();
    expect($service->isDuplicate('sharedhash', $user2->id))->toBeFalse();
});
