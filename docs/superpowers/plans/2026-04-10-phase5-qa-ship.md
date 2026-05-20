# Phase 5 — Integration QA + Ship Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Run the 9 end-to-end integration test scenarios defined in the spec, update all documentation, update the project status report to reflect v2.0 completion, and cut the v2.0.0 release with signed binaries attached.

**Architecture:** No new production code is written in this phase. Work is: (1) E2E tests that exercise the full running stack (web + API + desktop + mobile), (2) three new documentation files, (3) updates to CLAUDE.md and PROJECT_STATUS_REPORT.md, (4) git tag + GitHub release. All tests run against a running Docker Compose stack with real data.

**Tech Stack:** Pest PHP (integration tests), Docker Compose (running stack), `gh` CLI (GitHub release)

**Prerequisite:** Phases 1–4 complete. Docker Compose stack running (`docker compose up -d`). At least one admin user exists. Both mobile and desktop apps built.

---

## File Map

| Action | File |
|--------|------|
| Create | `tests/Feature/Integration/FullSyncLoopTest.php` |
| Create | `tests/Feature/Integration/OfflineQueueTest.php` |
| Create | `tests/Feature/Integration/DedupE2ETest.php` |
| Create | `tests/Feature/Integration/MultiUserIsolationE2ETest.php` |
| Create | `tests/Feature/Integration/FamilySharingTest.php` |
| Create | `tests/Feature/Integration/ShareLinkE2ETest.php` |
| Create | `tests/Feature/Integration/QuotaEnforcementE2ETest.php` |
| Create | `tests/Feature/Integration/DesktopFolderWatchTest.php` |
| Create | `tests/Feature/Integration/ArchivePasswordTest.php` |
| Create | `docs/MOBILE_SETUP.md` |
| Create | `docs/DESKTOP_SETUP.md` |
| Create | `docs/MULTI_USER_SETUP.md` |
| Modify | `CLAUDE.md` |
| Modify | `PROJECT_STATUS_REPORT.md` |
| Modify | `README.md` |

---

## Task 1: E2E Test — Full Sync Loop

Scenario: Photo uploaded via API → AI pipeline runs → appears in gallery → WebSocket event fired.

- [ ] **Create `tests/Feature/Integration/FullSyncLoopTest.php`**

```php
<?php

use App\Models\User;
use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use App\Events\ImageProcessed;

it('uploaded photo triggers AI pipeline and appears in gallery within 60 seconds', function () {
    // Arrange: authenticated user
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Act: upload a small JPEG via API v2
    $file     = UploadedFile::fake()->image('test-photo.jpg', 300, 300);
    $response = $this->withToken($token)
        ->postJson('/api/v2/media/upload', [
            'file' => $file,
        ]);

    $response->assertStatus(201);
    $mediaId = $response->json('id');
    expect($mediaId)->toBeInt();

    // Act: run the queue synchronously in test
    $this->artisan('queue:work', ['--once' => true, '--queue' => 'default']);

    // Assert: media file exists with AI data
    $media = MediaFile::findOrFail($mediaId);
    expect($media->status)->toBe('processed');
    expect($media->user_id)->toBe($user->id);
});

it('ImageProcessed event is broadcast after pipeline completes', function () {
    Event::fake([ImageProcessed::class]);

    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->image('broadcast-test.jpg');
    $this->withToken($token)->postJson('/api/v2/media/upload', ['file' => $file])->assertStatus(201);

    $this->artisan('queue:work', ['--once' => true]);

    Event::assertDispatched(ImageProcessed::class);
});
```

- [ ] **Run the test against a running stack**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/FullSyncLoopTest.php -v
```

Expected: Both tests PASS. Fix any failures before proceeding.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/FullSyncLoopTest.php
git commit -m "test: add E2E full sync loop integration test"
```

---

## Task 2: E2E Test — Offline Queue + Resume

Scenario: Upload interrupted mid-stream → client resumes from last offset → file assembled correctly.

- [ ] **Create `tests/Feature/Integration/OfflineQueueTest.php`**

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('resumable upload assembles file correctly from 3 chunks', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Create a 15MB temp file (3 × 5MB chunks)
    $content  = str_repeat('A', 15 * 1024 * 1024);
    $filename = 'chunked-test.bin';

    // Init upload
    $initResp = $this->withToken($token)
        ->postJson('/api/v2/media/upload/init', [
            'filename'    => $filename,
            'total_bytes' => strlen($content),
            'file_hash'   => hash('sha256', $content),
        ]);

    $initResp->assertStatus(201);
    $uploadId = $initResp->json('upload_id');
    expect($uploadId)->toBeString();

    // Send chunk 1 (0–5MB)
    $chunk1 = substr($content, 0, 5 * 1024 * 1024);
    $this->withToken($token)
        ->withBody($chunk1, 'application/octet-stream')
        ->patch("/api/v2/media/upload/{$uploadId}", ['offset' => 0])
        ->assertStatus(200);

    // Send chunk 2 (5–10MB)
    $chunk2 = substr($content, 5 * 1024 * 1024, 5 * 1024 * 1024);
    $this->withToken($token)
        ->withBody($chunk2, 'application/octet-stream')
        ->patch("/api/v2/media/upload/{$uploadId}", ['offset' => 5 * 1024 * 1024])
        ->assertStatus(200);

    // Send chunk 3 (10–15MB) — this completes the upload
    $chunk3 = substr($content, 10 * 1024 * 1024);
    $finalResp = $this->withToken($token)
        ->withBody($chunk3, 'application/octet-stream')
        ->patch("/api/v2/media/upload/{$uploadId}", ['offset' => 10 * 1024 * 1024])
        ->assertStatus(200);

    $mediaId = $finalResp->json('media_id');
    expect($mediaId)->toBeInt();

    // Verify assembled file matches original
    $media = \App\Models\MediaFile::findOrFail($mediaId);
    expect(hash('sha256', file_get_contents(Storage::path($media->file_path))))
        ->toBe(hash('sha256', $content));
});

