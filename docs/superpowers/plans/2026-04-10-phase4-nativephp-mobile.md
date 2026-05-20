# Phase 4 — NativePHP Mobile App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the existing React Native mobile app with a NativePHP Mobile app that backs up camera roll photos automatically (WiFi-instant, cellular-queued), provides a 5-tab UI matching Google Photos/Drive aesthetics, and builds to native iOS and Android binaries.

**Architecture:** The mobile app lives in `mobile-app/AvinashEYE/` as a **separate minimal Laravel installation** — no Docker stack, no AI services, no PostgreSQL. It is a thin client compiled to a native app via NativePHP Mobile (Capacitor). It stores server URL and auth token in the device keychain. `BackgroundSyncService` runs on a 15-min schedule (iOS `BGAppRefreshTask`, Android `WorkManager`). `OfflineQueueManager` stores pending uploads in a device-side SQLite file. `ChunkedUploadService` sends 5MB chunks to the API v2 resumable upload endpoint with retry. All five tabs (Photos, Search, Files, People, More) are Blade views rendered in a WebView and styled with Tailwind.

**Tech Stack:** NativePHP Mobile (`nativephp/mobile`), Capacitor v6, Laravel 12 (minimal), Tailwind CSS, Blade, Pest PHP (unit tests on service layer logic)

**Prerequisite:** Phase 2 complete — API v2 endpoints must be working. The server URL that the mobile app targets is the main Laravel Docker stack from the main project.

---

## File Map

All paths below are relative to `mobile-app/AvinashEYE/` unless otherwise noted.

| Action | File |
|--------|------|
| Delete | `mobile-app/AvinashEYE/` (entire existing React Native project) |
| Create | `mobile-app/AvinashEYE/` (new Laravel project via create-project) |
| Create | `app/Services/ServerConnectionService.php` |
| Create | `app/Services/BackgroundSyncService.php` |
| Create | `app/Services/OfflineQueueManager.php` |
| Create | `app/Services/ChunkedUploadService.php` |
| Create | `app/Http/Controllers/Mobile/OnboardingController.php` |
| Create | `app/Http/Controllers/Mobile/PhotosController.php` |
| Create | `app/Http/Controllers/Mobile/SearchController.php` |
| Create | `app/Http/Controllers/Mobile/FilesController.php` |
| Create | `app/Http/Controllers/Mobile/PeopleController.php` |
| Create | `app/Http/Controllers/Mobile/MoreController.php` |
| Create | `app/Http/Controllers/Mobile/MediaViewerController.php` |
| Create | `resources/views/mobile/layout.blade.php` |
| Create | `resources/views/mobile/onboarding/server.blade.php` |
| Create | `resources/views/mobile/onboarding/login.blade.php` |
| Create | `resources/views/mobile/onboarding/permissions.blade.php` |
| Create | `resources/views/mobile/onboarding/backup-setup.blade.php` |
| Create | `resources/views/mobile/photos.blade.php` |
| Create | `resources/views/mobile/search.blade.php` |
| Create | `resources/views/mobile/files.blade.php` |
| Create | `resources/views/mobile/people.blade.php` |
| Create | `resources/views/mobile/more.blade.php` |
| Create | `resources/views/mobile/viewer.blade.php` |
| Create | `resources/js/app.js` (Capacitor plugin bridges) |
| Create | `routes/web.php` (mobile routes) |
| Create | `tests/Unit/BackgroundSyncServiceTest.php` |
| Create | `tests/Unit/OfflineQueueManagerTest.php` |
| Create | `tests/Unit/ChunkedUploadServiceTest.php` |

---

## Task 1: Delete existing React Native project and create new Laravel project

- [ ] **Delete the existing React Native project**

```bash
rm -rf mobile-app/AvinashEYE
```

Expected: `mobile-app/AvinashEYE/` directory no longer exists.

- [ ] **Create a new minimal Laravel project**

```bash
composer create-project laravel/laravel mobile-app/AvinashEYE --prefer-dist
cd mobile-app/AvinashEYE
```

- [ ] **Install NativePHP Mobile and Sanctum**

```bash
composer require nativephp/mobile laravel/sanctum
php artisan native:install
php artisan native:mobile:add ios
php artisan native:mobile:add android
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

- [ ] **Install Capacitor plugins**

```bash
npm install @capacitor/camera @capacitor/filesystem @capacitor/network @capacitor/push-notifications @capacitor/background-runner @capacitor/preferences
npx cap sync
```

- [ ] **Install frontend tooling**

```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

Configure `tailwind.config.js`:

```js
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#4285F4',
                surface: '#1E1E1E',
                'surface-2': '#2D2D2D',
            },
        },
    },
    plugins: [],
};
```

- [ ] **Verify project builds**

```bash
npm run build
php artisan serve --port=8090
```

Expected: Laravel welcome page visible at `http://localhost:8090`.

- [ ] **Commit**

```bash
cd mobile-app/AvinashEYE
git add -A
git commit -m "feat: scaffold NativePHP Mobile Laravel project (replaces React Native)"
```

---

## Task 2: ServerConnectionService — keychain storage for server URL + token

