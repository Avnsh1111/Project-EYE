<?php

use App\Models\ArchiveFile;

test('hasPassword returns false when is_encrypted column is false', function () {
    $archive = new ArchiveFile(['is_encrypted' => false]);
    expect($archive->hasPassword())->toBeFalse();
});

test('hasPassword returns true when is_encrypted column is true', function () {
    $archive = new ArchiveFile(['is_encrypted' => true]);
    expect($archive->hasPassword())->toBeTrue();
});

test('hasPassword returns false when is_encrypted is null', function () {
    $archive = new ArchiveFile(['is_encrypted' => null]);
    expect($archive->hasPassword())->toBeFalse();
});
