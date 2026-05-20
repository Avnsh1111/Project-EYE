# Phase 1 — Bug Fixes + Web Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all 7 bugs from PROJECT_STATUS_REPORT.md and polish the web UI to the Unified Hub navigation (Google Photos/Drive aesthetic).

**Architecture:** Each bug is an isolated fix in a single file. Web polish touches only the layout blade and the EnhancedImageGallery Livewire component. No new services or models needed in this phase — starred_at/trashed_at migration is included here to enable the star/trash UI.

**Tech Stack:** Laravel 12, Livewire 3, Pest PHP, Blade/Tailwind CSS

---

## File Map

| Action | File | Change |
|--------|------|--------|
| Modify | `app/Jobs/ProcessImageAnalysis.php` | BUG-1 + BUG-5: fix config keys (4 locations) |
| Modify | `app/Http/Controllers/MediaController.php` | BUG-2: Range header guard |
| Modify | `.env` | BUG-3: Switch to pgsql |
| Create | `.env.example` | BUG-3: All missing vars documented |
| Create | `app/Listeners/ImageProcessedListener.php` | BUG-4: Handle ImageProcessed event |
| Modify | `app/Providers/AppServiceProvider.php` | BUG-4: Register listener |
| Modify | `app/Models/ArchiveFile.php` | BUG-6: Real hasPassword() |
| Modify | `app/Services/CacheService.php` | BUG-7: Real getStats() with counters |
| Create | `database/migrations/2026_04_10_000001_add_star_trash_to_media_files.php` | New columns |
| Modify | `resources/views/layouts/app.blade.php` | Unified Hub sidebar |
| Modify | `app/Livewire/EnhancedImageGallery.php` | Memories strip + star/trash actions |
| Modify | `resources/views/livewire/enhanced-image-gallery.blade.php` | Memories strip UI |
| Create | `tests/Unit/MediaControllerRangeTest.php` | BUG-2 regression |
| Create | `tests/Unit/ArchiveFileTest.php` | BUG-6 regression |
| Create | `tests/Unit/CacheServiceStatsTest.php` | BUG-7 regression |
| Create | `tests/Feature/ImageProcessedListenerTest.php` | BUG-4 regression |
| Modify | `tests/Unit/AiServiceTest.php` | BUG-1 config key assertion |

---

## Task 1: Fix ProcessImageAnalysis config keys (BUG-1 + BUG-5)

**Files:**
- Modify: `app/Jobs/ProcessImageAnalysis.php` (lines 303, 441, 495, 577)

There are 4 wrong config key calls in this file:
- Line 303: `config('services.ollama.url', 'http://ollama:11434')` — Ollama URL is not in `config/ai.php` yet; add it and fix
- Lines 441, 495, 577: `config('services.python_ai.url', 'http://python-ai:8000')` → `config('ai.api_url', 'http://python-ai:8000')`

- [ ] **Step 1: Add ollama_url to config/ai.php**

Open `config/ai.php`. After the `'api_url'` line, add:

```php
'api_url' => env('AI_API_URL', 'http://python-ai:8000'),

'ollama_url' => env('OLLAMA_URL', 'http://ollama:11434'),
```

- [ ] **Step 2: Fix line 303 in ProcessImageAnalysis.php**

Find and replace:
```php
// OLD (line 303)
$response = Http::timeout(90)->post(config('services.ollama.url', 'http://ollama:11434') . '/api/generate', [
```
```php
// NEW
$response = Http::timeout(90)->post(config('ai.ollama_url', 'http://ollama:11434') . '/api/generate', [
```

- [ ] **Step 3: Fix lines 441, 495, 577 in ProcessImageAnalysis.php**

Find all 3 occurrences and replace:
```php
// OLD (appears at lines 441, 495, 577)
$pythonUrl = config('services.python_ai.url', 'http://python-ai:8000');
```
```php
// NEW
$pythonUrl = config('ai.api_url', 'http://python-ai:8000');
```

- [ ] **Step 4: Verify no remaining bad config keys**

```bash
grep -n "config('services.python_ai\|config('services.ollama" app/Jobs/ProcessImageAnalysis.php
```
Expected: no output.

- [ ] **Step 5: Commit**

