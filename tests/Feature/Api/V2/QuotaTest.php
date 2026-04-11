<?php

use App\Exceptions\QuotaExceededException;
use App\Models\StorageQuota;
use App\Models\User;
use App\Services\QuotaService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('getUsage returns correct used and total bytes', function () {
    $user = User::factory()->create();
    StorageQuota::create(['user_id' => $user->id, 'quota_bytes' => 1000, 'used_bytes' => 400]);

    $service = new QuotaService();
    $usage   = $service->getUsage($user);

    expect($usage['used'])->toBe(400);
    expect($usage['total'])->toBe(1000);
    expect($usage['percent'])->toBe(40.0);
});

test('checkBeforeUpload throws when quota would be exceeded', function () {
    $user = User::factory()->create();
    StorageQuota::create(['user_id' => $user->id, 'quota_bytes' => 1000, 'used_bytes' => 950]);

    $service = new QuotaService();

    expect(fn () => $service->checkBeforeUpload($user, 100))->toThrow(QuotaExceededException::class);
});

test('checkBeforeUpload passes when within quota', function () {
    $user = User::factory()->create();
    StorageQuota::create(['user_id' => $user->id, 'quota_bytes' => 1000, 'used_bytes' => 500]);

    $service = new QuotaService();

    expect(fn () => $service->checkBeforeUpload($user, 400))->not->toThrow(QuotaExceededException::class);
});

test('increment and decrement update used_bytes correctly', function () {
    $user = User::factory()->create();
    $quota = StorageQuota::create(['user_id' => $user->id, 'quota_bytes' => 1000, 'used_bytes' => 500]);

    $service = new QuotaService();
    $service->increment($user, 200);
    $quota->refresh();
    expect($quota->used_bytes)->toBe(700);

    $service->decrement($user, 100);
    $quota->refresh();
    expect($quota->used_bytes)->toBe(600);
});

test('decrement floors at zero and does not go negative', function () {
    $user  = User::factory()->create();
    $quota = StorageQuota::create(['user_id' => $user->id, 'quota_bytes' => 1000, 'used_bytes' => 100]);

    $service = new QuotaService();
    $service->decrement($user, 9999); // far exceeds current value
    $quota->refresh();

    expect($quota->used_bytes)->toBe(0);
});
