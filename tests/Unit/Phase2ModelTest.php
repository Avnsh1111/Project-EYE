<?php

use App\Models\DeviceSyncState;
use App\Models\Family;
use App\Models\ShareLink;
use App\Models\StorageQuota;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('Family model has owner relationship', function () {
    $family = new Family(['name' => 'Test', 'owner_id' => 1]);
    expect($family->owner())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('Family model has members relationship', function () {
    $family = new Family();
    expect($family->members())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

test('StorageQuota model has user relationship', function () {
    $quota = new StorageQuota();
    expect($quota->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('ShareLink model has user and mediaFile relationships', function () {
    $link = new ShareLink();
    expect($link->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($link->mediaFile())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('DeviceSyncState model has user relationship', function () {
    $state = new DeviceSyncState();
    expect($state->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('User model has storageQuota, shareLinks, families relationships', function () {
    $user = new User();
    expect($user->storageQuota())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
    expect($user->shareLinks())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->families())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});