```bash
git add config/ai.php app/Jobs/ProcessImageAnalysis.php
git commit -m "fix: standardise AI config keys in ProcessImageAnalysis

Replace services.python_ai.url and services.ollama.url with the
correct ai.api_url and ai.ollama_url keys from config/ai.php.
Add ai.ollama_url entry to config/ai.php."
```

---

## Task 2: Fix MediaController Range header guard (BUG-2)

**Files:**
- Modify: `app/Http/Controllers/MediaController.php` (line 47)
- Create: `tests/Unit/MediaControllerRangeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MediaControllerRangeTest.php`:

```php
<?php

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('stream returns 416 for malformed Range header', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
    Storage::disk('local')->put('public/test.mp4', $file->getContent());

    $media = MediaFile::factory()->create([
        'file_path'  => 'public/test.mp4',
        'media_type' => 'video',
        'mime_type'  => 'video/mp4',
    ]);

    $response = $this->actingAs($user)
        ->get(route('media.stream', $media), [
            'Range' => 'invalid-range-header',
        ]);

    $response->assertStatus(416);
});

test('stream returns 206 for valid Range header', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $content = str_repeat('a', 2048);
    Storage::disk('local')->put('public/test.mp4', $content);

    $media = MediaFile::factory()->create([
        'file_path'  => 'public/test.mp4',
        'media_type' => 'video',
        'mime_type'  => 'video/mp4',
    ]);

    $response = $this->actingAs($user)
        ->get(route('media.stream', $media), [
            'Range' => 'bytes=0-1023',
        ]);

    $response->assertStatus(206);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec laravel-app php artisan test --filter=MediaControllerRangeTest
```
Expected: FAIL — the invalid Range header currently causes an undefined index error (500), not 416.

- [ ] **Step 3: Fix MediaController.php**

In `app/Http/Controllers/MediaController.php`, find the range request block (around line 47) and add the guard immediately after `preg_match`:

```php
if ($range) {
    // Parse range header (e.g., "bytes=0-1023")
    preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);

    // Guard: reject malformed Range headers
    if (empty($matches)) {
        abort(416, 'Range Not Satisfiable');
    }

    $start = intval($matches[1]);
    $end = $matches[2] ? intval($matches[2]) : $size - 1;
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose exec laravel-app php artisan test --filter=MediaControllerRangeTest
```
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/MediaController.php tests/Unit/MediaControllerRangeTest.php
git commit -m "fix: guard against malformed Range header in media stream

Add empty($matches) check after preg_match to return 416 Range Not
Satisfiable instead of crashing with undefined index error."
```

---

## Task 3: Fix .env PostgreSQL config + create .env.example (BUG-3)

**Files:**
- Modify: `.env`
- Create: `.env.example`

- [ ] **Step 1: Update .env to use PostgreSQL**

In `.env`, replace the SQLite DB block:
```
# OLD
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```
```
# NEW
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=avinash_eye
DB_USERNAME=avinash
DB_PASSWORD=secret
```

- [ ] **Step 2: Create .env.example**

Create `.env.example` with all variables documented:

```
APP_NAME="Avinash EYE"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Database — PostgreSQL with pgvector
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=avinash_eye
DB_USERNAME=avinash
DB_PASSWORD=secret

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database

# Session
SESSION_DRIVER=database

# Broadcasting (Laravel Reverb — added in Phase 2)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=avinash-eye
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

# Python AI service
AI_API_URL=http://python-ai:8000
AI_TIMEOUT=120
AI_CIRCUIT_BREAKER_THRESHOLD=10
AI_CIRCUIT_BREAKER_RECOVERY=30
AI_RETRY_MAX_ATTEMPTS=3

# Node.js image processor
NODE_PROCESSOR_URL=http://node-processor:3000

# Ollama LLM (optional)
OLLAMA_URL=http://ollama:11434
OLLAMA_ENABLED=true
OLLAMA_MODEL=llava:13b-v1.6

# Elasticsearch
SCOUT_DRIVER=elasticsearch
ELASTICSEARCH_HOST=http://elasticsearch:9200

