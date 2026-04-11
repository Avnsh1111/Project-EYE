<?php

use App\Models\MediaFile;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\ShareLinkService;
use Illuminate\Support\Facades\DB;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeMedia(int $userId): int
{
    return DB::table('media_files')->insertGetId([
        'user_id'           => $userId,
        'original_filename' => 'test.jpg',
        'file_path'         => 'uploads/' . uniqid() . '.jpg',
        'media_type'        => 'image',
        'mime_type'         => 'image/jpeg',
        'file_size'         => 1024,
        'processing_status' => 'completed',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
}

test('create returns a ShareLink with a unique token', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create([
        'user_id'       => $user->id,
        'media_file_id' => $mediaId,
    ]);

    expect($link)->toBeInstanceOf(ShareLink::class);
    expect($link->token)->toHaveLength(64);
    expect($link->is_active)->toBeTrue();
});

test('validate returns the share link for a valid token', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create(['user_id' => $user->id, 'media_file_id' => $mediaId]);

    $found = $service->validate($link->token, null);
    expect($found->id)->toBe($link->id);
});

test('validate throws for expired link', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create([
        'user_id'       => $user->id,
        'media_file_id' => $mediaId,
        'expires_at'    => now()->subHour(),
    ]);

    expect(fn () => $service->validate($link->token, null))
        ->toThrow(\App\Exceptions\ShareLinkException::class);
});

test('validate throws for wrong password', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create([
        'user_id'       => $user->id,
        'media_file_id' => $mediaId,
        'password'      => 'secret123',
    ]);

    expect(fn () => $service->validate($link->token, 'wrongpassword'))
        ->toThrow(\App\Exceptions\ShareLinkException::class);
});

test('validate throws when max_views reached', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create([
        'user_id'       => $user->id,
        'media_file_id' => $mediaId,
        'max_views'     => 2,
    ]);

    // Use up all views
    $service->validate($link->token, null); // view 1
    $service->validate($link->token, null); // view 2

    expect(fn () => $service->validate($link->token, null))
        ->toThrow(\App\Exceptions\ShareLinkException::class);
});

test('revoke deactivates the link', function () {
    $user    = User::factory()->create();
    $mediaId = makeMedia($user->id);

    $service = new ShareLinkService();
    $link    = $service->create(['user_id' => $user->id, 'media_file_id' => $mediaId]);

    $service->revoke($link->token, $user->id);

    expect(fn () => $service->validate($link->token, null))
        ->toThrow(\App\Exceptions\ShareLinkException::class);
});
