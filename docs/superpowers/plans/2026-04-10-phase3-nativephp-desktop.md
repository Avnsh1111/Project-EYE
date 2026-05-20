# Phase 3 — NativePHP Desktop App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wrap the existing Laravel/Livewire web app in a native desktop window using NativePHP Electron, adding system tray, folder watching with auto-import, and an offline-capable SQLite upload queue — producing signed Mac `.dmg`, Windows `.exe`, and Linux `.AppImage` builds.

**Architecture:** Install `nativephp/electron` into the main Laravel app (not a new project). A `NativeAppServiceProvider` opens an Electron window pointing at the existing `gallery` route. A `FolderWatcherService` manages configured watched directories, and a scheduled Artisan command (`desktop:scan`) checks for new files every 60 seconds. New files go into an `OfflineQueueService` backed by a per-user SQLite file at `~/.avinash-eye/queue.db`. A second command (`desktop:process-queue`) picks up pending items and calls the API v2 resumable upload endpoints. The web UI detects Electron via a middleware-injected `X-NativePHP-Desktop` cookie and hides browser-irrelevant UI.

**Tech Stack:** Laravel 12, NativePHP Electron (`nativephp/electron`), SQLite (local queue only), Pest PHP

**Prerequisite:** Phase 2 complete — API v2 endpoints for `/api/v2/media/upload/init`, `PATCH /api/v2/media/upload/{id}`, and `GET /api/v2/media/dedup/{hash}` must be working.

---

## File Map

| Action | File |
|--------|------|
| Install | `composer require nativephp/electron` |
| Create (published) | `config/nativephp.php` |
| Create | `app/Providers/NativeAppServiceProvider.php` |
| Create | `app/Services/FolderWatcherService.php` |
| Create | `app/Services/OfflineQueueService.php` |
| Create | `app/Console/Commands/Desktop/ScanFolders.php` |
| Create | `app/Console/Commands/Desktop/ProcessQueue.php` |
| Create | `app/Http/Middleware/DesktopDetect.php` |
| Modify | `app/Console/Kernel.php` (schedule desktop commands) |
| Modify | `bootstrap/app.php` (register middleware) |
| Modify | `resources/views/layouts/app.blade.php` (desktop class on body) |
| Create | `storage/app/tray-icon.png` (placeholder 16×16 PNG) |
| Create | `tests/Unit/FolderWatcherServiceTest.php` |
| Create | `tests/Unit/OfflineQueueServiceTest.php` |

---

## Task 1: Install and configure NativePHP Electron

- [ ] **Install the package**

```bash
composer require nativephp/electron
php artisan native:install
php artisan native:publish
```

Expected output: `config/nativephp.php` published, `NativeAppServiceProvider` stub created.

- [ ] **Configure `config/nativephp.php`**

Replace the defaults with Avinash EYE values:

```php
return [
    'id'          => 'com.avinasheye.desktop',
    'name'        => 'Avinash EYE',
    'version'     => '2.0.0',
    'author'      => 'Avinash',
    'description' => 'Self-hosted Google Photos + Drive',

    'updater' => [
        'enabled' => false,
    ],

    'publishing' => [
        'provider' => 'github',
        'owner'    => 'coding-sunshine',
        'repo'     => 'Avinash-EYE',
    ],
];
```

- [ ] **Verify install succeeded**

```bash
php artisan native:serve
```

