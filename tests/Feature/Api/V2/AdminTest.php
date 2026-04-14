<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Admin API', function () {
    beforeEach(function () {
        $this->makeAdmin = function (): User {
            $admin = User::factory()->create();
            $admin->is_admin = true;
            $admin->save();
            return $admin;
        };
    });

    it('non-admin gets 403 on users endpoint', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/admin/users');

        $response->assertForbidden();
    });

    it('non-admin gets 403 on stats endpoint', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/admin/stats');

        $response->assertForbidden();
    });

    it('non-admin gets 403 on quota update', function () {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v2/admin/users/{$target->id}/quota", ['quota_bytes' => 50000000000]);

        $response->assertForbidden();
    });

    it('admin can list users with quota usage', function () {
        $admin = ($this->makeAdmin)();
        User::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/users');

        $response->assertOk();
        expect($response->json('data'))->toBeArray();
        expect(count($response->json('data')))->toBeGreaterThanOrEqual(3);
    });

    it('admin can view system stats', function () {
        $admin = ($this->makeAdmin)();
        $user = User::factory()->create();

        DB::table('media_files')->insert([
            'user_id' => $user->id,
            'original_filename' => 'a.jpg',
            'file_path' => 'media/a.jpg',
            'media_type' => 'image',
            'file_size' => 1000,
            'processing_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/stats');

        $response->assertOk();
        expect($response->json('total_files'))->toBeGreaterThanOrEqual(1);
        expect($response->json('total_storage_bytes'))->toBeGreaterThanOrEqual(1000);
    });

    it('admin can update user quota', function () {
        $admin = ($this->makeAdmin)();
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v2/admin/users/{$user->id}/quota", ['quota_bytes' => 500000000]);

        $response->assertOk();
        expect($response->json('quota_bytes'))->toBe(500000000);

        // Verify DB state
        expect(\App\Models\StorageQuota::where('user_id', $user->id)->first()->quota_bytes)->toBe(500000000);
    });

    it('admin quota update validates quota_bytes', function () {
        $admin = ($this->makeAdmin)();
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v2/admin/users/{$user->id}/quota", ['quota_bytes' => -1]);

        $response->assertUnprocessable();
    });
});