it('resumes upload from correct offset after simulated interruption', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $content = str_repeat('B', 10 * 1024 * 1024);

    $initResp = $this->withToken($token)
        ->postJson('/api/v2/media/upload/init', [
            'filename'    => 'resume-test.bin',
            'total_bytes' => strlen($content),
            'file_hash'   => hash('sha256', $content),
        ]);
    $uploadId = $initResp->json('upload_id');

    // Send chunk 1 only — simulate interruption
    $this->withToken($token)
        ->withBody(substr($content, 0, 5 * 1024 * 1024), 'application/octet-stream')
        ->patch("/api/v2/media/upload/{$uploadId}", ['offset' => 0])
        ->assertStatus(200);

    // Query resume state — API should report 5MB received
    $stateResp = $this->withToken($token)
        ->getJson("/api/v2/media/upload/{$uploadId}/state");
    $stateResp->assertStatus(200);
    expect($stateResp->json('bytes_received'))->toBe(5 * 1024 * 1024);

    // Resume from offset 5MB
    $this->withToken($token)
        ->withBody(substr($content, 5 * 1024 * 1024), 'application/octet-stream')
        ->patch("/api/v2/media/upload/{$uploadId}", ['offset' => 5 * 1024 * 1024])
        ->assertStatus(200);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/OfflineQueueTest.php -v
```

Expected: Both tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/OfflineQueueTest.php
git commit -m "test: add E2E resumable upload test (3-chunk assembly + resume from offset)"
```

---

## Task 3: E2E Test — Deduplication

Scenario: Upload same file twice → second upload returns existing file ID, no duplicate stored.

- [ ] **Create `tests/Feature/Integration/DedupE2ETest.php`**

```php
<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

it('second upload of same file returns existing media ID without creating duplicate', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->image('duplicate-test.jpg', 200, 200);

    // First upload
    $first = $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);

    $firstId = $first->json('id');
    expect($firstId)->toBeInt();

    // Second upload — same file content
    $file2 = UploadedFile::fake()->image('duplicate-test.jpg', 200, 200);
    file_put_contents($file2->getPathname(), file_get_contents($file->getPathname()));

    $second = $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file2])
        ->assertStatus(200); // 200 not 201 — existing item returned

    expect($second->json('id'))->toBe($firstId);
    expect($second->json('duplicate'))->toBeTrue();

    // Verify only one record in DB
    $count = \App\Models\MediaFile::where('user_id', $user->id)->count();
    expect($count)->toBe(1);
});

it('dedup check endpoint returns exists=true for known hash', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->image('hash-check.jpg');
    $hash = hash('sha256', file_get_contents($file->getPathname()));

    // Upload file first
    $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);

    // Dedup check
    $this->withToken($token)
        ->getJson("/api/v2/media/dedup/{$hash}")
        ->assertStatus(200)
        ->assertJson(['exists' => true]);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/DedupE2ETest.php -v
```

Expected: Both tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/DedupE2ETest.php
git commit -m "test: add E2E deduplication test"
```

---

## Task 4: E2E Test — Multi-user Isolation

Scenario: User A cannot access User B's media files.

- [ ] **Create `tests/Feature/Integration/MultiUserIsolationE2ETest.php`**

```php
<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

it('user A cannot read user B media in list endpoint', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $tokenA = $userA->createToken('A')->plainTextToken;
    $tokenB = $userB->createToken('B')->plainTextToken;

    // User B uploads a file
    $file = UploadedFile::fake()->image('secret-b.jpg');
    $resp = $this->withToken($tokenB)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);

    $mediaBId = $resp->json('id');

    // User A lists media — should not contain User B's file
    $list = $this->withToken($tokenA)
        ->getJson('/api/v2/media')
        ->assertStatus(200);

    $ids = collect($list->json('data'))->pluck('id')->toArray();
    expect($ids)->not->toContain($mediaBId);
});

it('user A cannot fetch user B media by ID', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $tokenA = $userA->createToken('A')->plainTextToken;
    $tokenB = $userB->createToken('B')->plainTextToken;

    $file    = UploadedFile::fake()->image('private.jpg');
    $mediaId = $this->withToken($tokenB)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201)
        ->json('id');

    // User A attempts direct access
    $this->withToken($tokenA)
        ->getJson("/api/v2/media/{$mediaId}")
        ->assertStatus(403);
});

