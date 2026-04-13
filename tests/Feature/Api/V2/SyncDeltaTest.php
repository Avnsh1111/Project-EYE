<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('SyncController', function () {
    it('returns files created after the since timestamp', function () {
        $user = User::factory()->create();

        $before = now()->subSeconds(5);

        // Insert 3 media files after the cutoff
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $ids[] = DB::table('media_files')->insertGetId([
                'user_id' => $user->id,
                'original_filename' => "file{$i}.jpg",
                'file_path' => "media/file{$i}.jpg",
                'media_type' => 'image',
                'file_size' => 1000 * $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/sync/delta?since=' . urlencode($before->toIso8601String()));

        $response->assertOk();
        $returnedIds = collect($response->json('items'))->pluck('id')->toArray();

        expect($returnedIds)->toHaveCount(3);
        foreach ($ids as $id) {
            expect($returnedIds)->toContain($id);
        }
    });

    it('does not return files from another user', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $before = now()->subSeconds(5);

        DB::table('media_files')->insertGetId([
            'user_id' => $userB->id,
            'original_filename' => 'other.jpg',
            'file_path' => 'media/other.jpg',
            'media_type' => 'image',
            'file_size' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v2/sync/delta?since=' . urlencode($before->toIso8601String()));

        $response->assertOk();
        expect($response->json('count'))->toBe(0);
    });

    it('does not return files created before the since timestamp', function () {
        $user = User::factory()->create();

        // Insert an old file
        DB::table('media_files')->insertGetId([
            'user_id' => $user->id,
            'original_filename' => 'old.jpg',
            'file_path' => 'media/old.jpg',
            'media_type' => 'image',
            'file_size' => 100,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $since = now()->subMinutes(30)->toIso8601String();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/sync/delta?since=' . urlencode($since));

        $response->assertOk();
        expect($response->json('count'))->toBe(0);
    });

    it('upserts device sync state', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/sync/state', [
                'device_id' => 'device-abc-123',
                'last_sync_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201);
        expect($response->json('device_id'))->toBe('device-abc-123');

        // Second call should return 200 (update)
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/sync/state', [
                'device_id' => 'device-abc-123',
                'last_sync_at' => now()->toIso8601String(),
            ]);

        $response2->assertStatus(200);
    });

    it('requires since parameter for delta', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/sync/delta');

        $response->assertUnprocessable();
    });
});
