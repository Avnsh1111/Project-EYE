<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::forgetDisk('local');
});

afterEach(function () {
    $dirs = [storage_path('app/resumable_uploads'), storage_path('app/uploads')];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
});

describe('Upload endpoints', function () {
    it('initialises a resumable upload', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'test.jpg',
                'total_bytes' => 100,
            ]);

        $response->assertCreated();
        expect($response->json('upload_id'))->toBeString();
        expect($response->json('chunk_size'))->toBe(5242880);
    });

    it('uploads a chunk and finalises', function () {
        Queue::fake();
        $user = User::factory()->create();

        $init = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'hello.jpg',
                'total_bytes' => 11,
            ])
            ->json();

        $uploadId = $init['upload_id'];

        // Write the chunk directly via service (body-based chunk is awkward to test via HTTP)
        app(\App\Services\ResumableUploadService::class)
            ->appendChunk($uploadId, 0, 'hello world');

        $finalise = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/uploads/{$uploadId}/finalise");

        $finalise->assertCreated();
        expect($finalise->json('file_size'))->toBe(11);
        expect($finalise->json('user_id'))->toBe($user->id);
    });

    it('rejects upload over quota', function () {
        $user = User::factory()->create();

        \App\Models\StorageQuota::create([
            'user_id' => $user->id,
            'quota_bytes' => 50,
            'used_bytes' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'big.jpg',
                'total_bytes' => 100,
            ]);

        $response->assertStatus(413);
    });

    it('dedups files with the same hash for a user', function () {
        Queue::fake();
        $user = User::factory()->create();
        $service = app(\App\Services\ResumableUploadService::class);

        // First upload
        $init1 = $service->initUpload($user, 'first.jpg', 11);
        $service->appendChunk($init1['upload_id'], 0, 'hello world');
        $first = $service->finalise($init1['upload_id']);

        // Second upload with identical content
        $init2 = $service->initUpload($user, 'second.jpg', 11);
        $service->appendChunk($init2['upload_id'], 0, 'hello world');
        $second = $service->finalise($init2['upload_id']);

        // Dedup should return the existing record
        expect($second->id)->toBe($first->id);

        // Only one row persisted
        $count = \App\Models\MediaFile::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->count();
        expect($count)->toBe(1);
    });
});