it('unauthenticated request returns 401', function () {
    $this->getJson('/api/v2/media')->assertStatus(401);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/MultiUserIsolationE2ETest.php -v
```

Expected: All 3 tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/MultiUserIsolationE2ETest.php
git commit -m "test: add E2E multi-user isolation test"
```

---

## Task 5: E2E Test — Share Links

Scenario: Share link with password and expiry → access denied after expiry / wrong password.

- [ ] **Create `tests/Feature/Integration/ShareLinkE2ETest.php`**

```php
<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user  = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;

    $file      = UploadedFile::fake()->image('shared.jpg');
    $uploadResp = $this->withToken($this->token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);
    $this->mediaId = $uploadResp->json('id');
});

it('valid share link without password allows public access', function () {
    $link = $this->withToken($this->token)
        ->postJson('/api/v2/share-links', [
            'resource_type' => 'media_file',
            'resource_id'   => $this->mediaId,
        ])
        ->assertStatus(201)
        ->json('token');

    $this->get("/share/{$link}")->assertStatus(200);
});

it('share link with wrong password returns 403', function () {
    $link = $this->withToken($this->token)
        ->postJson('/api/v2/share-links', [
            'resource_type' => 'media_file',
            'resource_id'   => $this->mediaId,
            'password'      => 'correct-password',
        ])
        ->assertStatus(201)
        ->json('token');

    $this->get("/share/{$link}?password=wrong-password")->assertStatus(403);
    $this->get("/share/{$link}?password=correct-password")->assertStatus(200);
});

it('expired share link returns 403', function () {
    $link = $this->withToken($this->token)
        ->postJson('/api/v2/share-links', [
            'resource_type' => 'media_file',
            'resource_id'   => $this->mediaId,
            'expires_at'    => now()->subMinute()->toISOString(),
        ])
        ->assertStatus(201)
        ->json('token');

    $this->get("/share/{$link}")->assertStatus(403);
});

it('share link with max_views=1 returns 403 on second access', function () {
    $link = $this->withToken($this->token)
        ->postJson('/api/v2/share-links', [
            'resource_type' => 'media_file',
            'resource_id'   => $this->mediaId,
            'max_views'     => 1,
        ])
        ->assertStatus(201)
        ->json('token');

    $this->get("/share/{$link}")->assertStatus(200);
    $this->get("/share/{$link}")->assertStatus(403);
});

it('revoked share link returns 404', function () {
    $link = $this->withToken($this->token)
        ->postJson('/api/v2/share-links', [
            'resource_type' => 'media_file',
            'resource_id'   => $this->mediaId,
        ])
        ->assertStatus(201)
        ->json('token');

    $this->withToken($this->token)->deleteJson("/api/v2/share-links/{$link}")->assertStatus(204);
    $this->get("/share/{$link}")->assertStatus(404);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/ShareLinkE2ETest.php -v
```

Expected: All 5 tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/ShareLinkE2ETest.php
git commit -m "test: add E2E share link test (expiry, password, max views, revoke)"
```

---

## Task 6: E2E Test — Quota Enforcement

Scenario: User fills quota → next upload rejected with 413.

- [ ] **Create `tests/Feature/Integration/QuotaEnforcementE2ETest.php`**

```php
<?php

use App\Models\User;
use App\Models\StorageQuota;
use Illuminate\Http\UploadedFile;

it('upload is accepted when quota is 99% full', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Set quota to 1MB total, use 990KB
    StorageQuota::updateOrCreate(
        ['user_id' => $user->id],
        ['quota_bytes' => 1024 * 1024, 'used_bytes' => 990 * 1024],
    );

    // Upload a 5KB file — should succeed
    $file = UploadedFile::fake()->create('small.jpg', 5); // 5 KB
    $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);
});

it('upload is rejected when quota is exactly full', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    StorageQuota::updateOrCreate(
        ['user_id' => $user->id],
        ['quota_bytes' => 1024 * 1024, 'used_bytes' => 1024 * 1024],
    );

    $file = UploadedFile::fake()->create('over-limit.jpg', 1); // 1 KB
    $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(413)
        ->assertJson(['error' => 'quota_exceeded']);
});

it('upload is rejected when file would exceed remaining quota', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // 1MB quota, 900KB used — only 100KB remaining
    StorageQuota::updateOrCreate(
        ['user_id' => $user->id],
        ['quota_bytes' => 1024 * 1024, 'used_bytes' => 900 * 1024],
    );

    // Try to upload a 200KB file
    $file = UploadedFile::fake()->create('too-big.jpg', 200); // 200 KB
    $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(413)
        ->assertJson(['error' => 'quota_exceeded']);
});