# Default admin user (created on first run)
DEFAULT_USER_EMAIL=admin@avinash-eye.local
DEFAULT_USER_PASSWORD=Admin@123
DEFAULT_USER_NAME=Administrator
```

- [ ] **Step 3: Verify Docker can connect to PostgreSQL**

```bash
docker compose exec laravel-app php artisan db:show
```
Expected: shows PostgreSQL connection info, no errors.

- [ ] **Step 4: Commit**

```bash
git add .env.example
git commit -m "fix: switch DB to PostgreSQL and document all env variables

Create .env.example with all required variables including AI service
URLs, Ollama, Elasticsearch, and Reverb. Switch .env from sqlite to pgsql."
```

Note: `.env` itself should NOT be committed (it's in `.gitignore`).

---

## Task 4: Create ImageProcessedListener and wire it (BUG-4)

**Files:**
- Create: `app/Listeners/ImageProcessedListener.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/ImageProcessedListenerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ImageProcessedListenerTest.php`:

```php
<?php

use App\Events\ImageProcessed;
use App\Listeners\ImageProcessedListener;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Event;

test('ImageProcessed event has a registered listener', function () {
    $listeners = Event::getListeners(ImageProcessed::class);

    expect($listeners)->not->toBeEmpty();
});

test('ImageProcessedListener logs the processed file', function () {
    $media = MediaFile::factory()->create([
        'processing_status' => 'completed',
        'original_filename' => 'test.jpg',
    ]);

    $event = new ImageProcessed($media);
    $listener = new ImageProcessedListener();

    // Should not throw
    expect(fn () => $listener->handle($event))->not->toThrow(Exception::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec laravel-app php artisan test --filter=ImageProcessedListenerTest
```
Expected: FAIL — "ImageProcessed event has a registered listener" fails because no listeners are registered.

- [ ] **Step 3: Create ImageProcessedListener**

Create `app/Listeners/ImageProcessedListener.php`:

```php
<?php

namespace App\Listeners;

use App\Events\ImageProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ImageProcessedListener implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(ImageProcessed $event): void
    {
        Log::info('Media processed', [
            'id'       => $event->imageFile->id,
            'filename' => $event->imageFile->original_filename,
            'status'   => $event->imageFile->processing_status,
        ]);
    }
}
```

- [ ] **Step 4: Register listener in AppServiceProvider**

In `app/Providers/AppServiceProvider.php`, add to the `boot()` method:

```php
use App\Events\ImageProcessed;
use App\Listeners\ImageProcessedListener;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    // Existing observer + scout registration stays...

    // Wire ImageProcessed event to its listener
    Event::listen(ImageProcessed::class, ImageProcessedListener::class);
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
docker compose exec laravel-app php artisan test --filter=ImageProcessedListenerTest
```
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/ImageProcessedListener.php \
        app/Providers/AppServiceProvider.php \
        tests/Feature/ImageProcessedListenerTest.php
git commit -m "fix: create ImageProcessedListener and register it

Wire the ImageProcessed event to ImageProcessedListener so real-time
notifications fire after AI processing completes."
```

---

## Task 5: Implement ArchiveFile::hasPassword() (BUG-6)

**Files:**
- Modify: `app/Models/ArchiveFile.php`
- Create: `tests/Unit/ArchiveFileTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ArchiveFileTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec laravel-app php artisan test --filter=ArchiveFileTest
```
Expected: FAIL — `hasPassword()` always returns `false` regardless of `is_encrypted`.

- [ ] **Step 3: Check if is_encrypted column exists on archive files**

```bash
docker compose exec laravel-app php artisan tinker --execute="echo Schema::hasColumn('media_files', 'is_encrypted') ? 'yes' : 'no';"
```

If it doesn't exist, create a migration:

```bash
docker compose exec laravel-app php artisan make:migration add_is_encrypted_to_media_files
```

Content of the new migration:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('archive_format');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
};
```

Run it:
```bash
docker compose exec laravel-app php artisan migrate
```

- [ ] **Step 4: Update ArchiveFile::hasPassword()**

In `app/Models/ArchiveFile.php`, replace the placeholder:

```php
// OLD
public function hasPassword(): bool
{
    // This would need to be determined during processing
    // For now, return false as a placeholder
    return false;
}
```

```php
// NEW
public function hasPassword(): bool
{
    return (bool) $this->is_encrypted;
}
```

Also add `is_encrypted` to the `$casts` array in `ArchiveFile.php`:
```php
protected $casts = [
    // ... existing casts ...
    'is_encrypted' => 'boolean',
];
```

- [ ] **Step 5: Run test to verify it passes**

```bash
docker compose exec laravel-app php artisan test --filter=ArchiveFileTest
```
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Models/ArchiveFile.php \
        database/migrations/*add_is_encrypted_to_media_files* \
        tests/Unit/ArchiveFileTest.php
git commit -m "fix: implement ArchiveFile::hasPassword() using is_encrypted column

Replace placeholder that always returned false with a real implementation
backed by the is_encrypted boolean column on media_files."
```

---

## Task 6: Implement CacheService::getStats() (BUG-7)

**Files:**
- Modify: `app/Services/CacheService.php`
- Create: `tests/Unit/CacheServiceStatsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/CacheServiceStatsTest.php`:

```php
<?php

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

test('getStats returns hits and misses counters', function () {
    Cache::flush();
    $service = new CacheService();

    $stats = $service->getStats();

    expect($stats)->toHaveKeys(['prefix', 'default_ttl', 'store', 'hits', 'misses', 'hit_rate']);
});

test('getStats increments hits when cache key exists', function () {
    Cache::flush();
    $service = new CacheService();

    // Simulate a hit by manually calling get on a primed key
    $service->set('/tmp/test.jpg', ['caption' => 'test']);
    $service->get('/tmp/test.jpg');  // hit

    $stats = $service->getStats();
    expect($stats['hits'])->toBeGreaterThanOrEqual(1);
});

test('getStats increments misses when cache key missing', function () {
    Cache::flush();
    $service = new CacheService();
    $service->resetStats();

    $service->get('/tmp/nonexistent.jpg');  // miss

    $stats = $service->getStats();
    expect($stats['misses'])->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec laravel-app php artisan test --filter=CacheServiceStatsTest
```
Expected: FAIL — `getStats()` doesn't return `hits`, `misses`, or `hit_rate` keys.

- [ ] **Step 3: Update CacheService**

In `app/Services/CacheService.php`, add counter tracking. First add constants and a `resetStats()` helper near the top of the class:

```php
protected string $hitsKey  = 'cache_stats:hits';
protected string $missesKey = 'cache_stats:misses';

public function resetStats(): void
{
    Cache::put($this->hitsKey,  0, 86400 * 30);
    Cache::put($this->missesKey, 0, 86400 * 30);
}
```

Update `get()` to increment counters. Find the `get()` method and wrap the Cache call:

```php
public function get(string $filePath): ?array
{
    $key    = $this->generateKey($filePath);
    $result = Cache::get($key);

    if ($result !== null) {
        Cache::increment($this->hitsKey);
    } else {
        Cache::increment($this->missesKey);
    }

    return $result;
}
```

Replace `getStats()`:

```php
public function getStats(): array
{
    $hits   = (int) Cache::get($this->hitsKey, 0);
    $misses = (int) Cache::get($this->missesKey, 0);
    $total  = $hits + $misses;

    return [
        'prefix'      => $this->prefix,
        'default_ttl' => $this->defaultTtl,
        'store'       => config('cache.default'),
        'hits'        => $hits,
        'misses'      => $misses,
        'hit_rate'    => $total > 0 ? round(($hits / $total) * 100, 1) : 0.0,
    ];
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose exec laravel-app php artisan test --filter=CacheServiceStatsTest
```
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/CacheService.php tests/Unit/CacheServiceStatsTest.php
git commit -m "fix: implement real hit/miss tracking in CacheService::getStats()

Replace placeholder stub with Cache::increment counters tracked per
get() call. getStats() now returns hits, misses, and hit_rate."
```

---

## Task 7: Add starred_at + trashed_at migration (Web Polish prerequisite)

**Files:**
- Create: `database/migrations/2026_04_10_000002_add_star_trash_to_media_files.php`

- [ ] **Step 1: Create the migration**

```bash
docker compose exec laravel-app php artisan make:migration add_star_trash_to_media_files
```

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->timestamp('starred_at')->nullable()->after('processing_status');
            $table->timestamp('trashed_at')->nullable()->after('starred_at');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['starred_at', 'trashed_at']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
docker compose exec laravel-app php artisan migrate
```
Expected: "Migrating: ...add_star_trash_to_media_files ... Migrated"

- [ ] **Step 3: Add casts to MediaFile model**

In `app/Models/MediaFile.php`, add to the `$casts` array:

```php
'starred_at' => 'datetime',
'trashed_at' => 'datetime',
```

- [ ] **Step 4: Add scopes to MediaFile**

In `app/Models/MediaFile.php`, add these query scopes after the existing scopes:

```php
public function scopeStarred($query)
{
    return $query->whereNotNull('starred_at');
}

public function scopeNotTrashed($query)
{
    return $query->whereNull('trashed_at');
}

public function scopeTrashed($query)
{
    return $query->whereNotNull('trashed_at');
}
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*add_star_trash_to_media_files* app/Models/MediaFile.php
git commit -m "feat: add starred_at and trashed_at columns to media_files

Add nullable timestamp columns and corresponding model casts + query
scopes to support star/trash UI actions in Phase 1 web polish."
```

---

## Task 8: Add star/trash actions to EnhancedImageGallery

**Files:**
- Modify: `app/Livewire/EnhancedImageGallery.php`
- Modify: `resources/views/livewire/enhanced-image-gallery.blade.php`

- [ ] **Step 1: Add Livewire action methods**

In `app/Livewire/EnhancedImageGallery.php`, add these public methods:

```php
public function toggleStar(int $mediaId): void
{
    $media = MediaFile::findOrFail($mediaId);

    if ($media->starred_at) {
        $media->update(['starred_at' => null]);
    } else {
        $media->update(['starred_at' => now()]);
    }

    $this->dispatch('media-updated', id: $mediaId);
}

public function trashMedia(int $mediaId): void
{
    $media = MediaFile::findOrFail($mediaId);
    $media->update(['trashed_at' => now()]);
    $this->dispatch('media-updated', id: $mediaId);
}

public function restoreMedia(int $mediaId): void
{
    $media = MediaFile::findOrFail($mediaId);
    $media->update(['trashed_at' => null]);
    $this->dispatch('media-updated', id: $mediaId);
}
```

- [ ] **Step 2: Update gallery query to exclude trashed by default**

In `EnhancedImageGallery.php`, find the main query builder (the method that builds the media query — likely `getMediaQuery()` or inside `loadImages()`). Add a default scope to exclude trashed items unless the "trash" filter is active:

```php
// In the query builder, add before ->paginate():
if (!$this->filters['trashed'] ?? true) {
    $query->notTrashed();
}
```

- [ ] **Step 3: Add star/trash buttons to the gallery grid item in the blade view**

In `resources/views/livewire/enhanced-image-gallery.blade.php`, find the media card markup (the `<div>` that wraps each photo thumbnail). Add an action overlay:

```html
{{-- Inside the media card, add this overlay on hover --}}
<div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-200 rounded-md">
    <div class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        {{-- Star button --}}
        <button wire:click="toggleStar({{ $media->id }})"
                class="w-7 h-7 rounded-full bg-black/50 flex items-center justify-center hover:bg-black/70 transition-colors"
                title="{{ $media->starred_at ? 'Unstar' : 'Star' }}">
            <span class="material-symbols-outlined text-sm {{ $media->starred_at ? 'text-yellow-400' : 'text-white' }}">
                {{ $media->starred_at ? 'star' : 'star_border' }}
            </span>
        </button>
        {{-- Trash button --}}
        <button wire:click="trashMedia({{ $media->id }})"
                wire:confirm="Move this item to trash?"
                class="w-7 h-7 rounded-full bg-black/50 flex items-center justify-center hover:bg-red-600/80 transition-colors"
                title="Move to trash">
            <span class="material-symbols-outlined text-sm text-white">delete</span>
        </button>
    </div>
</div>
```

- [ ] **Step 4: Verify in browser**

```bash
docker compose up -d && open http://localhost:8080/gallery
```
Hover over a photo — star and trash icons should appear. Click star — icon turns yellow. Click trash — photo disappears from gallery.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/EnhancedImageGallery.php \
        resources/views/livewire/enhanced-image-gallery.blade.php
git commit -m "feat: add star and trash actions to gallery media cards

Add toggleStar(), trashMedia(), restoreMedia() Livewire actions.
Exclude trashed items from gallery by default. Show action icons
on card hover with star (yellow when active) and delete buttons."
```

---

## Task 9: Unified Hub sidebar navigation (Web Polish)

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Replace the $navItems array**

In `resources/views/layouts/app.blade.php`, find the `$navItems` array (around line 156) and replace it with the Unified Hub structure:

```php
$navItems = [
    // Primary navigation
    ['route' => 'gallery',       'icon' => 'photo_library',       'label' => 'Photos',       'section' => null],
    ['route' => 'search',        'icon' => 'search',              'label' => 'Search',        'section' => null],
    ['route' => 'documents',     'icon' => 'folder',              'label' => 'Files',         'section' => null],
    ['route' => 'people-and-pets','icon' => 'face',               'label' => 'People',        'section' => null],
    // Organise section
    ['route' => 'collections',   'icon' => 'auto_awesome_mosaic', 'label' => 'Albums',        'section' => 'Organise'],
    ['route' => 'instant-upload','icon' => 'upload',              'label' => 'Upload',        'section' => null],
    // Admin section
    ['route' => 'system-monitor','icon' => 'monitoring',          'label' => 'System Monitor','section' => 'Admin'],
    ['route' => 'settings',      'icon' => 'settings',            'label' => 'Settings',      'section' => null],
];
```

- [ ] **Step 2: Update the nav loop to render section headers**

Replace the existing `@foreach ($navItems as $item)` block with:

```html
@php $lastSection = null; @endphp
@foreach ($navItems as $item)
    @if ($item['section'] && $item['section'] !== $lastSection)
        @php $lastSection = $item['section']; @endphp
        <div class="px-3 pt-4 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $item['section'] }}</p>
        </div>
    @endif
    <a href="{{ route($item['route']) }}"
       class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition-all duration-200
              {{ $currentRoute === $item['route']
                 ? 'bg-primary-100 text-primary-700 font-semibold'
                 : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
        <span class="material-symbols-outlined text-xl
              {{ $currentRoute === $item['route'] ? 'text-primary-600' : '' }}">
            {{ $item['icon'] }}
        </span>
        {{ $item['label'] }}
    </a>
@endforeach
```

- [ ] **Step 3: Verify in browser**

```bash
open http://localhost:8080/gallery
```
Sidebar should show: Photos, Search, Files, People — then "Organise" header with Albums and Upload — then "Admin" header with System Monitor and Settings.

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: update sidebar to Unified Hub navigation

Reorganise nav items to Google Photos/Drive style: Photos, Search,
Files, People as primary items; Albums/Upload under Organise section;
System Monitor/Settings under Admin section."
```

---

## Task 10: Memories strip on gallery home (Web Polish)

**Files:**
- Modify: `app/Livewire/EnhancedImageGallery.php`
- Modify: `resources/views/livewire/enhanced-image-gallery.blade.php`

- [ ] **Step 1: Add memories query to EnhancedImageGallery**

In `app/Livewire/EnhancedImageGallery.php`, add a `getMemories()` computed property or call from `mount()`:

```php
public function getMemoriesProperty(): \Illuminate\Support\Collection
{
    return MediaFile::query()
        ->where('media_type', 'image')
        ->whereNull('trashed_at')
        ->whereNotNull('taken_at')
        ->where('taken_at', '>=', now()->subYears(2))
        ->selectRaw("DATE_TRUNC('month', taken_at) as month, COUNT(*) as count, MIN(id) as cover_id")
        ->groupByRaw("DATE_TRUNC('month', taken_at)")
        ->orderByDesc('month')
        ->limit(8)
        ->get()
        ->map(function ($row) {
            return [
                'label'    => \Carbon\Carbon::parse($row->month)->format('M Y'),
                'count'    => $row->count,
                'cover_id' => $row->cover_id,
            ];
        });
}
```

- [ ] **Step 2: Add memories strip to blade view**

In `resources/views/livewire/enhanced-image-gallery.blade.php`, add before the main photo grid (near the top of the component's visible content area):

```html
{{-- Memories strip --}}
@if($this->memories->isNotEmpty())
<div class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-gray-800">Memories</h2>
        <span class="text-xs text-primary-600 cursor-pointer hover:underline">See all</span>
    </div>
    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
        @foreach($this->memories as $memory)
        <div class="flex-shrink-0 w-28 rounded-2xl overflow-hidden relative cursor-pointer
                    hover:scale-105 transition-transform duration-200 shadow-md3-2"
             style="aspect-ratio: 3/4; background: linear-gradient(135deg, #1e3a5f, #2d6a9f)">
            {{-- Cover image if available --}}
            <img src="{{ route('media.thumbnail', $memory['cover_id']) }}"
                 class="absolute inset-0 w-full h-full object-cover"
                 loading="lazy"
                 onerror="this.style.display='none'"
                 alt="{{ $memory['label'] }}"/>
            {{-- Label overlay --}}
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                <p class="text-white text-xs font-semibold leading-tight">{{ $memory['label'] }}</p>
                <p class="text-white/70 text-xs">{{ $memory['count'] }} photos</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
```

- [ ] **Step 3: Add scrollbar-hide utility to Tailwind config**

In `tailwind.config.js`, add to the `theme.extend` plugins array (or in the CSS):

```css
/* In resources/css/app.css */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
```

- [ ] **Step 4: Verify in browser**

```bash
open http://localhost:8080/gallery
```
A horizontal strip of month cards should appear above the photo grid, each showing a month label and photo count.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/EnhancedImageGallery.php \
        resources/views/livewire/enhanced-image-gallery.blade.php \
        resources/css/app.css
git commit -m "feat: add memories strip to gallery home

Show AI-curated monthly memory cards above the photo grid, grouped by
month with cover image, label, and photo count. Horizontal scroll with
hidden scrollbar, limited to last 2 years / 8 months."
```

---

## Task 11: Run full test suite and verify all bugs fixed

- [ ] **Step 1: Run all tests**

```bash
docker compose exec laravel-app php artisan test
```
Expected: All existing tests pass + the 4 new test files (11 new tests) all pass.

- [ ] **Step 2: Verify each bug fix in browser**

```bash
open http://localhost:8080/gallery
```

Check:
- [ ] Sidebar shows Unified Hub nav (Photos / Search / Files / People / Albums / Upload / System Monitor / Settings)
- [ ] Memories strip appears at top of gallery
- [ ] Hovering a photo shows star + trash icons
- [ ] Clicking star turns icon yellow; clicking again removes star
- [ ] Clicking trash removes photo from gallery (moves to trash filter)
- [ ] Video streaming works without crashing (open any video)

- [ ] **Step 3: Final commit with updated report**

Update `PROJECT_STATUS_REPORT.md` — mark Phase 1 items as resolved:

```bash
# Open PROJECT_STATUS_REPORT.md and mark these items:
# BUG-1 ✅, BUG-2 ✅, BUG-3 ✅, BUG-4 ✅, BUG-5 ✅, BUG-6 ✅, BUG-7 ✅
# Web Polish ✅

git add PROJECT_STATUS_REPORT.md
git commit -m "docs: mark Phase 1 complete in PROJECT_STATUS_REPORT"
```

---

## Phase 1 Complete Checklist

- [ ] BUG-1: ProcessImageAnalysis config keys fixed (4 locations)
- [ ] BUG-2: MediaController Range header guard added
- [ ] BUG-3: .env uses PostgreSQL, .env.example created
- [ ] BUG-4: ImageProcessedListener created and registered
- [ ] BUG-5: Config keys standardised
- [ ] BUG-6: ArchiveFile::hasPassword() uses is_encrypted column
- [ ] BUG-7: CacheService::getStats() returns real counters
- [ ] Web: Unified Hub sidebar navigation
- [ ] Web: Memories strip on gallery home
- [ ] Web: Star + trash actions on media cards
- [ ] All tests passing
- [ ] PROJECT_STATUS_REPORT.md updated