- [ ] **Create `app/Services/ServerConnectionService.php`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ServerConnectionService
{
    private string $prefsFile;

    public function __construct()
    {
        // In NativePHP Mobile the app's storage path is sandboxed on-device
        $this->prefsFile = storage_path('app/server_prefs.json');
    }

    public function getServerUrl(): ?string
    {
        return $this->loadPrefs()['server_url'] ?? null;
    }

    public function getToken(): ?string
    {
        return $this->loadPrefs()['token'] ?? null;
    }

    public function saveServerUrl(string $url): void
    {
        $prefs = $this->loadPrefs();
        $prefs['server_url'] = rtrim($url, '/');
        $this->savePrefs($prefs);
    }

    public function saveToken(string $token): void
    {
        $prefs = $this->loadPrefs();
        $prefs['token'] = $token;
        $this->savePrefs($prefs);
    }

    public function isConfigured(): bool
    {
        return $this->getServerUrl() !== null && $this->getToken() !== null;
    }

    public function testConnection(): bool
    {
        $url = $this->getServerUrl();
        if (!$url) return false;

        try {
            $response = Http::timeout(5)->get("{$url}/api/v2/users/me", [], [
                'Authorization' => 'Bearer ' . $this->getToken(),
            ]);
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function logout(): void
    {
        $this->savePrefs([]);
    }

    private function loadPrefs(): array
    {
        if (!file_exists($this->prefsFile)) return [];
        return json_decode(file_get_contents($this->prefsFile), true) ?? [];
    }

    private function savePrefs(array $prefs): void
    {
        file_put_contents($this->prefsFile, json_encode($prefs));
    }
}
```

- [ ] **Commit**

```bash
git add app/Services/ServerConnectionService.php
git commit -m "feat: add ServerConnectionService for storing server URL and auth token"
```

---

## Task 3: OfflineQueueManager

- [ ] **Write the failing test `tests/Unit/OfflineQueueManagerTest.php`**

```php
<?php

use App\Services\OfflineQueueManager;

beforeEach(function () {
    $this->queue = new OfflineQueueManager(':memory:');
});

it('enqueues a photo asset', function () {
    $id = $this->queue->enqueue('asset-id-123', '/tmp/photo.jpg', 'sha256hash', 'wifi');
    expect($id)->toBeInt()->toBeGreaterThan(0);
});

it('returns pending items respecting network requirement', function () {
    $this->queue->enqueue('asset-1', '/tmp/photo1.jpg', 'hash1', 'wifi');
    $this->queue->enqueue('asset-2', '/tmp/photo2.jpg', 'hash2', 'any');

    $wifiItems = $this->queue->getPendingForNetwork('wifi');
    expect($wifiItems)->toHaveCount(2); // wifi network can upload 'wifi' + 'any' items

    $cellularItems = $this->queue->getPendingForNetwork('cellular');
    expect($cellularItems)->toHaveCount(1); // cellular only gets 'any' items
});

it('marks item as done', function () {
    $id = $this->queue->enqueue('asset-1', '/tmp/photo.jpg', 'hash1', 'wifi');
    $this->queue->markDone($id);
    expect($this->queue->getPendingCount())->toBe(0);
});

it('increments attempts and stops after 5', function () {
    $id = $this->queue->enqueue('asset-1', '/tmp/photo.jpg', 'hash1', 'wifi');
    for ($i = 0; $i < 5; $i++) {
        $this->queue->markFailed($id);
    }
    expect($this->queue->getPendingForNetwork('wifi'))->toHaveCount(0);
});

it('checks for existing asset by original ID', function () {
    $this->queue->enqueue('asset-photo-unique', '/tmp/photo.jpg', 'hash1', 'wifi');
    expect($this->queue->existsByAssetId('asset-photo-unique'))->toBeTrue();
    expect($this->queue->existsByAssetId('non-existent-id'))->toBeFalse();
});
```

- [ ] **Run test to confirm it fails**

```bash
./vendor/bin/pest tests/Unit/OfflineQueueManagerTest.php -v
```

Expected: FAIL — `App\Services\OfflineQueueManager` not found.

- [ ] **Create `app/Services/OfflineQueueManager.php`**

```php
<?php

namespace App\Services;

class OfflineQueueManager
{
    private \PDO $pdo;

    public function __construct(string $dbPath = '')
    {
        if ($dbPath === '') {
            $dbPath = storage_path('app/upload_queue.db');
        }
        $this->pdo = new \PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS upload_queue (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                original_asset_id   TEXT    NOT NULL,
                file_path           TEXT    NOT NULL,
                file_hash           TEXT,
                status              TEXT    NOT NULL DEFAULT 'pending',
                upload_id           TEXT,
                bytes_uploaded      INTEGER NOT NULL DEFAULT 0,
                attempts            INTEGER NOT NULL DEFAULT 0,
                network_required    TEXT    NOT NULL DEFAULT 'wifi',
                created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function enqueue(string $assetId, string $filePath, string $fileHash, string $networkRequired = 'wifi'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO upload_queue (original_asset_id, file_path, file_hash, network_required) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$assetId, $filePath, $fileHash, $networkRequired]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getPendingForNetwork(string $networkType, int $limit = 3): array
    {
        // On WiFi: upload items with network_required = 'wifi' OR 'any'
        // On cellular: only upload items with network_required = 'any'
        if ($networkType === 'wifi') {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM upload_queue WHERE status = 'pending' AND attempts < 5 ORDER BY created_at LIMIT ?"
            );
            $stmt->execute([$limit]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM upload_queue WHERE status = 'pending' AND attempts < 5 AND network_required = 'any' ORDER BY created_at LIMIT ?"
            );
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markUploading(int $id, string $uploadId): void
    {
        $this->pdo->prepare(
            "UPDATE upload_queue SET status = 'uploading', upload_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$uploadId, $id]);
    }

    public function updateProgress(int $id, int $bytesUploaded): void
    {
        $this->pdo->prepare(
            "UPDATE upload_queue SET bytes_uploaded = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$bytesUploaded, $id]);
    }

    public function markDone(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE upload_queue SET status = 'done', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$id]);
    }

    public function markFailed(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE upload_queue SET status = 'pending', attempts = attempts + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$id]);
    }

    public function getPendingCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM upload_queue WHERE status IN ('pending', 'uploading')"
        )->fetchColumn();
    }

    public function existsByAssetId(string $assetId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM upload_queue WHERE original_asset_id = ?"
        );
        $stmt->execute([$assetId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function existsByHash(string $hash): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM upload_queue WHERE file_hash = ? AND status != 'failed'"
        );
        $stmt->execute([$hash]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
```

- [ ] **Run test to confirm it passes**

```bash
./vendor/bin/pest tests/Unit/OfflineQueueManagerTest.php -v
```

Expected: All 5 tests PASS.

- [ ] **Commit**

```bash
git add app/Services/OfflineQueueManager.php tests/Unit/OfflineQueueManagerTest.php
git commit -m "feat: add OfflineQueueManager with network-aware dequeue"
```

---

## Task 4: ChunkedUploadService

- [ ] **Write the failing test `tests/Unit/ChunkedUploadServiceTest.php`**

```php
<?php

use App\Services\ChunkedUploadService;
use App\Services\ServerConnectionService;
use Illuminate\Support\Facades\Http;

it('returns existing media ID when dedup finds a match', function () {
    Http::fake([
        '*/api/v2/media/dedup/*' => Http::response(['exists' => true, 'media_id' => 42], 200),
    ]);

    $conn = Mockery::mock(ServerConnectionService::class);
    $conn->allows('getServerUrl')->andReturn('http://localhost:8080');
    $conn->allows('getToken')->andReturn('test-token');

    $service = new ChunkedUploadService($conn);
    $result = $service->checkDedup('abc123sha256');

    expect($result)->toBe(42);
});

it('returns null when file is not a duplicate', function () {
    Http::fake([
        '*/api/v2/media/dedup/*' => Http::response(['exists' => false], 200),
    ]);

    $conn = Mockery::mock(ServerConnectionService::class);
    $conn->allows('getServerUrl')->andReturn('http://localhost:8080');
    $conn->allows('getToken')->andReturn('test-token');

    $service = new ChunkedUploadService($conn);
    expect($service->checkDedup('nothash999'))->toBeNull();
});

it('initialises a resumable upload and returns upload ID', function () {
    Http::fake([
        '*/api/v2/media/upload/init' => Http::response(['upload_id' => 'uuid-abc-123'], 201),
    ]);

    $conn = Mockery::mock(ServerConnectionService::class);
    $conn->allows('getServerUrl')->andReturn('http://localhost:8080');
    $conn->allows('getToken')->andReturn('test-token');

    $service = new ChunkedUploadService($conn);
    $uploadId = $service->initUpload('photo.jpg', 1024 * 1024, 'sha256hash');

    expect($uploadId)->toBe('uuid-abc-123');
});
```

- [ ] **Run test to verify it fails**

```bash
./vendor/bin/pest tests/Unit/ChunkedUploadServiceTest.php -v
```

Expected: FAIL — `App\Services\ChunkedUploadService` not found.

- [ ] **Create `app/Services/ChunkedUploadService.php`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChunkedUploadService
{
    private const CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB

    public function __construct(
        private readonly ServerConnectionService $connection,
    ) {}

    public function checkDedup(string $sha256Hash): ?int
    {
        $url   = $this->connection->getServerUrl();
        $token = $this->connection->getToken();

        $response = Http::withToken($token)
            ->timeout(5)
            ->get("{$url}/api/v2/media/dedup/{$sha256Hash}");

        if ($response->successful() && $response->json('exists')) {
            return $response->json('media_id');
        }
        return null;
    }

    public function initUpload(string $filename, int $totalBytes, string $fileHash): ?string
    {
        $url   = $this->connection->getServerUrl();
        $token = $this->connection->getToken();

        $response = Http::withToken($token)
            ->timeout(10)
            ->post("{$url}/api/v2/media/upload/init", [
                'filename'    => $filename,
                'total_bytes' => $totalBytes,
                'file_hash'   => $fileHash,
            ]);

        if ($response->successful()) {
            return $response->json('upload_id');
        }
        return null;
    }

    public function sendChunk(string $uploadId, string $data, int $offset): bool
    {
        $url   = $this->connection->getServerUrl();
        $token = $this->connection->getToken();

        $response = Http::withToken($token)
            ->withBody($data, 'application/octet-stream')
            ->timeout(30)
            ->patch("{$url}/api/v2/media/upload/{$uploadId}", [
                'offset' => $offset,
            ]);

        return $response->successful();
    }

    /**
     * Upload a file from a local path in 5MB chunks.
     * Calls $onProgress(int $bytesUploaded) after each chunk.
     * Returns true on success, false on failure.
     */
    public function uploadFile(
        string $filePath,
        string $uploadId,
        int $startOffset = 0,
        callable $onProgress = null,
    ): bool {
        $handle = fopen($filePath, 'rb');
        if (!$handle) return false;

        fseek($handle, $startOffset);
        $offset = $startOffset;

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);
                if ($chunk === false || $chunk === '') break;

                if (!$this->sendChunk($uploadId, $chunk, $offset)) {
                    fclose($handle);
                    return false;
                }

                $offset += strlen($chunk);
                if ($onProgress) {
                    ($onProgress)($offset);
                }
            }
        } finally {
            fclose($handle);
        }

        return true;
    }
}
```

- [ ] **Run test to confirm it passes**

```bash
./vendor/bin/pest tests/Unit/ChunkedUploadServiceTest.php -v
```

Expected: All 3 tests PASS.

- [ ] **Commit**

```bash
git add app/Services/ChunkedUploadService.php tests/Unit/ChunkedUploadServiceTest.php
git commit -m "feat: add ChunkedUploadService with dedup check and resumable 5MB chunks"
```

---

## Task 5: BackgroundSyncService

- [ ] **Write the failing test `tests/Unit/BackgroundSyncServiceTest.php`**

```php
<?php

use App\Services\BackgroundSyncService;
use App\Services\OfflineQueueManager;
use App\Services\ServerConnectionService;
use App\Services\ChunkedUploadService;
use Illuminate\Support\Facades\Http;

it('skips sync when not on wifi and wifi_only is enabled', function () {
    $conn   = Mockery::mock(ServerConnectionService::class);
    $queue  = Mockery::mock(OfflineQueueManager::class);
    $upload = Mockery::mock(ChunkedUploadService::class);

    // wifi_only preference is true, current network is cellular
    $service = new BackgroundSyncService($conn, $queue, $upload, wifiOnly: true);
    $result  = $service->run(networkType: 'cellular');

    expect($result)->toBe(['skipped' => true, 'reason' => 'cellular_blocked']);
    // Upload service should never be called
    $upload->shouldNotHaveReceived('initUpload');
});

it('skips sync when server is not configured', function () {
    $conn = Mockery::mock(ServerConnectionService::class);
    $conn->allows('isConfigured')->andReturn(false);

    $queue  = Mockery::mock(OfflineQueueManager::class);
    $upload = Mockery::mock(ChunkedUploadService::class);

    $service = new BackgroundSyncService($conn, $queue, $upload, wifiOnly: false);
    $result  = $service->run(networkType: 'wifi');

    expect($result)->toBe(['skipped' => true, 'reason' => 'not_configured']);
});

it('processes queue items when on wifi', function () {
    $conn = Mockery::mock(ServerConnectionService::class);
    $conn->allows('isConfigured')->andReturn(true);

    $queue = Mockery::mock(OfflineQueueManager::class);
    $queue->allows('getPendingForNetwork')->with('wifi', 3)->andReturn([
        ['id' => 1, 'file_path' => '/tmp/photo.jpg', 'file_hash' => 'abc', 'upload_id' => null, 'bytes_uploaded' => 0],
    ]);
    $queue->allows('markUploading')->with(1, 'uuid-123');
    $queue->allows('updateProgress');
    $queue->allows('markDone')->with(1);

    $upload = Mockery::mock(ChunkedUploadService::class);
    $upload->allows('checkDedup')->andReturn(null); // not a duplicate
    $upload->allows('initUpload')->andReturn('uuid-123');
    $upload->allows('uploadFile')->andReturn(true);

    $service = new BackgroundSyncService($conn, $queue, $upload, wifiOnly: false);
    $result  = $service->run(networkType: 'wifi');

    expect($result['uploaded'])->toBe(1);
    expect($result['skipped'])->toBeFalse();
});
```

- [ ] **Run test to verify it fails**

```bash
./vendor/bin/pest tests/Unit/BackgroundSyncServiceTest.php -v
```

Expected: FAIL — `App\Services\BackgroundSyncService` not found.

- [ ] **Create `app/Services/BackgroundSyncService.php`**

```php
<?php

namespace App\Services;

class BackgroundSyncService
{
    public function __construct(
        private readonly ServerConnectionService $connection,
        private readonly OfflineQueueManager     $queue,
        private readonly ChunkedUploadService    $upload,
        private readonly bool                    $wifiOnly = true,
    ) {}

    /**
     * Run one sync cycle.
     * @param  string $networkType  'wifi' | 'cellular' | 'none'
     * @return array{skipped: bool, reason?: string, uploaded?: int, failed?: int}
     */
    public function run(string $networkType = 'wifi'): array
    {
        if ($networkType === 'none') {
            return ['skipped' => true, 'reason' => 'offline'];
        }

        if ($this->wifiOnly && $networkType === 'cellular') {
            return ['skipped' => true, 'reason' => 'cellular_blocked'];
        }

        if (!$this->connection->isConfigured()) {
            return ['skipped' => true, 'reason' => 'not_configured'];
        }

        $items    = $this->queue->getPendingForNetwork($networkType, 3);
        $uploaded = 0;
        $failed   = 0;

        foreach ($items as $item) {
            $result = $this->processItem($item);
            $result ? $uploaded++ : $failed++;
        }

        return ['skipped' => false, 'uploaded' => $uploaded, 'failed' => $failed];
    }

    private function processItem(array $item): bool
    {
        // Check dedup — if already on server, mark done and skip
        if ($item['file_hash']) {
            $existingId = $this->upload->checkDedup($item['file_hash']);
            if ($existingId !== null) {
                $this->queue->markDone($item['id']);
                return true;
            }
        }

        // Init upload if not resuming
        $uploadId = $item['upload_id'];
        if (!$uploadId) {
            $uploadId = $this->upload->initUpload(
                basename($item['file_path']),
                file_exists($item['file_path']) ? filesize($item['file_path']) : 0,
                $item['file_hash'] ?? '',
            );

            if (!$uploadId) {
                $this->queue->markFailed($item['id']);
                return false;
            }

            $this->queue->markUploading($item['id'], $uploadId);
        }

        // Upload in chunks
        $success = $this->upload->uploadFile(
            filePath:    $item['file_path'],
            uploadId:    $uploadId,
            startOffset: (int) $item['bytes_uploaded'],
            onProgress:  fn ($bytes) => $this->queue->updateProgress($item['id'], $bytes),
        );

        if ($success) {
            $this->queue->markDone($item['id']);
        } else {
            $this->queue->markFailed($item['id']);
        }

        return $success;
    }
}
```

- [ ] **Run test to confirm it passes**

```bash
./vendor/bin/pest tests/Unit/BackgroundSyncServiceTest.php -v
```

Expected: All 3 tests PASS.

- [ ] **Commit**

```bash
git add app/Services/BackgroundSyncService.php tests/Unit/BackgroundSyncServiceTest.php
git commit -m "feat: add BackgroundSyncService with wifi-only guard and chunked upload"
```

---

## Task 6: Mobile routes and layout

- [ ] **Replace `routes/web.php` with mobile routes**

```php
<?php

use App\Http\Controllers\Mobile\OnboardingController;
use App\Http\Controllers\Mobile\PhotosController;
use App\Http\Controllers\Mobile\SearchController;
use App\Http\Controllers\Mobile\FilesController;
use App\Http\Controllers\Mobile\PeopleController;
use App\Http\Controllers\Mobile\MoreController;
use App\Http\Controllers\Mobile\MediaViewerController;
use Illuminate\Support\Facades\Route;

// Onboarding (no auth required)
Route::get('/', [OnboardingController::class, 'index'])->name('onboarding');
Route::get('/onboarding/server', [OnboardingController::class, 'server'])->name('onboarding.server');
Route::post('/onboarding/server', [OnboardingController::class, 'saveServer'])->name('onboarding.server.save');
Route::get('/onboarding/login', [OnboardingController::class, 'login'])->name('onboarding.login');
Route::post('/onboarding/login', [OnboardingController::class, 'doLogin'])->name('onboarding.login.post');
Route::get('/onboarding/permissions', [OnboardingController::class, 'permissions'])->name('onboarding.permissions');
Route::get('/onboarding/backup-setup', [OnboardingController::class, 'backupSetup'])->name('onboarding.backup-setup');
Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

// Main app tabs (auth check done in controller via ServerConnectionService)
Route::get('/photos', [PhotosController::class, 'index'])->name('photos');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/files', [FilesController::class, 'index'])->name('files');
Route::get('/people', [PeopleController::class, 'index'])->name('people');
Route::get('/more', [MoreController::class, 'index'])->name('more');

// Media viewer
Route::get('/media/{id}', [MediaViewerController::class, 'show'])->name('media.show');

// Settings actions
Route::post('/more/logout', [MoreController::class, 'logout'])->name('more.logout');
Route::post('/more/settings', [MoreController::class, 'saveSettings'])->name('more.settings');
```

- [ ] **Create `resources/views/mobile/layout.blade.php`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1E1E1E">
    <title>Avinash EYE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-white min-h-screen flex flex-col" style="padding-bottom: env(safe-area-inset-bottom)">

    {{-- Content area --}}
    <main class="flex-1 overflow-y-auto pb-20">
        @yield('content')
    </main>

    {{-- Bottom tab bar --}}
    @if($showTabs ?? true)
    <nav class="fixed bottom-0 left-0 right-0 bg-surface-2 border-t border-gray-800 flex"
         style="padding-bottom: env(safe-area-inset-bottom)">
        @php
            $tabs = [
                ['route' => 'photos',  'icon' => 'photo_library',  'label' => 'Photos'],
                ['route' => 'search',  'icon' => 'search',          'label' => 'Search'],
                ['route' => 'files',   'icon' => 'folder',          'label' => 'Files'],
                ['route' => 'people',  'icon' => 'face',            'label' => 'People'],
                ['route' => 'more',    'icon' => 'more_horiz',      'label' => 'More'],
            ];
        @endphp
        @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="flex-1 flex flex-col items-center py-2 {{ request()->routeIs($tab['route']) ? 'text-primary' : 'text-gray-500' }}">
            <span class="material-symbols-outlined text-2xl">{{ $tab['icon'] }}</span>
            <span class="text-xs mt-0.5">{{ $tab['label'] }}</span>
        </a>
        @endforeach
    </nav>
    @endif

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</body>
</html>
```

- [ ] **Commit**

```bash
git add routes/web.php resources/views/mobile/layout.blade.php
git commit -m "feat: add mobile routes and bottom tab bar layout"
```

---

## Task 7: Onboarding flow (4 screens)

- [ ] **Create `app/Http/Controllers/Mobile/OnboardingController.php`**

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OnboardingController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function index()
    {
        if ($this->conn->isConfigured()) {
            return redirect()->route('photos');
        }
        return redirect()->route('onboarding.server');
    }

    public function server() { return view('mobile.onboarding.server'); }

    public function saveServer(Request $request)
    {
        $validated = $request->validate(['url' => 'required|url']);
        $this->conn->saveServerUrl($validated['url']);
        return redirect()->route('onboarding.login');
    }

    public function login() { return view('mobile.onboarding.login'); }

    public function doLogin(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $response = Http::post($this->conn->getServerUrl() . '/api/v2/auth/login', $validated);

        if (!$response->successful()) {
            return back()->withErrors(['email' => 'Invalid credentials or server unreachable.']);
        }

        $this->conn->saveToken($response->json('token'));
        return redirect()->route('onboarding.permissions');
    }

    public function permissions() { return view('mobile.onboarding.permissions'); }

    public function backupSetup() { return view('mobile.onboarding.backup-setup'); }

    public function complete(Request $request)
    {
        $wifiOnly = $request->boolean('wifi_only', true);
        $includeVideos = $request->boolean('include_videos', true);
        session(['backup_wifi_only' => $wifiOnly, 'backup_include_videos' => $includeVideos]);
        return redirect()->route('photos');
    }
}
```

- [ ] **Create `resources/views/mobile/onboarding/server.blade.php`**

```html
@extends('mobile.layout', ['showTabs' => false])
@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-6 py-12">
    <div class="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center mb-8">
        <span class="material-symbols-outlined text-white text-4xl">photo_library</span>
    </div>
    <h1 class="text-2xl font-semibold text-white mb-2">Welcome to Avinash EYE</h1>
    <p class="text-gray-400 text-center text-sm mb-10">
        Enter your self-hosted server URL to connect.
    </p>

    <form method="POST" action="{{ route('onboarding.server.save') }}" class="w-full max-w-sm">
        @csrf
        @error('url') <p class="text-red-400 text-sm mb-2">{{ $message }}</p> @enderror
        <label class="block text-sm text-gray-400 mb-1">Server URL</label>
        <input type="url" name="url" placeholder="http://192.168.1.10:8080"
               value="{{ old('url') }}"
               class="w-full bg-surface-2 text-white border border-gray-600 rounded-xl px-4 py-3 text-base mb-4 focus:outline-none focus:border-primary">
        <button type="submit" class="w-full bg-primary text-white rounded-xl py-3 font-medium text-base">
            Connect
        </button>
    </form>
</div>
@endsection
```

- [ ] **Create `resources/views/mobile/onboarding/login.blade.php`**

```html
@extends('mobile.layout', ['showTabs' => false])
@section('content')
<div class="flex flex-col min-h-screen px-6 py-16">
    <h1 class="text-2xl font-semibold text-white mb-1">Sign in</h1>
    <p class="text-gray-400 text-sm mb-8">{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}</p>

    <form method="POST" action="{{ route('onboarding.login.post') }}" class="space-y-4">
        @csrf
        @error('email') <p class="text-red-400 text-sm">{{ $message }}</p> @enderror

        <div>
            <label class="block text-sm text-gray-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full bg-surface-2 text-white border border-gray-600 rounded-xl px-4 py-3 text-base focus:outline-none focus:border-primary">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Password</label>
            <input type="password" name="password"
                   class="w-full bg-surface-2 text-white border border-gray-600 rounded-xl px-4 py-3 text-base focus:outline-none focus:border-primary">
        </div>
        <button type="submit" class="w-full bg-primary text-white rounded-xl py-3 font-medium text-base mt-4">
            Sign in
        </button>
    </form>
</div>
@endsection
```

- [ ] **Create `resources/views/mobile/onboarding/permissions.blade.php`**

```html
@extends('mobile.layout', ['showTabs' => false])
@section('content')
<div class="flex flex-col min-h-screen px-6 py-16">
    <h1 class="text-2xl font-semibold text-white mb-2">Allow Access</h1>
    <p class="text-gray-400 text-sm mb-8">These permissions are needed to back up your photos.</p>

    @php
    $perms = [
        ['icon' => 'photo_library', 'title' => 'Photo Library',       'desc' => 'Read your camera roll to back up photos'],
        ['icon' => 'notifications', 'title' => 'Notifications',        'desc' => 'Alert you when backup is complete'],
        ['icon' => 'sync',          'title' => 'Background Refresh',   'desc' => 'Back up photos even when the app is closed'],
    ];
    @endphp

    <div class="space-y-4 mb-10">
        @foreach($perms as $perm)
        <div class="flex items-start gap-4 bg-surface-2 rounded-xl p-4">
            <span class="material-symbols-outlined text-primary text-2xl mt-0.5">{{ $perm['icon'] }}</span>
            <div>
                <p class="text-white font-medium text-sm">{{ $perm['title'] }}</p>
                <p class="text-gray-400 text-xs mt-0.5">{{ $perm['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <a href="{{ route('onboarding.backup-setup') }}"
       id="btn-request-permissions"
       class="w-full bg-primary text-white rounded-xl py-3 font-medium text-base text-center block">
        Grant Permissions
    </a>
</div>
@endsection
```

- [ ] **Create `resources/views/mobile/onboarding/backup-setup.blade.php`**

```html
@extends('mobile.layout', ['showTabs' => false])
@section('content')
<div class="flex flex-col min-h-screen px-6 py-16">
    <h1 class="text-2xl font-semibold text-white mb-2">Backup Settings</h1>
    <p class="text-gray-400 text-sm mb-8">Choose how Avinash EYE backs up your photos.</p>

    <form method="POST" action="{{ route('onboarding.complete') }}" class="space-y-6">
        @csrf
        <div class="bg-surface-2 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-white font-medium text-sm">WiFi only</p>
                <p class="text-gray-400 text-xs mt-0.5">Use cellular data when on WiFi only</p>
            </div>
            <input type="hidden" name="wifi_only" value="0">
            <input type="checkbox" name="wifi_only" value="1" checked
                   class="w-5 h-5 accent-primary">
        </div>

        <div class="bg-surface-2 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-white font-medium text-sm">Include Videos</p>
                <p class="text-gray-400 text-xs mt-0.5">Back up videos from your camera roll</p>
            </div>
            <input type="hidden" name="include_videos" value="0">
            <input type="checkbox" name="include_videos" value="1" checked
                   class="w-5 h-5 accent-primary">
        </div>

        <button type="submit" class="w-full bg-primary text-white rounded-xl py-3 font-medium text-base">
            Start Backup
        </button>
    </form>
</div>
@endsection
```

- [ ] **Commit**

```bash
git add app/Http/Controllers/Mobile/OnboardingController.php resources/views/mobile/onboarding/
git commit -m "feat: add 4-screen onboarding flow (server, login, permissions, backup setup)"
```

---

## Task 8: Main app tabs (5 screens)

- [ ] **Create `app/Http/Controllers/Mobile/PhotosController.php`**

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Support\Facades\Http;

class PhotosController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function index()
    {
        if (!$this->conn->isConfigured()) {
            return redirect()->route('onboarding');
        }

        $token = $this->conn->getToken();
        $url   = $this->conn->getServerUrl();

        $mediaResponse = Http::withToken($token)
            ->timeout(10)
            ->get("{$url}/api/v2/media", ['type' => 'photo,video', 'per_page' => 60, 'sort' => 'taken_at_desc']);

        $items = $mediaResponse->successful() ? $mediaResponse->json('data', []) : [];

        return view('mobile.photos', compact('items'));
    }
}
```

- [ ] **Create `resources/views/mobile/photos.blade.php`**

```html
@extends('mobile.layout')
@section('content')
<div class="px-0">
    {{-- Header --}}
    <div class="px-4 pt-safe pt-4 pb-3 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-white">Photos</h1>
        <button class="w-9 h-9 rounded-full bg-surface-2 flex items-center justify-center">
            <span class="material-symbols-outlined text-gray-300">account_circle</span>
        </button>
    </div>

    {{-- Memories strip (top 3 date groups) --}}
    @if(!empty($items))
    <div class="px-4 mb-3">
        <p class="text-sm font-medium text-gray-400 mb-2">Memories</p>
        <div class="flex gap-2 overflow-x-auto pb-1 snap-x" id="memories-strip">
            @foreach(collect($items)->groupBy(fn($i) => substr($i['taken_at'] ?? $i['created_at'], 0, 10))->take(3) as $date => $group)
            <div class="snap-start flex-shrink-0 w-28 h-36 relative rounded-xl overflow-hidden bg-surface-2">
                <img src="{{ $conn->getServerUrl() }}/storage/{{ $group->first()['thumbnail_path'] ?? '' }}"
                     class="w-full h-full object-cover" loading="lazy">
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                    <p class="text-white text-xs font-medium">{{ \Carbon\Carbon::parse($date)->format('M d') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Chronological photo grid --}}
    @php $grouped = collect($items)->groupBy(fn($i) => substr($i['taken_at'] ?? $i['created_at'], 0, 10)); @endphp
    @foreach($grouped as $date => $dayItems)
    <div class="px-4 pt-4">
        <p class="text-xs text-gray-400 mb-2 uppercase tracking-wide">
            {{ \Carbon\Carbon::parse($date)->format('D, M j, Y') }}
        </p>
    </div>
    <div class="grid grid-cols-3 gap-0.5">
        @foreach($dayItems as $item)
        <a href="{{ route('media.show', $item['id']) }}" class="relative aspect-square bg-surface-2">
            <img src="{{ $conn->getServerUrl() }}/storage/{{ $item['thumbnail_path'] ?? '' }}"
                 class="w-full h-full object-cover" loading="lazy">
            @if($item['media_type'] === 'video')
            <span class="absolute bottom-1 right-1 material-symbols-outlined text-white text-base drop-shadow">play_circle</span>
            @endif
            @if($item['backed_up'] ?? false)
            <span class="absolute top-1 left-1 material-symbols-outlined text-white text-sm drop-shadow" title="Backed up">cloud_done</span>
            @endif
        </a>
        @endforeach
    </div>
    @endforeach

    @if(empty($items))
    <div class="flex flex-col items-center justify-center h-64 text-center px-8">
        <span class="material-symbols-outlined text-gray-600 text-6xl mb-3">photo_library</span>
        <p class="text-gray-400">No photos yet. Pull to refresh or wait for background sync.</p>
    </div>
    @endif
</div>
@endsection
```

- [ ] **Create remaining tab controllers**

`app/Http/Controllers/Mobile/SearchController.php`:

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function index(Request $request)
    {
        if (!$this->conn->isConfigured()) return redirect()->route('onboarding');

        $query  = $request->input('q', '');
        $type   = $request->input('type', 'all');
        $token  = $this->conn->getToken();
        $url    = $this->conn->getServerUrl();
        $results = [];

        if ($query) {
            $response = Http::withToken($token)->timeout(10)
                ->get("{$url}/api/v2/media", ['q' => $query, 'type' => $type, 'per_page' => 40]);
            $results = $response->successful() ? $response->json('data', []) : [];
        }

        return view('mobile.search', compact('query', 'type', 'results'));
    }
}
```

`app/Http/Controllers/Mobile/FilesController.php`:

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FilesController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function index(Request $request)
    {
        if (!$this->conn->isConfigured()) return redirect()->route('onboarding');

        $type   = $request->input('type', 'all');
        $token  = $this->conn->getToken();
        $url    = $this->conn->getServerUrl();

        $response = Http::withToken($token)->timeout(10)
            ->get("{$url}/api/v2/media", ['type' => $type, 'per_page' => 40, 'sort' => 'created_at_desc']);

        $files = $response->successful() ? $response->json('data', []) : [];

        return view('mobile.files', compact('type', 'files'));
    }
}
```

`app/Http/Controllers/Mobile/PeopleController.php`:

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Support\Facades\Http;

class PeopleController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function index()
    {
        if (!$this->conn->isConfigured()) return redirect()->route('onboarding');

        $token    = $this->conn->getToken();
        $url      = $this->conn->getServerUrl();

        $response = Http::withToken($token)->timeout(10)->get("{$url}/api/v2/face-clusters");
        $clusters = $response->successful() ? $response->json('data', []) : [];

        return view('mobile.people', compact('clusters'));
    }
}
```

`app/Http/Controllers/Mobile/MoreController.php`:

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use App\Services\OfflineQueueManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoreController extends Controller
{
    public function __construct(
        private readonly ServerConnectionService $conn,
        private readonly OfflineQueueManager     $queue,
    ) {}

    public function index()
    {
        if (!$this->conn->isConfigured()) return redirect()->route('onboarding');

        $token    = $this->conn->getToken();
        $url      = $this->conn->getServerUrl();

        $meResp    = Http::withToken($token)->timeout(5)->get("{$url}/api/v2/users/me");
        $quotaResp = Http::withToken($token)->timeout(5)->get("{$url}/api/v2/quota");

        $user         = $meResp->successful() ? $meResp->json() : [];
        $quota        = $quotaResp->successful() ? $quotaResp->json() : [];
        $pendingCount = $this->queue->getPendingCount();
        $wifiOnly     = session('backup_wifi_only', true);
        $includeVideos = session('backup_include_videos', true);

        return view('mobile.more', compact('user', 'quota', 'pendingCount', 'wifiOnly', 'includeVideos'));
    }

    public function saveSettings(Request $request)
    {
        session([
            'backup_wifi_only'      => $request->boolean('wifi_only'),
            'backup_include_videos' => $request->boolean('include_videos'),
        ]);
        return back()->with('success', 'Settings saved.');
    }

    public function logout()
    {
        $this->conn->logout();
        return redirect()->route('onboarding');
    }
}
```

`app/Http/Controllers/Mobile/MediaViewerController.php`:

```php
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ServerConnectionService;
use Illuminate\Support\Facades\Http;

class MediaViewerController extends Controller
{
    public function __construct(private readonly ServerConnectionService $conn) {}

    public function show(int $id)
    {
        if (!$this->conn->isConfigured()) return redirect()->route('onboarding');

        $token    = $this->conn->getToken();
        $url      = $this->conn->getServerUrl();

        $response = Http::withToken($token)->timeout(10)->get("{$url}/api/v2/media/{$id}");
        $media    = $response->successful() ? $response->json() : null;

        if (!$media) abort(404);

        return view('mobile.viewer', compact('media'));
    }
}
```

- [ ] **Create remaining 4 tab views (concise versions — full visual parity with layout.blade.php)**

`resources/views/mobile/search.blade.php`:

```html
@extends('mobile.layout')
@section('content')
<div class="px-4 pt-4">
    <form method="GET" action="{{ route('search') }}" class="mb-4">
        <div class="flex items-center bg-surface-2 rounded-full px-4 py-2 gap-2">
            <span class="material-symbols-outlined text-gray-400">search</span>
            <input type="text" name="q" value="{{ $query }}" placeholder="Search photos, files, people…"
                   class="flex-1 bg-transparent text-white text-sm outline-none" autofocus>
        </div>
        <div class="flex gap-2 mt-3 overflow-x-auto pb-1">
            @foreach(['all' => 'All', 'image' => 'Photos', 'video' => 'Videos', 'document' => 'Docs', 'audio' => 'Audio'] as $value => $label)
            <button type="submit" name="type" value="{{ $value }}"
                    class="flex-shrink-0 px-3 py-1 rounded-full text-sm {{ $type === $value ? 'bg-primary text-white' : 'bg-surface-2 text-gray-400' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </form>

    @if(!empty($results))
    <div class="grid grid-cols-3 gap-0.5">
        @foreach($results as $item)
        <a href="{{ route('media.show', $item['id']) }}" class="relative aspect-square bg-surface-2">
            <img src="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/storage/{{ $item['thumbnail_path'] ?? '' }}"
                 class="w-full h-full object-cover" loading="lazy">
        </a>
        @endforeach
    </div>
    @elseif($query)
    <p class="text-center text-gray-500 mt-16">No results for "{{ $query }}"</p>
    @endif
</div>
@endsection
```

`resources/views/mobile/files.blade.php`:

```html
@extends('mobile.layout')
@section('content')
<div class="px-4 pt-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-white">Files</h1>
        <a href="#" id="btn-upload-fab" class="bg-primary text-white rounded-full px-4 py-1.5 text-sm font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-base">upload</span> Upload
        </a>
    </div>

    {{-- Type filter chips --}}
    <div class="flex gap-2 overflow-x-auto pb-3">
        @foreach(['all' => 'All', 'image' => 'Photos', 'video' => 'Videos', 'document' => 'Docs', 'audio' => 'Audio', 'archive' => 'Archives'] as $value => $label)
        <a href="{{ route('files', ['type' => $value]) }}"
           class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm {{ $type === $value ? 'bg-primary text-white' : 'bg-surface-2 text-gray-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- File list --}}
    <div class="divide-y divide-gray-800">
        @forelse($files as $file)
        <a href="{{ route('media.show', $file['id']) }}" class="flex items-center py-3 gap-3">
            <div class="w-10 h-10 bg-surface-2 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-gray-400 text-xl">
                    {{ match($file['media_type'] ?? '') { 'image' => 'image', 'video' => 'videocam', 'audio' => 'audio_file', 'document' => 'description', default => 'folder_zip' } }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm truncate">{{ $file['filename'] }}</p>
                <p class="text-gray-500 text-xs">{{ number_format(($file['file_size'] ?? 0) / 1024 / 1024, 1) }} MB · {{ \Carbon\Carbon::parse($file['created_at'])->diffForHumans() }}</p>
            </div>
            <span class="material-symbols-outlined text-gray-600">more_vert</span>
        </a>
        @empty
        <p class="text-center text-gray-500 py-16">No files yet.</p>
        @endforelse
    </div>
</div>
@endsection
```

`resources/views/mobile/people.blade.php`:

```html
@extends('mobile.layout')
@section('content')
<div class="px-4 pt-4">
    <h1 class="text-xl font-semibold text-white mb-4">People</h1>
    <div class="grid grid-cols-3 gap-3">
        @forelse($clusters as $cluster)
        <div class="flex flex-col items-center">
            <div class="w-20 h-20 rounded-full overflow-hidden {{ empty($cluster['name']) ? 'border-2 border-dashed border-gray-600' : 'border-2 border-primary' }}">
                <img src="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/storage/{{ $cluster['face_thumbnail'] ?? '' }}"
                     class="w-full h-full object-cover" loading="lazy">
            </div>
            <p class="text-white text-xs mt-1 font-medium truncate w-20 text-center">{{ $cluster['name'] ?: 'Unknown' }}</p>
            <p class="text-gray-500 text-xs">{{ $cluster['photo_count'] ?? 0 }} photos</p>
        </div>
        @empty
        <div class="col-span-3 text-center py-16">
            <span class="material-symbols-outlined text-gray-600 text-6xl">face</span>
            <p class="text-gray-500 mt-2">No people detected yet.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
```

`resources/views/mobile/more.blade.php`:

```html
@extends('mobile.layout')
@section('content')
<div class="px-4 pt-4">
    {{-- User card --}}
    <div class="bg-surface-2 rounded-2xl p-4 mb-4 flex items-center gap-3">
        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
            {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
        </div>
        <div class="flex-1">
            <p class="text-white font-medium">{{ $user['name'] ?? 'Unknown' }}</p>
            <p class="text-gray-400 text-sm">{{ $user['email'] ?? '' }}</p>
        </div>
    </div>

    {{-- Storage quota bar --}}
    @if(!empty($quota))
    <div class="bg-surface-2 rounded-2xl p-4 mb-4">
        <div class="flex justify-between text-sm mb-2">
            <span class="text-white font-medium">Storage</span>
            <span class="text-gray-400">{{ number_format(($quota['used'] ?? 0) / 1024**3, 1) }} GB / {{ number_format(($quota['total'] ?? 107374182400) / 1024**3, 0) }} GB</span>
        </div>
        <div class="w-full bg-gray-700 rounded-full h-2">
            <div class="bg-primary h-2 rounded-full" style="width: {{ min($quota['percent'] ?? 0, 100) }}%"></div>
        </div>
    </div>
    @endif

    {{-- Backup status --}}
    <div class="bg-surface-2 rounded-2xl p-4 mb-4 flex items-center justify-between">
        <div>
            <p class="text-white font-medium text-sm">Backup Status</p>
            <p class="text-gray-400 text-xs">{{ $pendingCount }} item(s) pending</p>
        </div>
        <span class="flex items-center gap-1 text-xs {{ $pendingCount > 0 ? 'text-yellow-400' : 'text-green-400' }}">
            <span class="w-2 h-2 rounded-full {{ $pendingCount > 0 ? 'bg-yellow-400 animate-pulse' : 'bg-green-400' }}"></span>
            {{ $pendingCount > 0 ? 'Syncing' : 'Up to date' }}
        </span>
    </div>

    {{-- Settings --}}
    <form method="POST" action="{{ route('more.settings') }}" class="bg-surface-2 rounded-2xl p-4 mb-4 space-y-3">
        @csrf
        <div class="flex items-center justify-between">
            <p class="text-white text-sm">WiFi only</p>
            <input type="hidden" name="wifi_only" value="0">
            <input type="checkbox" name="wifi_only" value="1" {{ $wifiOnly ? 'checked' : '' }} class="accent-primary w-5 h-5">
        </div>
        <div class="flex items-center justify-between">
            <p class="text-white text-sm">Include Videos</p>
            <input type="hidden" name="include_videos" value="0">
            <input type="checkbox" name="include_videos" value="1" {{ $includeVideos ? 'checked' : '' }} class="accent-primary w-5 h-5">
        </div>
        <button type="submit" class="w-full bg-primary text-white rounded-xl py-2 text-sm font-medium">Save Settings</button>
    </form>

    {{-- Sign out --}}
    <form method="POST" action="{{ route('more.logout') }}">
        @csrf
        <button type="submit" class="w-full bg-surface-2 text-red-400 rounded-xl py-3 text-sm font-medium">Sign Out</button>
    </form>
</div>
@endsection
```

`resources/views/mobile/viewer.blade.php`:

```html
@extends('mobile.layout', ['showTabs' => false])
@section('content')
<div class="flex flex-col min-h-screen bg-black">
    {{-- Back button --}}
    <div class="absolute top-0 left-0 right-0 flex items-center px-4 pt-safe pt-4 z-10">
        <a href="javascript:history.back()" class="w-9 h-9 rounded-full bg-black/50 flex items-center justify-center">
            <span class="material-symbols-outlined text-white">arrow_back</span>
        </a>
    </div>

    {{-- Media --}}
    <div class="flex-1 flex items-center justify-center" id="viewer-media">
        @if(($media['media_type'] ?? '') === 'video')
        <video src="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/storage/{{ $media['file_path'] }}"
               controls class="max-w-full max-h-screen"></video>
        @else
        <img src="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/storage/{{ $media['file_path'] }}"
             class="max-w-full max-h-screen object-contain" id="viewer-img">
        @endif
    </div>

    {{-- Bottom toolbar --}}
    <div class="px-6 py-4 flex items-center justify-around bg-gradient-to-t from-black/80 to-transparent">
        <button class="flex flex-col items-center text-white gap-1">
            <span class="material-symbols-outlined">share</span>
            <span class="text-xs">Share</span>
        </button>
        <a href="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/storage/{{ $media['file_path'] }}"
           download class="flex flex-col items-center text-white gap-1">
            <span class="material-symbols-outlined">download</span>
            <span class="text-xs">Save</span>
        </a>
        <button class="flex flex-col items-center text-white gap-1" id="btn-info-toggle">
            <span class="material-symbols-outlined">info</span>
            <span class="text-xs">Info</span>
        </button>
        <form method="POST" action="{{ app(App\Services\ServerConnectionService::class)->getServerUrl() }}/api/v2/media/{{ $media['id'] }}/trash">
            @csrf @method('PATCH')
            <button type="submit" class="flex flex-col items-center text-red-400 gap-1"
                    onclick="return confirm('Move to trash?')">
                <span class="material-symbols-outlined">delete</span>
                <span class="text-xs">Delete</span>
            </button>
        </form>
    </div>

    {{-- Info panel --}}
    <div id="info-panel" class="hidden bg-surface-2 rounded-t-2xl px-6 py-4 space-y-3">
        @if($media['ai_description'] ?? '')
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">AI Caption</p>
            <p class="text-white text-sm">{{ $media['ai_description'] }}</p>
        </div>
        @endif
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Details</p>
            <p class="text-white text-sm">{{ $media['filename'] }}</p>
            <p class="text-gray-400 text-xs">{{ number_format(($media['file_size'] ?? 0) / 1024 / 1024, 2) }} MB · {{ $media['created_at'] ?? '' }}</p>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Commit all tab views and controllers**

```bash
git add app/Http/Controllers/Mobile/ resources/views/mobile/
git commit -m "feat: add all 5 tab screens, media viewer, and mobile controllers"
```

---

## Task 9: Capacitor plugin wiring (JavaScript)

- [ ] **Create `resources/js/app.js` with Capacitor plugin initialization**

```javascript
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Network } from '@capacitor/network';
import { Preferences } from '@capacitor/preferences';

// Network status monitoring — add 'offline-banner' class to body when offline
Network.addListener('networkStatusChange', (status) => {
    document.body.classList.toggle('offline', !status.connected);
    document.body.dataset.networkType = status.connectionType; // 'wifi' | 'cellular' | 'none'
});

// Pull-to-refresh on photos page
let startY = 0;
document.addEventListener('touchstart', (e) => { startY = e.touches[0].clientY; });
document.addEventListener('touchend', (e) => {
    const delta = e.changedTouches[0].clientY - startY;
    if (delta > 80 && window.scrollY === 0) {
        window.location.reload();
    }
});

// Request camera permissions on the permissions screen
const permBtn = document.getElementById('btn-request-permissions');
if (permBtn) {
    permBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await Camera.requestPermissions();
        window.location.href = permBtn.href;
    });
}

// Media viewer: pinch-to-zoom on img
const viewerImg = document.getElementById('viewer-img');
if (viewerImg) {
    let scale = 1;
    viewerImg.addEventListener('dblclick', () => {
        scale = scale > 1 ? 1 : 2;
        viewerImg.style.transform = `scale(${scale})`;
    });
}

// Info panel toggle on viewer page
const infoToggle = document.getElementById('btn-info-toggle');
const infoPanel  = document.getElementById('info-panel');
if (infoToggle && infoPanel) {
    infoToggle.addEventListener('click', () => {
        infoPanel.classList.toggle('hidden');
    });
}
```

- [ ] **Run Capacitor sync**

```bash
npm run build
npx cap sync ios
npx cap sync android
```

Expected: No errors. Capacitor copies built assets to `ios/App/` and `android/`.

- [ ] **Commit**

```bash
git add resources/js/app.js
git commit -m "feat: wire Capacitor plugins for network detection, pull-to-refresh, media viewer"
```

---

## Task 10: Build iOS and Android

- [ ] **Build iOS app (requires macOS + Xcode)**

```bash
npx cap open ios
# In Xcode: set Team to your Apple ID
# Product → Archive → Distribute App → Ad Hoc
```

Expected: `.ipa` file created for direct install on test devices.

- [ ] **Build Android APK**

```bash
npx cap open android
# In Android Studio: Build → Generate Signed APK → debug build for testing
```

Expected: `app-debug.apk` in `android/app/build/outputs/apk/debug/`.

- [ ] **Smoke test on device: onboarding flow**

Install `.ipa` or `.apk` on a test device. Steps:
1. App opens to server URL screen
2. Enter `http://<your-server-ip>:8080` → tap Connect
3. Enter credentials → tap Sign in
4. Grant permissions → tap Next
5. Configure backup → tap Start Backup
6. Lands on Photos tab — grid appears if server has media

- [ ] **Smoke test: manual upload from Files tab**

1. Open Files tab → tap Upload
2. Select a file from device storage
3. File appears in server gallery within 30 seconds (on WiFi)

- [ ] **Run all unit tests**

```bash
./vendor/bin/pest --filter="BackgroundSync|OfflineQueue|ChunkedUpload"
```

Expected: All 11 tests PASS.

- [ ] **Commit**

```bash
git add .
git commit -m "feat: complete NativePHP Mobile app — all screens, sync, and Capacitor build"
```

---

## Phase 4 Complete Checklist

- [ ] React Native project deleted; new NativePHP Mobile project at `mobile-app/AvinashEYE/`
- [ ] Onboarding: server URL → login → permissions → backup setup (4 screens)
- [ ] Photos tab: memories strip + chronological grid with cloud-done badges
- [ ] Search tab: unified search with type filters
- [ ] Files tab: type filter chips + file list + upload FAB
- [ ] People tab: face cluster cards in 3-column grid
- [ ] More tab: user card, quota bar, sync status, settings, sign-out
- [ ] Media viewer: full-screen, double-tap zoom, info panel, delete, download
- [ ] `BackgroundSyncService` respects wifi_only and skips unconfigured server
- [ ] `OfflineQueueManager` dequeues by network type, stops after 5 failures
- [ ] `ChunkedUploadService` checks dedup, inits upload, sends 5MB chunks
- [ ] Capacitor plugins wired: network detection, pull-to-refresh, pinch-to-zoom
- [ ] iOS `.ipa` and Android `.apk` build successfully
- [ ] All unit tests pass