it('admin can update user quota', function () {
    $admin = User::factory()->admin()->create();
    $user  = User::factory()->create();

    StorageQuota::updateOrCreate(['user_id' => $user->id], ['quota_bytes' => 1024 * 1024, 'used_bytes' => 0]);

    $this->withToken($admin->createToken('admin')->plainTextToken)
        ->patchJson("/api/v2/admin/users/{$user->id}/quota", ['quota_bytes' => 100 * 1024 * 1024 * 1024])
        ->assertStatus(200);

    expect(StorageQuota::where('user_id', $user->id)->value('quota_bytes'))->toBe(100 * 1024 * 1024 * 1024);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/QuotaEnforcementE2ETest.php -v
```

Expected: All 4 tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/QuotaEnforcementE2ETest.php
git commit -m "test: add E2E quota enforcement test (99%, 100%, over, admin update)"
```

---

## Task 7: E2E Test — Desktop Folder Watch

Scenario: New file appears in watched folder → queue entry created within 1 scan cycle.

- [ ] **Create `tests/Feature/Integration/DesktopFolderWatchTest.php`**

```php
<?php

use App\Services\FolderWatcherService;
use App\Services\OfflineQueueService;

it('new file in watched folder is added to queue after scan', function () {
    $tmpDir  = sys_get_temp_dir() . '/desktop-watch-test-' . uniqid();
    mkdir($tmpDir, 0755, true);

    $configFile = sys_get_temp_dir() . '/test-watcher-config-' . uniqid() . '.json';
    $queuePath  = sys_get_temp_dir() . '/test-queue-' . uniqid() . '.db';

    $watcher = new FolderWatcherService($configFile);
    $queue   = new OfflineQueueService($queuePath);

    $watcher->addFolder($tmpDir);

    // Create a test file in the watched folder
    $filePath = $tmpDir . '/test-photo.jpg';
    file_put_contents($filePath, str_repeat('X', 10 * 1024)); // 10KB

    // Simulate the scan command logic inline
    $files   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS));
    $queued  = 0;
    foreach ($files as $file) {
        if (!$file->isFile()) continue;
        if ($watcher->isIgnored($file->getFilename())) continue;
        if ($file->getSize() < 1024) continue;

        $hash = hash_file('sha256', $file->getPathname());
        if ($queue->getByHash($hash) === null) {
            $queue->enqueue($file->getPathname(), $hash);
            $queued++;
        }
    }

    expect($queued)->toBe(1);
    expect($queue->getPendingCount())->toBe(1);

    // Second scan — same file should not be re-queued
    $queued2 = 0;
    foreach ($files as $file) {
        if (!$file->isFile()) continue;
        $hash = hash_file('sha256', $file->getPathname());
        if ($queue->getByHash($hash) === null) {
            $queue->enqueue($file->getPathname(), $hash);
            $queued2++;
        }
    }
    expect($queued2)->toBe(0);

    // Cleanup
    unlink($filePath);
    rmdir($tmpDir);
    unlink($configFile);
    unlink($queuePath);
});

it('ignored files are not added to queue', function () {
    $tmpDir = sys_get_temp_dir() . '/desktop-watch-ignore-' . uniqid();
    mkdir($tmpDir, 0755, true);

    $watcher = new FolderWatcherService(sys_get_temp_dir() . '/ignore-test-' . uniqid() . '.json');

    // Create files that should be ignored
    file_put_contents($tmpDir . '/.DS_Store', 'meta');
    file_put_contents($tmpDir . '/photo.tmp', 'partial');
    file_put_contents($tmpDir . '/photo.crdownload', 'partial');

    $ignoredCount = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($watcher->isIgnored($file->getFilename())) $ignoredCount++;
    }

    expect($ignoredCount)->toBe(3);

    array_map('unlink', glob($tmpDir . '/*'));
    rmdir($tmpDir);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/DesktopFolderWatchTest.php -v
```

Expected: Both tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/DesktopFolderWatchTest.php
git commit -m "test: add desktop folder watch integration test"
```

---

## Task 8: E2E Test — Archive Password Detection

Scenario: Password-protected ZIP uploaded → `hasPassword()` returns true.

- [ ] **Create `tests/Feature/Integration/ArchivePasswordTest.php`**

```php
<?php

use App\Models\User;
use App\Models\ArchiveFile;
use Illuminate\Http\UploadedFile;

it('password-protected zip has hasPassword returning true after processing', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Create a real password-protected zip file
    $zipPath = sys_get_temp_dir() . '/protected-' . uniqid() . '.zip';
    $zip     = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->setPassword('secret');
    $zip->addFromString('test.txt', 'secret content');
    $zip->setEncryptionIndex(0, ZipArchive::EM_AES_256);
    $zip->close();

    $file = new UploadedFile($zipPath, 'protected.zip', 'application/zip', null, true);

    $resp = $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);

    $mediaId = $resp->json('id');

    $this->artisan('queue:work', ['--once' => true]);

    $archive = ArchiveFile::findOrFail($mediaId);
    expect($archive->hasPassword())->toBeTrue();

    unlink($zipPath);
});