Expected: Electron window opens showing the Laravel app (may show an error page if DB isn't running — that's fine, the window opening is what we're verifying).

- [ ] **Commit**

```bash
git add composer.json composer.lock config/nativephp.php
git commit -m "feat: install nativephp/electron and publish config"
```

---

## Task 2: NativeAppServiceProvider — window + system tray

- [ ] **Create `app/Providers/NativeAppServiceProvider.php`**

```php
<?php

namespace App\Providers;

use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\SystemTray;
use Native\Laravel\Menu\Menu;
use Native\Laravel\Menu\Items\MenuItem;

class NativeAppServiceProvider extends \Native\Laravel\Providers\NativeAppServiceProvider implements ProvidesPhpIni
{
    public function phpIni(): array
    {
        return [];
    }

    public function boot(): void
    {
        Window::open()
            ->title('Avinash EYE')
            ->url(route('gallery'))
            ->width(1280)
            ->height(800)
            ->minWidth(900)
            ->minHeight(600)
            ->titleBarStyle('hiddenInset');

        SystemTray::create()
            ->icon(storage_path('app/tray-icon.png'))
            ->tooltip('Avinash EYE')
            ->menu(
                Menu::new()
                    ->item(MenuItem::new()->label('Open Avinash EYE')->event('open-window'))
                    ->item(MenuItem::new()->label('Upload Files…')->event('open-upload'))
                    ->item(MenuItem::separator())
                    ->item(MenuItem::new()->label('Pause Sync')->event('toggle-sync'))
                    ->item(MenuItem::separator())
                    ->item(MenuItem::new()->label('Quit')->quit())
            );
    }
}
```

- [ ] **Handle tray events — add listener in the same provider's `boot()`**

Append inside the `boot()` method, after the SystemTray call:

```php
        // Listen to tray menu events
        \Native\Laravel\Events\SystemTray\MenuItemClicked::listen(function ($event) {
            match ($event->menuItem) {
                'open-window' => Window::open()->url(route('gallery')),
                'open-upload' => Window::open()->url(route('instant-upload')),
                'toggle-sync' => $this->toggleSync(),
                default => null,
            };
        });
```

Add the private helper to the class:

```php
    private function toggleSync(): void
    {
        $paused = cache()->get('desktop_sync_paused', false);
        cache()->put('desktop_sync_paused', !$paused, now()->addDays(30));
    }
```

- [ ] **Add a placeholder tray icon**

```bash
# Create a simple 16x16 placeholder PNG in storage
php artisan tinker --execute="
\$img = imagecreatetruecolor(16, 16);
\$blue = imagecolorallocate(\$img, 99, 102, 241);
imagefill(\$img, 0, 0, \$blue);
imagepng(\$img, storage_path('app/tray-icon.png'));
imagedestroy(\$img);
echo 'Tray icon created';
"
```

- [ ] **Test: launch desktop app and verify tray icon appears**

```bash
php artisan native:serve
```

Expected: Electron window at 1280×800, tray icon in menu bar (macOS) or system tray (Windows/Linux).

- [ ] **Commit**

```bash
git add app/Providers/NativeAppServiceProvider.php storage/app/tray-icon.png
git commit -m "feat: add NativeAppServiceProvider with window and system tray"
```

---

## Task 3: FolderWatcherService

- [ ] **Write the failing test `tests/Unit/FolderWatcherServiceTest.php`**

```php
<?php

use App\Services\FolderWatcherService;

beforeEach(function () {
    // Use a temp path so test does not write to real app storage
    $this->service = new FolderWatcherService(sys_get_temp_dir() . '/test-watcher-' . uniqid() . '.json');
});

afterEach(function () {
    $path = $this->service->getConfigPath();
    if (file_exists($path)) {
        unlink($path);
    }
});

it('starts with no watched folders', function () {
    expect($this->service->getWatchedFolders())->toBe([]);
});

it('adds and persists a folder', function () {
    $this->service->addFolder('/tmp/photos');
    expect($this->service->getWatchedFolders())->toContain('/tmp/photos');

    // Fresh instance reads from same file
    $fresh = new FolderWatcherService($this->service->getConfigPath());
    expect($fresh->getWatchedFolders())->toContain('/tmp/photos');
});

it('removes a folder', function () {
    $this->service->addFolder('/tmp/photos');
    $this->service->removeFolder('/tmp/photos');
    expect($this->service->getWatchedFolders())->not->toContain('/tmp/photos');
});

it('does not duplicate folders', function () {
    $this->service->addFolder('/tmp/photos');
    $this->service->addFolder('/tmp/photos');
    expect($this->service->getWatchedFolders())->toHaveCount(1);
});

it('ignores system files', function () {
    expect($this->service->isIgnored('.DS_Store'))->toBeTrue();
    expect($this->service->isIgnored('Thumbs.db'))->toBeTrue();
    expect($this->service->isIgnored('.hidden_file'))->toBeTrue();
    expect($this->service->isIgnored('upload.part'))->toBeTrue();
    expect($this->service->isIgnored('temp.tmp'))->toBeTrue();
});

it('does not ignore normal media files', function () {
    expect($this->service->isIgnored('photo.jpg'))->toBeFalse();
    expect($this->service->isIgnored('video.mp4'))->toBeFalse();
    expect($this->service->isIgnored('document.pdf'))->toBeFalse();
});
```

- [ ] **Run test to verify it fails**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Unit/FolderWatcherServiceTest.php -v
```

Expected: FAIL — `App\Services\FolderWatcherService` not found.

- [ ] **Create `app/Services/FolderWatcherService.php`**

```php
<?php

namespace App\Services;

class FolderWatcherService
{
    public function __construct(
        private readonly string $configPath = '',
    ) {
        if ($this->configPath === '') {
            $this->configPath = storage_path('app/watched_folders.json');
        }
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    public function getWatchedFolders(): array
    {
        if (!file_exists($this->configPath)) {
            return [];
        }
        return json_decode(file_get_contents($this->configPath), true) ?? [];
    }

    public function addFolder(string $path): void
    {
        $folders = $this->getWatchedFolders();
        if (in_array($path, $folders)) {
            return;
        }
        $folders[] = $path;
        file_put_contents($this->configPath, json_encode($folders, JSON_PRETTY_PRINT));
    }

    public function removeFolder(string $path): void
    {
        $folders = array_values(array_filter(
            $this->getWatchedFolders(),
            fn ($f) => $f !== $path,
        ));
        file_put_contents($this->configPath, json_encode($folders, JSON_PRETTY_PRINT));
    }

    public function isIgnored(string $filename): bool
    {
        $basename = basename($filename);

        if (in_array($basename, ['.DS_Store', 'Thumbs.db', 'desktop.ini', '.gitkeep'], true)) {
            return true;
        }
        if (str_starts_with($basename, '.')) {
            return true;
        }
        if (str_ends_with($basename, '.tmp') || str_ends_with($basename, '.part') || str_ends_with($basename, '.crdownload')) {
            return true;
        }
        return false;
    }
}
```

- [ ] **Run test to verify it passes**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Unit/FolderWatcherServiceTest.php -v
```

Expected: All 7 tests PASS.

- [ ] **Commit**

```bash
git add app/Services/FolderWatcherService.php tests/Unit/FolderWatcherServiceTest.php
git commit -m "feat: add FolderWatcherService with ignored-file detection"
```

---

## Task 4: OfflineQueueService (SQLite)

- [ ] **Write the failing test `tests/Unit/OfflineQueueServiceTest.php`**

```php
<?php

use App\Services\OfflineQueueService;

beforeEach(function () {
    // Use an in-memory SQLite DB for tests
    $this->queue = new OfflineQueueService(':memory:');
});

it('enqueues an item and returns its ID', function () {
    $id = $this->queue->enqueue('/tmp/photo.jpg', 'abc123sha256');
    expect($id)->toBeInt()->toBeGreaterThan(0);
});

it('dequeues pending items', function () {
    $this->queue->enqueue('/tmp/photo.jpg', 'abc123');
    $this->queue->enqueue('/tmp/video.mp4', 'def456');

    $items = $this->queue->dequeue(10);
    expect($items)->toHaveCount(2);
    expect($items[0]['file_path'])->toBe('/tmp/photo.jpg');
    expect($items[0]['status'])->toBe('pending');
});

it('marks item as uploading with upload ID', function () {
    $id = $this->queue->enqueue('/tmp/photo.jpg', 'abc123');
    $this->queue->markUploading($id, 'upload-uuid-abc');

    $items = $this->queue->dequeue(10);
    // uploading items not returned by dequeue (which only returns pending)
    expect($items)->toHaveCount(0);
});

it('marks item as done', function () {
    $id = $this->queue->enqueue('/tmp/photo.jpg', 'abc123');
    $this->queue->markDone($id);
    expect($this->queue->getPendingCount())->toBe(0);
});

it('increments attempt count on failure', function () {
    $id = $this->queue->enqueue('/tmp/photo.jpg', 'abc123');
    $this->queue->markFailed($id);
    $this->queue->markFailed($id);

    $items = $this->queue->dequeue(10);
    expect($items[0]['attempts'])->toBe(2);
});

it('stops returning item after 5 failed attempts', function () {
    $id = $this->queue->enqueue('/tmp/photo.jpg', 'abc123');
    for ($i = 0; $i < 5; $i++) {
        $this->queue->markFailed($id);
    }
    expect($this->queue->dequeue(10))->toHaveCount(0);
});

it('counts pending and uploading items', function () {
    $id1 = $this->queue->enqueue('/tmp/photo1.jpg', 'hash1');
    $id2 = $this->queue->enqueue('/tmp/photo2.jpg', 'hash2');
    $this->queue->markUploading($id1, 'uuid1');

    expect($this->queue->getPendingCount())->toBe(2); // pending + uploading
});
```

- [ ] **Run test to verify it fails**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Unit/OfflineQueueServiceTest.php -v
```

Expected: FAIL — `App\Services\OfflineQueueService` not found.

- [ ] **Create `app/Services/OfflineQueueService.php`**

```php
<?php

namespace App\Services;

class OfflineQueueService
{
    private \PDO $pdo;

    public function __construct(string $dbPath = '')
    {
        if ($dbPath === '') {
            $dbPath = $this->resolveDefaultPath();
        }
        $this->pdo = new \PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->initSchema();
    }

    private function resolveDefaultPath(): string
    {
        $home = getenv('HOME') ?: sys_get_temp_dir();
        $dir  = $home . '/.avinash-eye';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/queue.db';
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS upload_queue (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path       TEXT    NOT NULL,
                file_hash       TEXT,
                status          TEXT    NOT NULL DEFAULT 'pending',
                upload_id       TEXT,
                bytes_uploaded  INTEGER NOT NULL DEFAULT 0,
                attempts        INTEGER NOT NULL DEFAULT 0,
                created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function enqueue(string $filePath, string $fileHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO upload_queue (file_path, file_hash) VALUES (?, ?)'
        );
        $stmt->execute([$filePath, $fileHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function dequeue(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM upload_queue WHERE status = 'pending' AND attempts < 5 ORDER BY created_at LIMIT ?"
        );
        $stmt->execute([$limit]);
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

    public function getByHash(string $hash): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM upload_queue WHERE file_hash = ? AND status IN ('pending','uploading','done') LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
```

- [ ] **Run test to verify it passes**

```bash
docker compose exec laravel-app ./vendor/bin/pest tests/Unit/OfflineQueueServiceTest.php -v
```

Expected: All 7 tests PASS.

- [ ] **Commit**

```bash
git add app/Services/OfflineQueueService.php tests/Unit/OfflineQueueServiceTest.php
git commit -m "feat: add OfflineQueueService backed by SQLite for desktop upload queue"
```

---

## Task 5: ScanFolders + ProcessQueue Artisan commands

- [ ] **Create `app/Console/Commands/Desktop/ScanFolders.php`**

```php
<?php

namespace App\Console\Commands\Desktop;

use App\Services\FolderWatcherService;
use App\Services\OfflineQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScanFolders extends Command
{
    protected $signature   = 'desktop:scan';
    protected $description = 'Scan watched folders for new files and add to upload queue';

    public function handle(FolderWatcherService $watcher, OfflineQueueService $queue): int
    {
        if (cache()->get('desktop_sync_paused')) {
            $this->info('Sync is paused.');
            return 0;
        }

        $folders = $watcher->getWatchedFolders();
        if (empty($folders)) {
            return 0;
        }

        $queued = 0;
        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                $this->warn("Folder not found: {$folder}");
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                if ($watcher->isIgnored($file->getFilename())) continue;
                if ($file->getSize() < 1024) continue; // skip files under 1KB

                $hash = hash_file('sha256', $file->getPathname());

                // Skip if already in queue
                if ($queue->getByHash($hash) !== null) continue;

                // Check dedup API — skip if already on server
                $token = config('nativephp.api_token');
                if ($token) {
                    $response = Http::withToken($token)
                        ->timeout(5)
                        ->get(config('app.url') . '/api/v2/media/dedup/' . $hash);

                    if ($response->successful() && $response->json('exists')) {
                        continue;
                    }
                }

                $queue->enqueue($file->getPathname(), $hash);
                $queued++;
                $this->line("Queued: {$file->getFilename()}");
            }
        }

        $this->info("Scan complete. {$queued} new file(s) queued.");
        return 0;
    }
}
```

- [ ] **Create `app/Console/Commands/Desktop/ProcessQueue.php`**

```php
<?php

namespace App\Console\Commands\Desktop;

use App\Services\OfflineQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProcessQueue extends Command
{
    protected $signature   = 'desktop:process-queue';
    protected $description = 'Upload pending items from the desktop offline queue';

    private const CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB

    public function handle(OfflineQueueService $queue): int
    {
        if (cache()->get('desktop_sync_paused')) {
            return 0;
        }

        $token = config('nativephp.api_token');
        if (!$token) {
            $this->warn('No API token configured. Set NATIVEPHP_API_TOKEN in desktop env.');
            return 1;
        }

        $items = $queue->dequeue(3);

        foreach ($items as $item) {
            $this->processItem($item, $token, $queue);
        }

        return 0;
    }

    private function processItem(array $item, string $token, OfflineQueueService $queue): void
    {
        $filePath = $item['file_path'];
        if (!file_exists($filePath)) {
            $queue->markDone($item['id']); // file deleted on disk — remove from queue
            return;
        }

        $baseUrl = config('app.url');
        $fileSize = filesize($filePath);

        try {
            // Init resumable upload
            $uploadId = $item['upload_id'];
            $bytesUploaded = (int) $item['bytes_uploaded'];

            if (!$uploadId) {
                $response = Http::withToken($token)->post("{$baseUrl}/api/v2/media/upload/init", [
                    'filename'    => basename($filePath),
                    'total_bytes' => $fileSize,
                    'file_hash'   => $item['file_hash'],
                ]);

                if (!$response->successful()) {
                    $queue->markFailed($item['id']);
                    return;
                }

                $uploadId = $response->json('upload_id');
                $queue->markUploading($item['id'], $uploadId);
            }

            // Upload chunks
            $handle = fopen($filePath, 'rb');
            fseek($handle, $bytesUploaded);

            while (!feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);
                if ($chunk === false || $chunk === '') break;

                $response = Http::withToken($token)
                    ->withBody($chunk, 'application/octet-stream')
                    ->patch("{$baseUrl}/api/v2/media/upload/{$uploadId}", [
                        'offset' => $bytesUploaded,
                    ]);

                if (!$response->successful()) {
                    fclose($handle);
                    $queue->markFailed($item['id']);
                    return;
                }

                $bytesUploaded += strlen($chunk);
                $queue->updateProgress($item['id'], $bytesUploaded);
            }

            fclose($handle);
            $queue->markDone($item['id']);
            $this->line('Uploaded: ' . basename($filePath));
        } catch (\Throwable $e) {
            $queue->markFailed($item['id']);
            $this->error("Failed {$filePath}: " . $e->getMessage());
        }
    }
}
```

- [ ] **Schedule the commands — modify `app/Console/Kernel.php`**

If `Kernel.php` doesn't exist (Laravel 12 uses `bootstrap/app.php` schedules), add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Desktop folder scan — only runs in NativePHP Electron context
if (app()->runningInConsole() && getenv('NATIVEPHP_ELECTRON')) {
    Schedule::command('desktop:scan')->everyMinute();
    Schedule::command('desktop:process-queue')->everyThirtySeconds();
}
```

- [ ] **Test: manually run both commands**

```bash
docker compose exec laravel-app php artisan desktop:scan
docker compose exec laravel-app php artisan desktop:process-queue
```

Expected: Both exit with "0 new file(s) queued" and no errors.

- [ ] **Commit**

```bash
git add app/Console/Commands/Desktop/ routes/console.php
git commit -m "feat: add desktop:scan and desktop:process-queue artisan commands"
```

---

## Task 6: Desktop detection middleware — web UI adjustment

- [ ] **Create `app/Http/Middleware/DesktopDetect.php`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DesktopDetect
{
    public function handle(Request $request, Closure $next): mixed
    {
        // NativePHP Electron sets this header on all requests
        if ($request->hasHeader('X-NativePHP-Desktop') || $request->userAgent() === 'NativePHP') {
            $request->attributes->set('is_desktop', true);
            config(['app.is_desktop' => true]);
        }

        return $next($request);
    }
}
```

- [ ] **Register middleware in `bootstrap/app.php`**

Find the `->withMiddleware()` call and add:

```php
->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
    $middleware->appendToGroup('web', \App\Http\Middleware\DesktopDetect::class);
})
```

- [ ] **Update `resources/views/layouts/app.blade.php` — add desktop CSS class to body**

Find the `<body` tag and update it:

```php
{{-- before --}}
<body class="...">

{{-- after --}}
<body class="{{ config('app.is_desktop') ? 'desktop-app' : '' }} ...">
```

- [ ] **Add desktop-specific CSS to hide browser chrome in web view**

In `resources/css/app.css`, append:

```css
/* Desktop app adjustments — hide browser-specific UI when running in Electron */
.desktop-app .browser-only {
    display: none !important;
}

.desktop-app .app-header {
    -webkit-app-region: drag; /* allow window drag on macOS titlebar */
}
```

- [ ] **Mark browser-only elements in `app.blade.php`**

Add `class="browser-only"` to any `<a href="...">` that opens external URLs in a browser, and to the "Install PWA" button if present.

- [ ] **Commit**

```bash
git add app/Http/Middleware/DesktopDetect.php bootstrap/app.php resources/views/layouts/app.blade.php resources/css/app.css
git commit -m "feat: add desktop detection middleware and hide browser-only UI in Electron"
```

---

## Task 7: Build configuration for Mac / Windows / Linux

- [ ] **Update `config/nativephp.php` with build targets**

```php
'building' => [
    'targets' => [
        'mac'     => ['dmg', 'zip'],
        'windows' => ['nsis'],
        'linux'   => ['AppImage'],
    ],
    'mac' => [
        'identity' => env('APPLE_DEVELOPER_IDENTITY'),
        'notarize' => [
            'team_id'   => env('APPLE_TEAM_ID'),
            'apple_id'  => env('APPLE_ID'),
            'password'  => env('APPLE_PASSWORD'),
        ],
    ],
],
```

- [ ] **Add build env vars to `.env.example`**

```
# NativePHP Desktop build
NATIVEPHP_ELECTRON=false
NATIVEPHP_API_TOKEN=
APPLE_DEVELOPER_IDENTITY=
APPLE_TEAM_ID=
APPLE_ID=
APPLE_PASSWORD=
```

- [ ] **Test: build macOS DMG (requires macOS)**

```bash
php artisan native:build mac
```

Expected: `dist/Avinash EYE-2.0.0.dmg` created (or equivalent path set in nativephp config).

- [ ] **Test: verify the DMG installs and opens**

Mount and install the `.dmg`, launch the app, verify:
1. Window opens to gallery
2. System tray icon appears
3. `desktop:scan` runs in background (check Console.app for artisan output)

- [ ] **Commit**

```bash
git add config/nativephp.php .env.example
git commit -m "feat: configure NativePHP build targets for Mac/Windows/Linux"
```

---

## Task 8: Web UI — Watched Folders settings page

- [ ] **Add route to `routes/web.php`**

```php
Route::middleware(['auth'])->group(function () {
    // ... existing routes ...
    Route::get('/settings/desktop', [\App\Http\Controllers\DesktopSettingsController::class, 'show'])->name('settings.desktop');
    Route::post('/settings/desktop/folders', [\App\Http\Controllers\DesktopSettingsController::class, 'addFolder'])->name('settings.desktop.add-folder');
    Route::delete('/settings/desktop/folders', [\App\Http\Controllers\DesktopSettingsController::class, 'removeFolder'])->name('settings.desktop.remove-folder');
});
```

- [ ] **Create `app/Http/Controllers/DesktopSettingsController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Services\FolderWatcherService;
use App\Services\OfflineQueueService;
use Illuminate\Http\Request;

class DesktopSettingsController extends Controller
{
    public function show(FolderWatcherService $watcher, OfflineQueueService $queue)
    {
        return view('desktop.settings', [
            'folders'      => $watcher->getWatchedFolders(),
            'pendingCount' => $queue->getPendingCount(),
            'isPaused'     => cache()->get('desktop_sync_paused', false),
        ]);
    }

    public function addFolder(Request $request, FolderWatcherService $watcher)
    {
        $validated = $request->validate(['path' => 'required|string']);
        $watcher->addFolder($validated['path']);
        return back()->with('success', 'Folder added to watch list.');
    }

    public function removeFolder(Request $request, FolderWatcherService $watcher)
    {
        $validated = $request->validate(['path' => 'required|string']);
        $watcher->removeFolder($validated['path']);
        return back()->with('success', 'Folder removed from watch list.');
    }
}
```

- [ ] **Create `resources/views/desktop/settings.blade.php`**

```php
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-white mb-6">Desktop Sync Settings</h1>

    {{-- Status card --}}
    <div class="bg-surface rounded-xl p-4 mb-6 flex items-center justify-between">
        <div>
            <p class="text-white font-medium">Sync Status</p>
            <p class="text-sm text-gray-400">{{ $pendingCount }} file(s) pending upload</p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm {{ $isPaused ? 'bg-yellow-600 text-white' : 'bg-green-600 text-white' }}">
            {{ $isPaused ? 'Paused' : 'Active' }}
        </span>
    </div>

    {{-- Watched folders --}}
    <div class="bg-surface rounded-xl p-4 mb-6">
        <h2 class="text-lg font-medium text-white mb-4">Watched Folders</h2>

        @forelse($folders as $folder)
        <div class="flex items-center justify-between py-2 border-b border-gray-700 last:border-0">
            <span class="text-gray-300 text-sm font-mono">{{ $folder }}</span>
            <form method="POST" action="{{ route('settings.desktop.remove-folder') }}">
                @csrf @method('DELETE')
                <input type="hidden" name="path" value="{{ $folder }}">
                <button type="submit" class="text-red-400 hover:text-red-300 text-sm">Remove</button>
            </form>
        </div>
        @empty
        <p class="text-gray-500 text-sm">No folders configured.</p>
        @endforelse
    </div>

    {{-- Add folder form --}}
    <form method="POST" action="{{ route('settings.desktop.add-folder') }}" class="flex gap-2">
        @csrf
        <input type="text" name="path" placeholder="/Users/you/Photos"
               class="flex-1 bg-gray-800 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm">
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm">Add Folder</button>
    </form>
</div>
@endsection
```

- [ ] **Add link to Desktop Settings in sidebar nav — modify `resources/views/layouts/app.blade.php`**

Find the `$navItems` array and add (desktop only):

```php
// Add conditionally in the view:
@if(config('app.is_desktop'))
    <a href="{{ route('settings.desktop') }}" class="nav-item {{ request()->routeIs('settings.desktop') ? 'active' : '' }}">
        <span class="material-symbols-outlined">folder_open</span>
        <span>Watched Folders</span>
    </a>
@endif
```

- [ ] **Commit**

```bash
git add routes/web.php app/Http/Controllers/DesktopSettingsController.php resources/views/desktop/
git commit -m "feat: add desktop settings page for managing watched folders"
```

---

## Phase 3 Complete Checklist

- [ ] `php artisan native:serve` opens Electron window at 1280×800 pointing at gallery
- [ ] System tray icon appears with Open / Upload / Pause Sync / Quit menu
- [ ] `FolderWatcherService` correctly ignores system files — unit tests pass
- [ ] `OfflineQueueService` enqueue/dequeue/markDone/markFailed — unit tests pass
- [ ] `desktop:scan` scans configured folders and adds new files to queue
- [ ] `desktop:process-queue` uploads files in 5MB chunks via API v2
- [ ] Web UI shows `.desktop-app` class on body when running in Electron
- [ ] Desktop settings page shows watched folders and queue count
- [ ] macOS `.dmg` builds via `php artisan native:build mac`
- [ ] All unit tests pass