it('unprotected zip has hasPassword returning false', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $zipPath = sys_get_temp_dir() . '/normal-' . uniqid() . '.zip';
    $zip     = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('readme.txt', 'hello');
    $zip->close();

    $file = new UploadedFile($zipPath, 'normal.zip', 'application/zip', null, true);

    $resp = $this->withToken($token)
        ->postJson('/api/v2/media/upload', ['file' => $file])
        ->assertStatus(201);

    $mediaId = $resp->json('id');
    $this->artisan('queue:work', ['--once' => true]);

    $archive = ArchiveFile::findOrFail($mediaId);
    expect($archive->hasPassword())->toBeFalse();

    unlink($zipPath);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/ArchivePasswordTest.php -v
```

Expected: Both tests PASS. If ZipArchive password detection is not yet implemented, this test failure will identify which code to fix before shipping.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/ArchivePasswordTest.php
git commit -m "test: add E2E archive password detection test"
```

---

## Task 9: Family Sharing E2E Test

Scenario: User A creates family → invites User B → User A shares album → User B sees it.

- [ ] **Create `tests/Feature/Integration/FamilySharingTest.php`**

```php
<?php

use App\Models\User;

it('family owner can invite a member', function () {
    $owner  = User::factory()->create();
    $member = User::factory()->create();

    $ownerToken  = $owner->createToken('owner')->plainTextToken;
    $memberToken = $member->createToken('member')->plainTextToken;

    // Create family
    $family = $this->withToken($ownerToken)
        ->postJson('/api/v2/family', ['name' => 'Test Family'])
        ->assertStatus(201)
        ->json();

    // Invite member
    $this->withToken($ownerToken)
        ->postJson('/api/v2/family/members', ['user_id' => $member->id])
        ->assertStatus(200);

    // Member can see the family
    $familyResp = $this->withToken($memberToken)
        ->getJson('/api/v2/family')
        ->assertStatus(200);

    expect($familyResp->json('name'))->toBe('Test Family');
});

it('non-admin cannot access admin endpoints', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v2/admin/users')->assertStatus(403);
    $this->withToken($token)->getJson('/api/v2/admin/stats')->assertStatus(403);
});

it('admin can list all users with quota usage', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $resp = $this->withToken($admin->createToken('admin')->plainTextToken)
        ->getJson('/api/v2/admin/users')
        ->assertStatus(200);

    expect($resp->json('data'))->not->toBeEmpty();
    expect($resp->json('data.0'))->toHaveKeys(['id', 'name', 'email', 'quota_used', 'quota_total']);
});
```

- [ ] **Run the test**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/FamilySharingTest.php -v
```

Expected: All 3 tests PASS.

- [ ] **Commit**

```bash
git add tests/Feature/Integration/FamilySharingTest.php
git commit -m "test: add E2E family sharing and admin endpoint tests"
```

---

## Task 10: Run all integration tests in full suite

- [ ] **Run all Phase 5 integration tests together**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Feature/Integration/ -v
```

Expected: All tests PASS. If any fail, fix the underlying production code before proceeding.

- [ ] **Run full test suite to confirm no regressions**

```bash
docker compose exec laravel-app ./vendor/bin/pest -v
```

Expected: All existing tests + new integration tests PASS.

---

## Task 11: Create documentation files

- [ ] **Create `docs/MOBILE_SETUP.md`**

```markdown
# Mobile App Setup Guide

Avinash EYE Mobile is a native iOS + Android app that backs up your camera roll to your self-hosted server.

## Requirements

- A running Avinash EYE server (see main README)
- iOS 16+ or Android 10+
- The server must be reachable from your phone (same WiFi network, or VPN/Tailscale for remote access)

## Installing on iOS

1. Download `AvinashEYE-2.0.0.ipa` from the [GitHub releases page](https://github.com/coding-sunshine/Avinash-EYE/releases)
2. Install via AltStore or Apple Configurator 2 (sideload)
3. On first launch, tap "Trust" when prompted about the developer

### Installing via TestFlight (if shared by the developer)
1. Install [TestFlight](https://apps.apple.com/app/testflight/id899247664) from the App Store
2. Accept the TestFlight invitation link
3. Install from TestFlight

## Installing on Android

1. Download `AvinashEYE-2.0.0.apk` from the [GitHub releases page](https://github.com/coding-sunshine/Avinash-EYE/releases)
2. On your Android device: Settings → Security → Allow installation from unknown sources
3. Open the downloaded APK and tap Install

## First-Time Setup

1. **Server URL** — Enter your server's local IP and port (e.g., `http://192.168.1.10:8080`). For remote access use your domain or Tailscale IP.
2. **Sign in** — Use your Avinash EYE username and password
3. **Permissions** — Grant access to Photo Library, Notifications, and Background Refresh
4. **Backup Settings** — Choose WiFi only (recommended) and whether to include videos

## Background Sync

- **iOS**: The app registers a background refresh task that runs every 15 minutes minimum (iOS controls exact timing)
- **Android**: Uses WorkManager with a 15-minute periodic task

Photos taken while offline are queued and uploaded automatically when you're back on WiFi.

## Troubleshooting

| Problem | Solution |
|---------|---------|
| "Cannot connect to server" | Verify server is running: `curl http://<ip>:8080` |
| Photos not backing up | Check WiFi only setting, verify background refresh is enabled |
| Login fails | Ensure your server is on API v2 (Phase 2 complete) |
| App crashes on launch | Check iOS/Android version requirements above |
```

- [ ] **Create `docs/DESKTOP_SETUP.md`**

```markdown
# Desktop App Setup Guide

Avinash EYE Desktop is a native Mac/Windows/Linux app that wraps the web interface and adds folder watching and system tray integration.

## System Requirements

- macOS 12 Monterey+ / Windows 10+ / Ubuntu 20.04+
- 200MB disk space for app + Electron runtime
- Your Avinash EYE server running (Docker Compose stack)

## Installation

### macOS
1. Download `AvinashEYE-2.0.0.dmg` from the [GitHub releases page](https://github.com/coding-sunshine/Avinash-EYE/releases)
2. Open the DMG and drag Avinash EYE to Applications
3. First launch: right-click → Open (to bypass Gatekeeper on unsigned builds)
4. Grant Accessibility permission if prompted (needed for tray menu)

### Windows
1. Download `AvinashEYE-2.0.0-Setup.exe`
2. Run the installer — it installs to `%APPDATA%\AvinashEYE`
3. App auto-launches on login (configurable in settings)

### Linux
1. Download `AvinashEYE-2.0.0.AppImage`
2. `chmod +x AvinashEYE-2.0.0.AppImage && ./AvinashEYE-2.0.0.AppImage`
3. To add to application menu: `./AvinashEYE-2.0.0.AppImage --install`

## Configuring Watched Folders

1. Open the app and navigate to **Settings → Watched Folders** (or click the tray icon → Open App)
2. Click **Add Folder** and enter the full path to your photos directory
   - macOS example: `/Users/yourname/Pictures`
   - Windows example: `C:\Users\yourname\Pictures`
3. The app scans watched folders every 60 seconds for new files

### Auto-import rules
- Files smaller than 1KB are ignored
- Hidden files (`.DS_Store`, `Thumbs.db`, etc.) are ignored
- Incomplete downloads (`.tmp`, `.part`, `.crdownload`) are ignored

## System Tray

The tray icon shows:
- **Open Avinash EYE** — brings the window to front
- **Upload Files…** — opens the upload page directly
- **Pause Sync** — temporarily stops folder watching
- **Quit** — exits the app

## Offline Queue

Files that fail to upload (server offline, network interruption) are stored in `~/.avinash-eye/queue.db`. They retry automatically with exponential backoff (max 5 attempts).

## Building from Source

```bash
git clone https://github.com/coding-sunshine/Avinash-EYE
cd Avinash-EYE
composer install
npm install && npm run build
php artisan native:serve       # development
php artisan native:build mac   # macOS DMG
php artisan native:build win   # Windows installer
php artisan native:build linux # Linux AppImage
```
```

- [ ] **Create `docs/MULTI_USER_SETUP.md`**

```markdown
# Multi-User + Family Sharing Setup Guide

Avinash EYE v2.0 supports multiple users with individual storage quotas and optional family sharing.

## User Roles

| Role | Permissions |
|------|------------|
| `admin` | Full access — manage users, view all files, set quotas |
| `member` | Own files only — upload, view, share their own media |

The first user created (via `php artisan user:create-default`) is automatically an admin.

## Inviting New Users

### Via Admin Dashboard (Web)
1. Go to `http://localhost:8080/admin/users`
2. Click **Invite User** → enter email and name
3. The user receives a setup link (or you can set their password directly)

### Via API
```bash
curl -X POST http://localhost:8080/api/v2/users/invite \
  -H "Authorization: Bearer <admin-token>" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "name": "Jane Doe", "password": "SecurePass123!"}'
```

## Storage Quotas

Default quota per user: **100 GB**

### Viewing quota usage
- Admin: `GET /api/v2/admin/users` — lists all users with quota usage
- User: `GET /api/v2/quota` — returns own usage

### Updating a user's quota (admin only)
```bash
curl -X PATCH http://localhost:8080/api/v2/admin/users/2/quota \
  -H "Authorization: Bearer <admin-token>" \
  -H "Content-Type: application/json" \
  -d '{"quota_bytes": 214748364800}'   # 200 GB
```

Common quota values:
- 10 GB: `10737418240`
- 50 GB: `53687091200`
- 100 GB: `107374182400` (default)
- 500 GB: `536870912000`
- 1 TB: `1099511627776`

## Family Sharing

Family sharing lets a group of users see each other's shared albums and optionally contributes to a shared storage pool.

### Creating a family
```bash
curl -X POST http://localhost:8080/api/v2/family \
  -H "Authorization: Bearer <your-token>" \
  -d '{"name": "Smith Family"}'
```

### Inviting a member
```bash
curl -X POST http://localhost:8080/api/v2/family/members \
  -H "Authorization: Bearer <owner-token>" \
  -d '{"user_id": 3}'
```

### Removing a member
```bash
curl -X DELETE http://localhost:8080/api/v2/family/members/3 \
  -H "Authorization: Bearer <owner-token>"
```

## Data Isolation

- Each user can **only see their own files** — even admins must explicitly request `?scope=all`
- File paths are prefixed per user: `storage/app/users/{user_id}/`
- pgvector semantic search is scoped by `user_id` — searches never cross user boundaries

## Shareable Links

Any user can generate a shareable link for a file or album:

```bash
curl -X POST http://localhost:8080/api/v2/share-links \
  -H "Authorization: Bearer <your-token>" \
  -d '{
    "resource_type": "media_file",
    "resource_id": 42,
    "password": "optional-password",
    "expires_at": "2026-12-31T23:59:59Z",
    "max_views": 10
  }'
```

The link is: `http://localhost:8080/share/{token}`

Revoke a link:
```bash
curl -X DELETE http://localhost:8080/api/v2/share-links/{token} \
  -H "Authorization: Bearer <your-token>"
```
```

- [ ] **Commit documentation**

```bash
git add docs/MOBILE_SETUP.md docs/DESKTOP_SETUP.md docs/MULTI_USER_SETUP.md
git commit -m "docs: add mobile, desktop, and multi-user setup guides"
```

---

## Task 12: Update CLAUDE.md with v2 architecture

- [ ] **Add v2 section to `CLAUDE.md`**

Find the "## Architecture Overview" section and add after it:

```markdown
## v2.0 Architecture (Added 2026-04-10)

### Three Client Apps

| Client | Location | Technology |
|--------|----------|-----------|
| Web app | Root Laravel project | Livewire 3 + browser |
| Desktop app | Root Laravel project (NativePHP build) | Electron via `nativephp/electron` |
| Mobile app | `mobile-app/AvinashEYE/` | NativePHP Mobile + Capacitor |

All three share the same API layer at `/api/v2/` authenticated with Sanctum tokens.

### New Services (Phase 2)
- `QuotaService` — per-user storage limits with 413 enforcement
- `ShareLinkService` — token-based shareable links with expiry + password
- `ResumableUploadService` — 5MB chunked uploads with offset tracking
- `DeduplicationService` — SHA256 hash check before accepting upload
- `FamilyService` — family group membership and shared album access

### Desktop Services (Phase 3)
- `FolderWatcherService` — JSON-backed list of watched directories
- `OfflineQueueService` — SQLite queue at `~/.avinash-eye/queue.db`
- `NativeAppServiceProvider` — Electron window + system tray setup
- Artisan commands: `desktop:scan`, `desktop:process-queue`

### Mobile App (Phase 4, `mobile-app/AvinashEYE/`)
- Separate minimal Laravel project — no Docker stack, thin client only
- `BackgroundSyncService` — wifi/cellular-aware sync with queue processing
- `OfflineQueueManager` — SQLite upload queue with network-required field
- `ChunkedUploadService` — dedup check + 5MB chunks to API v2
- 5 Capacitor tabs: Photos, Search, Files, People, More

### New API Routes
All under `/api/v2/` — see `routes/api_v2.php` for full list.
Key: auth, family, media upload (standard + resumable), dedup, share links, sync delta, quota, admin.

### WebSocket (Laravel Reverb)
Runs on port 8081 (host) → 8080 (container). Events:
- `MediaProcessed` — AI pipeline complete
- `UploadProgress` — chunked upload progress  
- `SyncComplete` — device sync state updated
- `QuotaWarning` — user at 90% quota
```

- [ ] **Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md with v2.0 architecture (NativePHP apps, API v2, Reverb)"
```

---

## Task 13: Update PROJECT_STATUS_REPORT.md

- [ ] **Replace the existing report with a v2 status summary**

Open `PROJECT_STATUS_REPORT.md` and replace its content:

```markdown
# Avinash EYE — Project Status Report v2.0

**Date**: 2026-04-10  
**Version**: 2.0.0  
**Status**: All items from v1 report resolved. v2.0 implementation complete.

## v1 Issues — All Resolved

| # | Severity | Issue | Status |
|---|---------|-------|--------|
| BUG-1 | Critical | Wrong config key `services.python_ai.url` in ProcessImageAnalysis | ✅ Fixed (Phase 1) |
| BUG-2 | Critical | Range header crash in MediaController | ✅ Fixed (Phase 1) |
| BUG-3 | High | `.env` missing DB/AI variables | ✅ Fixed (Phase 1) |
| BUG-4 | High | `ImageProcessed` event has no listener | ✅ Fixed (Phase 1) |
| BUG-5 | High | Python AI URL inconsistent across files | ✅ Fixed (Phase 1) |
| BUG-6 | Medium | `ArchiveFile::hasPassword()` placeholder | ✅ Fixed (Phase 1) |
| BUG-7 | Medium | `CacheService::getStats()` placeholder | ✅ Fixed (Phase 1) |

## v2.0 New Features — Complete

| Feature | Phase | Status |
|---------|-------|--------|
| Multi-user accounts + isolation | Phase 2 | ✅ Done |
| Storage quotas with enforcement | Phase 2 | ✅ Done |
| Shareable links (expiry, password, view limit) | Phase 2 | ✅ Done |
| Resumable chunked uploads (5MB chunks) | Phase 2 | ✅ Done |
| SHA256 deduplication | Phase 2 | ✅ Done |
| Family sharing | Phase 2 | ✅ Done |
| Laravel Reverb WebSocket | Phase 2 | ✅ Done |
| API v2 layer (all routes) | Phase 2 | ✅ Done |
| NativePHP Desktop (Mac/Win/Linux) | Phase 3 | ✅ Done |
| Desktop folder watching + tray | Phase 3 | ✅ Done |
| Desktop offline queue (SQLite) | Phase 3 | ✅ Done |
| NativePHP Mobile (iOS + Android) | Phase 4 | ✅ Done |
| Camera roll auto-backup | Phase 4 | ✅ Done |
| Background sync (WiFi + offline queue) | Phase 4 | ✅ Done |
| 5-tab mobile UI (Photos/Search/Files/People/More) | Phase 4 | ✅ Done |
| Integration QA (9 E2E scenarios) | Phase 5 | ✅ Done |
| Documentation (Mobile/Desktop/Multi-user guides) | Phase 5 | ✅ Done |

## Test Coverage

| Suite | Tests | Status |
|-------|-------|--------|
| Unit tests (services) | 40+ | ✅ All passing |
| Feature tests (API v2) | 25+ | ✅ All passing |
| Integration tests (E2E) | 20+ | ✅ All passing |

## Build Artifacts

| Platform | File | Status |
|----------|------|--------|
| macOS | `AvinashEYE-2.0.0.dmg` | ✅ Built |
| Windows | `AvinashEYE-2.0.0-Setup.exe` | ✅ Built |
| Linux | `AvinashEYE-2.0.0.AppImage` | ✅ Built |
| iOS | `AvinashEYE-2.0.0.ipa` | ✅ Built |
| Android | `AvinashEYE-2.0.0.apk` | ✅ Built |
```

- [ ] **Commit**

```bash
git add PROJECT_STATUS_REPORT.md
git commit -m "docs: update PROJECT_STATUS_REPORT to v2.0 — all items resolved"
```

---

## Task 14: Update README.md with v2 highlights

- [ ] **Update the top section of README.md**

Find the existing intro section and update/add the v2 highlights block:

```markdown
## What's New in v2.0

- **NativePHP Desktop** — Mac, Windows, Linux native apps with folder watching and system tray
- **NativePHP Mobile** — iOS + Android with automatic camera roll backup
- **Multi-User** — invite users, set storage quotas, family sharing
- **Shareable Links** — password-protected, expiring, view-limited share links
- **Resumable Uploads** — 5MB chunks with automatic resume on interruption
- **Deduplication** — SHA256 check prevents duplicate uploads
- **Real-Time Sync** — Laravel Reverb WebSocket for instant gallery updates across all devices

### Client Apps

| Platform | Install |
|----------|---------|
| Web | Visit `http://localhost:8080` in any browser |
| Desktop (Mac) | [Download .dmg](releases/latest) |
| Desktop (Windows) | [Download .exe installer](releases/latest) |
| Desktop (Linux) | [Download .AppImage](releases/latest) |
| Mobile (iOS) | [Install guide](docs/MOBILE_SETUP.md) |
| Mobile (Android) | [Install guide](docs/MOBILE_SETUP.md) |
```

- [ ] **Commit**

```bash
git add README.md
git commit -m "docs: update README with v2.0 feature highlights and client app table"
```

---

## Task 15: Tag v2.0.0 and create GitHub release

- [ ] **Verify all tests pass one final time**

```bash
docker compose exec laravel-app ./vendor/bin/pest -v
```

Expected: All tests PASS, zero failures.

- [ ] **Create the git tag**

```bash
git tag -a v2.0.0 -m "Avinash EYE v2.0.0

- NativePHP Desktop app (Mac/Windows/Linux)
- NativePHP Mobile app (iOS/Android) — replaces React Native
- API v2: multi-user, family sharing, storage quotas, share links
- Resumable chunked uploads with SHA256 deduplication
- Laravel Reverb WebSocket for real-time sync
- All 7 v1 bugs fixed with regression tests
- 9 E2E integration test scenarios passing"
```

- [ ] **Push tag to origin**

```bash
git push origin v2.0.0
```

- [ ] **Create GitHub release with binaries**

```bash
gh release create v2.0.0 \
  --title "Avinash EYE v2.0.0 — NativePHP Desktop + Mobile" \
  --notes-file docs/superpowers/specs/2026-04-10-avinash-eye-v2-design.md \
  dist/AvinashEYE-2.0.0.dmg \
  dist/AvinashEYE-2.0.0-Setup.exe \
  dist/AvinashEYE-2.0.0.AppImage \
  mobile-app/AvinashEYE/AvinashEYE-2.0.0.ipa \
  mobile-app/AvinashEYE/AvinashEYE-2.0.0.apk
```

- [ ] **Verify the release on GitHub**

```bash
gh release view v2.0.0
```

Expected: All 5 binaries attached, release notes visible.

---

## Phase 5 Complete Checklist

- [ ] Full sync loop E2E test passes (upload → AI pipeline → gallery appears)
- [ ] Resumable upload test passes (3-chunk assembly + resume from offset)
- [ ] Dedup E2E test passes (second upload returns existing ID)
- [ ] Multi-user isolation test passes (User A cannot see User B's files)
- [ ] Share link test passes (expiry, wrong password, max views, revoke)
- [ ] Quota enforcement test passes (99%, 100%, over limit, admin update)
- [ ] Desktop folder watch test passes (new file → queued in one scan cycle)
- [ ] Archive password detection test passes
- [ ] Family sharing + admin endpoint test passes
- [ ] Full test suite (`./vendor/bin/pest`) — zero failures
- [ ] `docs/MOBILE_SETUP.md` created
- [ ] `docs/DESKTOP_SETUP.md` created
- [ ] `docs/MULTI_USER_SETUP.md` created
- [ ] `CLAUDE.md` updated with v2 architecture
- [ ] `PROJECT_STATUS_REPORT.md` updated to v2 (all items resolved)
- [ ] `README.md` updated with v2 highlights
- [ ] `v2.0.0` tag pushed to origin
- [ ] GitHub release created with all 5 binaries attached
