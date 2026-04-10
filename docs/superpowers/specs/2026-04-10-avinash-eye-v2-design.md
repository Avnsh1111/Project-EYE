# Avinash-EYE v2.0 — Design Spec

**Date**: 2026-04-10  
**Status**: Approved for implementation  
**Approach**: API-First Parallel (Approach 2)  
**Timeline**: 12 weeks

---

## 1. Overview

Transform Avinash-EYE from a single-user web-only media manager into a **self-hosted Google Photos + Google Drive** equivalent with three client apps sharing one backend:

| Client | Technology | Purpose |
|--------|-----------|---------|
| Web app | Laravel 12 + Livewire 3 (existing) | Browser access — polish only |
| Desktop app | NativePHP + Electron | Mac / Windows / Linux — folder watch + system tray |
| Mobile app | NativePHP Mobile + Capacitor | iOS + Android — camera roll backup |

**Core promise**: Take a photo on your phone → it appears on your web and desktop automatically. No cloud, no subscriptions, 100% self-hosted.

---

## 2. Goals

- **Replace** the React Native mobile app with a NativePHP Mobile app
- **Add** a NativePHP Desktop app wrapping the existing web UI
- **Fix** all 7 bugs identified in `PROJECT_STATUS_REPORT.md`
- **Add** multi-user accounts, family sharing, storage quotas, shareable links
- **Add** any-file-type upload (remove media-only restriction)
- **Add** instant camera-roll auto-backup (WiFi) + offline queue (cellular)
- **Add** desktop folder watching with auto-import
- **Polish** web UI to Unified Hub navigation (matching Google Photos/Drive aesthetic already in place)

---

## 3. Non-Goals

- No Google account sync or any cloud integration
- No collaborative document editing (Google Docs equivalent)
- No video calling or messaging
- No public internet hosting (designed for home network / VPN access)

---

## 4. Architecture

### 4.1 System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  CLIENT LAYER                                                       │
│                                                                     │
│  ┌──────────────────┐  ┌───────────────────┐  ┌─────────────────┐  │
│  │  Web App         │  │  NativePHP Desktop│  │ NativePHP Mobile│  │
│  │  (Livewire 3)    │  │  (Electron)       │  │ (Capacitor)     │  │
│  │  Browser :8080   │  │  Mac/Win/Linux    │  │ iOS + Android   │  │
│  └────────┬─────────┘  └────────┬──────────┘  └───────┬─────────┘  │
└───────────┼─────────────────────┼────────────────────┼─────────────┘
            │                     │                    │
            └─────────────────────┼────────────────────┘
                          HTTP REST + WebSocket (Reverb)
                          Laravel Sanctum auth tokens
                                  │
┌─────────────────────────────────▼───────────────────────────────────┐
│  API LAYER — Laravel 12 (enhanced)                                  │
│  Auth/Users · Multi-user · Quotas · Sharing · Sync · Admin          │
└──────┬──────────────┬────────────────┬──────────────────────────────┘
       │              │                │
┌──────▼──┐    ┌──────▼──┐    ┌────────▼────────┐
│ Python  │    │ Node.js │    │ Ollama LLM      │
│ AI/ML   │    │ Processor│   │ LLaVA 13B       │
│ :8000   │    │ :3000   │    │ :11434          │
└──────┬──┘    └──────┬──┘    └────────┬────────┘
       └──────────────┴────────────────┘
                      │
┌─────────────────────▼───────────────────────────┐
│  STORAGE                                        │
│  PostgreSQL 16 + pgvector  Elasticsearch 8      │
│  File storage (per-user)   Laravel Reverb (WS)  │
└─────────────────────────────────────────────────┘
```

### 4.2 Desktop App Architecture

NativePHP Desktop wraps the **existing web app** inside an Electron window — no separate frontend is built. The native layer adds:

- `FolderWatcherService` (chokidar) — watches configured directories
- `TrayManager` — system tray icon with upload queue status
- `OfflineQueueService` — SQLite-backed queue, retries on reconnect
- `NativeMenuBuilder` — File, Edit, View, Sync, Help menus
- `NotificationService` — macOS/Windows native notifications

The web app detects it is running inside Electron via a custom `X-NativePHP-Desktop: 1` header and adjusts the UI (hides browser-specific elements, enables drag-drop onto window).

### 4.3 Mobile App Architecture

NativePHP Mobile is a **new Laravel app** (not the existing server) bundled via Capacitor into an iOS/Android binary. It is a **client app** that connects to a configurable self-hosted server URL.

```
Mobile App (NativePHP Mobile)
├── Laravel routes/controllers — mobile-specific views
├── Capacitor plugins
│   ├── @capacitor/camera         — camera roll access
│   ├── @capacitor/filesystem     — local SQLite queue storage
│   ├── @capacitor/network        — WiFi detection
│   ├── @capacitor/push-notifications — upload complete alerts
│   └── @capacitor/background-runner  — background sync task
├── BackgroundSyncService         — runs every 15 min even when closed
├── OfflineQueueManager           — SQLite queue with retry logic
└── ChunkedUploadService          — 5MB chunks, resumable
```

### 4.4 API Design Principles

- All three clients use the same REST API under `/api/v2/`
- Sanctum token auth — tokens stored in device keychain (mobile/desktop)
- Every resource scoped to authenticated user (`user_id` on all queries)
- Admin users can query across all users
- WebSocket channel per user via Laravel Reverb for real-time events

---

## 5. Phase Plan

### Phase 1 — Bug Fixes + Web Polish (Week 1–2)

#### 5.1.1 Critical Bug Fixes

| # | File | Fix |
|---|------|-----|
| BUG-1 | `app/Jobs/ProcessImageAnalysis.php:441` | Change `config('services.python_ai.url')` → `config('ai.api_url')` |
| BUG-2 | `app/Http/Controllers/MediaController.php:47` | Add `if (empty($matches)) abort(416, 'Range Not Satisfiable');` after preg_match |
| BUG-3 | `.env` | Switch `DB_CONNECTION=sqlite` → `pgsql`, add all missing AI/service vars |
| BUG-4 | `app/Listeners/` (empty) | Create `ImageProcessedListener.php`, register in `EventServiceProvider` |
| BUG-5 | Multiple files | Standardise all Python AI URL refs to `config('ai.api_url')` |
| BUG-6 | `app/Models/ArchiveFile.php:119` | Implement real password detection via `ArchiveProcessor` |
| BUG-7 | `app/Services/CacheService.php:148` | Implement hit/miss tracking with cache tags |

#### 5.1.2 Web UI Polish

- Update sidebar navigation to **Unified Hub** layout: Photos / Search / Files / People / More
- Add **Memories strip** to gallery home (AI-curated date groups, horizontal scroll)
- Upgrade Files tab: smart type-filter chips (All / Photos / Videos / Documents / Audio / Archives)
- Add **Unified search** bar — single query searches photos + files + people
- Add **star / trash / restore** actions to media item context menus
- Add `.env.example` entries for all undocumented variables

#### 5.1.3 .env.example additions

```
AI_API_URL=http://python-ai:8000
PYTHON_AI_URL=http://python-ai:8000
NODE_PROCESSOR_URL=http://node-processor:3000
OLLAMA_URL=http://ollama:11434
OLLAMA_ENABLED=true
OLLAMA_MODEL=llava:13b-v1.6
AI_CIRCUIT_BREAKER_THRESHOLD=10
AI_CIRCUIT_BREAKER_RECOVERY=30
ELASTICSEARCH_HOST=http://elasticsearch:9200
REVERB_APP_ID=avinash-eye
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
```

---

### Phase 2 — API Layer + New Features (Week 3–5)

#### 5.2.1 New Database Migrations

```sql
-- Multi-user roles
CREATE TABLE user_roles (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT REFERENCES users(id),
  role VARCHAR(20) DEFAULT 'member', -- 'admin' | 'member'
  created_at TIMESTAMP, updated_at TIMESTAMP
);

-- Family groups
CREATE TABLE families (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(255),
  owner_id BIGINT REFERENCES users(id),
  shared_quota_bytes BIGINT DEFAULT 0,
  created_at TIMESTAMP, updated_at TIMESTAMP
);

CREATE TABLE family_members (
  id BIGSERIAL PRIMARY KEY,
  family_id BIGINT REFERENCES families(id),
  user_id BIGINT REFERENCES users(id),
  joined_at TIMESTAMP
);

-- Storage quotas
CREATE TABLE storage_quotas (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT REFERENCES users(id) UNIQUE,
  quota_bytes BIGINT DEFAULT 107374182400, -- 100 GB default
  used_bytes BIGINT DEFAULT 0,
  updated_at TIMESTAMP
);

-- Shareable links
CREATE TABLE share_links (
  id BIGSERIAL PRIMARY KEY,
  token VARCHAR(64) UNIQUE NOT NULL,
  user_id BIGINT REFERENCES users(id),
  resource_type VARCHAR(50), -- 'media_file' | 'album' | 'folder'
  resource_id BIGINT,
  password_hash VARCHAR(255) NULL,
  expires_at TIMESTAMP NULL,
  max_views INT NULL,
  view_count INT DEFAULT 0,
  created_at TIMESTAMP, updated_at TIMESTAMP
);

-- Device sync state
CREATE TABLE device_sync_states (
  id BIGSERIAL PRIMARY KEY,
  device_id VARCHAR(255),
  user_id BIGINT REFERENCES users(id),
  platform VARCHAR(20), -- 'mobile_ios' | 'mobile_android' | 'desktop_mac' | 'desktop_win'
  last_sync_at TIMESTAMP,
  last_synced_file_id BIGINT NULL,
  created_at TIMESTAMP, updated_at TIMESTAMP
);

-- Shared album members
CREATE TABLE shared_album_members (
  id BIGSERIAL PRIMARY KEY,
  album_id BIGINT,
  user_id BIGINT REFERENCES users(id),
  can_add BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP
);

-- Add to media_files
ALTER TABLE media_files ADD COLUMN user_id BIGINT REFERENCES users(id);
ALTER TABLE media_files ADD COLUMN starred_at TIMESTAMP NULL;
ALTER TABLE media_files ADD COLUMN trashed_at TIMESTAMP NULL;
ALTER TABLE media_files ADD COLUMN file_hash VARCHAR(64) NULL; -- SHA256 for dedup
```

#### 5.2.2 New Services

| Service | Purpose |
|---------|---------|
| `UserService` | Invite, role management, family membership |
| `QuotaService` | Track and enforce per-user storage limits |
| `ShareLinkService` | Generate/validate/revoke shareable tokens |
| `ResumableUploadService` | Chunked upload with offset tracking, supports resume |
| `DeduplicationService` | SHA256 hash check before accepting upload |
| `FamilyService` | Shared pool, cross-user album access |

#### 5.2.3 New API Routes (`routes/api.php`)

```php
// Auth + Users
POST   /api/v2/auth/login
POST   /api/v2/auth/register
POST   /api/v2/users/invite
GET    /api/v2/users/me
PATCH  /api/v2/users/me
DELETE /api/v2/users/{id}          // admin only

// Family
GET    /api/v2/family
POST   /api/v2/family/members
DELETE /api/v2/family/members/{id}

// Media + Files
GET    /api/v2/media                // paginated, user-scoped
POST   /api/v2/media/upload         // standard upload
POST   /api/v2/media/upload/init    // start resumable upload
PATCH  /api/v2/media/upload/{id}    // continue chunk
GET    /api/v2/media/dedup/{hash}   // check if file already exists
DELETE /api/v2/media/{id}
PATCH  /api/v2/media/{id}/star
PATCH  /api/v2/media/{id}/trash
PATCH  /api/v2/media/{id}/restore

// Sharing
POST   /api/v2/share-links          // create link
DELETE /api/v2/share-links/{token}  // revoke
GET    /share/{token}               // public viewer (no auth)

// Sync
GET    /api/v2/sync/state           // get device sync state
POST   /api/v2/sync/state           // update device sync state
GET    /api/v2/sync/delta           // items since last_sync_at

// Quota
GET    /api/v2/quota                // current user's usage

// Admin
GET    /api/v2/admin/users
GET    /api/v2/admin/stats
PATCH  /api/v2/admin/users/{id}/quota
```

#### 5.2.4 Laravel Reverb Setup

Install `laravel/reverb` as WebSocket server. Add `reverb` service to `docker-compose.yml`:

```yaml
reverb:
  build:
    context: ./docker/laravel
  command: php artisan reverb:start --host=0.0.0.0 --port=8080
  ports:
    - "8081:8080"
  depends_on:
    - laravel-app
```

**Broadcast events**:
- `MediaProcessed` — fires when AI pipeline completes for a file
- `UploadProgress` — fires every 10% during chunked upload  
- `SyncComplete` — fires when device sync state updated
- `QuotaWarning` — fires when user reaches 90% of quota

---

### Phase 3 — NativePHP Desktop App (Week 6–11, parallel with Phase 4)

#### 5.3.1 Setup

```bash
composer require nativephp/electron
php artisan native:install
php artisan native:publish
```

Configure `config/nativephp.php`:
- App name: "Avinash EYE"
- Window size: 1280×800, min 900×600
- Single window mode
- Auto-launch on login: configurable

#### 5.3.2 App Entry Point

`app/NativeAppServiceProvider.php`:

```php
Window::open()
    ->title('Avinash EYE')
    ->url(route('gallery'))
    ->width(1280)->height(800)
    ->minWidth(900)->minHeight(600);

SystemTray::label('Avinash EYE')
    ->icon(storage_path('app/tray-icon.png'))
    ->menu([
        NativeMenuItem::label('Open App')->click(fn() => Window::open()),
        NativeMenuItem::label('Upload Files')->click(fn() => $this->openUploadDialog()),
        NativeMenuItem::separator(),
        NativeMenuItem::label('Pause Sync')->click(fn() => FolderWatcherService::pause()),
        NativeMenuItem::separator(),
        NativeMenuItem::label('Quit')->click(fn() => App::quit()),
    ]);
```

#### 5.3.3 FolderWatcherService

- Uses chokidar (Electron native) via NativePHP's `Shell::exec()`
- Watches configured directories (stored in `config/watched_folders.json`)
- On new file detected:
  1. Calculate SHA256 hash
  2. Call dedup API — skip if already uploaded
  3. Add to `OfflineQueueService`
  4. Queue processor picks up and calls resumable upload API
- Excludes: `.DS_Store`, `Thumbs.db`, temp files, files under 1KB

#### 5.3.4 OfflineQueueService (Desktop)

SQLite database at `~/.avinash-eye/queue.db`:

```sql
CREATE TABLE upload_queue (
  id INTEGER PRIMARY KEY,
  file_path TEXT,
  file_hash TEXT,
  status TEXT DEFAULT 'pending', -- pending | uploading | done | failed
  upload_id TEXT NULL,            -- resumable upload ID
  bytes_uploaded INTEGER DEFAULT 0,
  attempts INTEGER DEFAULT 0,
  created_at DATETIME, updated_at DATETIME
);
```

Retry policy: exponential backoff, max 5 attempts, pause on server offline.

#### 5.3.5 Build Targets

| Platform | Output | Signing |
|----------|--------|---------|
| macOS | `.dmg` | Apple Developer ID (Gatekeeper) |
| Windows | `.exe` NSIS installer | Code signing certificate |
| Linux | `.AppImage` | No signing required |

---

### Phase 4 — NativePHP Mobile App (Week 6–11, parallel with Phase 3)

#### 5.4.1 Project Setup

```bash
# In mobile-app/ directory — DELETE existing AvinashEYE React Native project
rm -rf mobile-app/AvinashEYE

# Create new NativePHP Mobile project
composer create-project laravel/laravel mobile-app/AvinashEYE
cd mobile-app/AvinashEYE
composer require nativephp/mobile
php artisan native:install
php artisan native:mobile:add ios
php artisan native:mobile:add android
```

The mobile app is a **separate minimal Laravel installation** located at `mobile-app/AvinashEYE/` — it does NOT include the Docker stack, AI services, PostgreSQL, or Elasticsearch. It contains only mobile views, routes, and sync logic. It is a thin client that communicates with the self-hosted server (the main Docker stack) via a configurable URL stored in the device keychain.

#### 5.4.2 Onboarding Flow

1. **Server URL screen** — user enters their self-hosted server URL (e.g., `http://192.168.1.10:8080`). URL saved to device keychain.
2. **Login screen** — Sanctum API token auth. Token stored in device keychain.
3. **Permissions screen** — camera roll, notifications, background tasks. Explains why each is needed.
4. **Backup setup** — WiFi only toggle, include videos toggle. Starts initial sync.

#### 5.4.3 BackgroundSyncService

iOS: Registered as `BGAppRefreshTask` + `BGProcessingTask`  
Android: `WorkManager` with `PeriodicWorkRequest` (15-min minimum)

```
BackgroundSyncService::run():
  1. Check network — if cellular and wifi_only=true, skip
  2. Load last_sync_at from device storage
  3. Call GET /api/v2/sync/delta?since=last_sync_at
  4. For each new asset in camera roll since last_sync_at:
     a. Calculate SHA256 hash
     b. Call GET /api/v2/media/dedup/{hash} — skip if exists
     c. Add to OfflineQueueManager
  5. Process queue — chunked uploads, max 3 concurrent
  6. Update last_sync_at on success
  7. Fire push notification if > 0 files uploaded
```

#### 5.4.4 OfflineQueueManager (Mobile)

SQLite via `@capacitor/filesystem`:

```
queue.db — same schema as desktop queue, plus:
  - network_required: 'wifi' | 'any'
  - original_asset_id: device photo library ID
```

#### 5.4.5 Screen Specifications

**Tab 1 — Photos**
- Memories strip: horizontal scroll, AI-curated cards (top 5 date groups with most photos)
- Chronological grid: 3-column, date section headers
- Long-press: select mode with checkboxes, batch share/delete/download
- Pull to refresh: triggers sync check
- Backed-up badge: small cloud icon on each backed-up photo

**Tab 2 — Search**
- Unified search bar (placeholder: "Search photos, files, people…")
- Type filter chips: All / Photos / Videos / Docs / People
- People row: face cluster avatars, tap to filter by person
- Smart Albums grid: AI-curated (Trips, Food, Sunsets, Pets, etc.)
- Search uses `/api/v2/search?q=&type=` — calls both Elasticsearch + pgvector

**Tab 3 — Files**
- Type filter chips: All / Photos / Videos / Documents / Audio / Archives
- File list: icon, name, size, date, overflow menu (share, move, rename, delete, download)
- Folder navigation with breadcrumb header
- FAB: upload from device storage or camera
- Long-press: multi-select for batch operations
- Share: generates share link via API, copies to clipboard

**Tab 4 — People**
- AI face cluster cards in 3-column grid
- Each card: face avatar, name (or "Unknown"), photo count
- Unnamed clusters shown with dashed border
- Tap unnamed → name assignment sheet
- Tap named → filtered gallery of that person's photos

**Tab 5 — More**
- User card: avatar, name, email, storage quota progress bar
- Menu items: Albums, Shared Links, Family, Trash, Backup Status, Settings
- Backup Status: live sync progress with pulsing dot, last sync time, queue count
- Settings: server URL, WiFi only toggle, include videos toggle, backup frequency, biometric lock

#### 5.4.6 Media Viewer

- Full-screen swipe navigation between photos
- Double-tap to zoom, pinch to zoom
- Bottom toolbar: share, download, delete, info
- Info panel: AI caption, detected faces, EXIF data, file size
- Delete shows confirmation then removes from server + local cache

#### 5.4.7 Build Targets

| Platform | Output | Distribution |
|----------|--------|-------------|
| iOS | `.ipa` | TestFlight (personal) / Ad Hoc |
| Android | `.apk` | Direct install (sideload) |

---

### Phase 5 — Integration QA + Ship (Week 12)

#### 5.5.1 End-to-End Test Scenarios

1. **Full sync loop**: Take photo on phone → appears on web + desktop within 60 seconds (on WiFi)
2. **Offline queue**: Disable WiFi → take 5 photos → re-enable WiFi → all 5 upload
3. **Dedup**: Upload same photo twice → only one copy stored
4. **Multi-user isolation**: User A logs in → cannot see User B's files
5. **Family sharing**: User A shares album → User B sees it in Family tab
6. **Share link**: Create link with expiry + password → open in incognito → verify access control
7. **Quota enforcement**: Fill quota → next upload rejected with 413 error + quota warning notification
8. **Desktop folder watch**: Drop file into watched folder → appears in gallery within 5 seconds
9. **Archive password detection**: Upload password-protected ZIP → `hasPassword()` returns true

#### 5.5.2 Documentation Updates

- Update `CLAUDE.md` — new architecture section (NativePHP apps, API v2)
- Update `PROJECT_STATUS_REPORT.md` — mark all items resolved, add v2 status
- `docs/MOBILE_SETUP.md` — TestFlight / sideload install guide
- `docs/DESKTOP_SETUP.md` — `.dmg` / `.exe` install guide + watched folders setup
- `docs/MULTI_USER_SETUP.md` — invite system, family sharing, quotas

#### 5.5.3 Release

- Tag `v2.0.0` on master
- Update `docker-compose.yml` — add Reverb service
- Update `start-production.sh` — initialise Reverb config
- GitHub release — attach `.dmg`, `.exe`, `.AppImage` binaries
- Update main `README.md` with v2 features

---

## 6. Data Flow — Photo Auto-Backup

```
[Phone Camera]
     │  new photo detected by BackgroundSyncService
     ▼
[SHA256 hash]
     │  GET /api/v2/media/dedup/{hash}
     ├── EXISTS → skip (already backed up)
     └── NEW ──►
              │  POST /api/v2/media/upload/init  (resumable)
              │  PATCH /api/v2/media/upload/{id} (5MB chunks)
              │  (retries on failure, pauses on cellular if wifi_only)
              ▼
         [Laravel API]
              │  dispatches ProcessImageAnalysis job
              ▼
         [Queue Worker]
              │  calls Python AI → CLIP embeddings + BLIP caption + face detection
              │  calls Node.js → thumbnails + WebP conversion
              │  calls Ollama → enhanced scene description (optional)
              ▼
         [PostgreSQL + pgvector + Elasticsearch]
              │  media record saved, embedding indexed, ES document created
              ▼
         [Laravel Reverb] ── broadcasts MediaProcessed event ──►
              │                                                  │
         [Web app]                                    [Mobile + Desktop]
         Livewire updates                             WebSocket listener
         gallery in real-time                        refreshes gallery
```

---

## 7. Error Handling

| Scenario | Behaviour |
|----------|-----------|
| Server offline (mobile) | Queue uploads locally, retry every 15 min on WiFi |
| Server offline (desktop) | Queue uploads in SQLite, retry on reconnect |
| Quota exceeded | API returns 413, client shows quota warning, upload rejected |
| Duplicate file | API returns 200 with existing file ID, client marks as backed up |
| Chunked upload interrupted | Client resumes from last confirmed offset |
| AI service down | Circuit breaker opens, file stored without AI analysis, requeued when service recovers |
| Share link expired/wrong password | API returns 403, public viewer shows friendly error |
| Face recognition fails | File stored without face data, no error shown to user |

---

## 8. Testing Strategy

| Layer | Approach |
|-------|---------|
| Bug fixes | Add regression test for each fix before merging |
| API v2 routes | Feature tests for every new endpoint (auth, quota, sharing, sync) |
| SearchService | Integration test with real pgvector similarity queries |
| FaceClusteringService | Unit test clustering logic with fixture face encodings |
| ProcessImageAnalysis job | Feature test with mocked AI service responses |
| Quota enforcement | Test at boundary: 99% full, 100% full, over quota |
| Desktop folder watcher | Integration test: drop file → verify queue entry created |
| Mobile sync service | Unit test with mock network states and queue states |
| Multi-user isolation | Test that user-scoped queries never leak across users |

---

## 9. New Dependencies

### Server (composer.json)

```json
"laravel/reverb": "^1.0",
"nativephp/electron": "^0.8"
```

### Mobile App (composer.json)

```json
"nativephp/mobile": "^0.1",
"laravel/sanctum": "^4.0"
```

### Mobile App (package.json / Capacitor)

```json
"@capacitor/camera": "^6.0",
"@capacitor/filesystem": "^6.0",
"@capacitor/network": "^6.0",
"@capacitor/push-notifications": "^6.0",
"@capacitor/background-runner": "^1.0"
```

---

## 10. Files to Create / Modify

### New Files

| File | Purpose |
|------|---------|
| `app/Listeners/ImageProcessedListener.php` | Handle ImageProcessed event (BUG-4 fix) |
| `app/Services/UserService.php` | Invite, roles, family management |
| `app/Services/QuotaService.php` | Storage quota tracking and enforcement |
| `app/Services/ShareLinkService.php` | Shareable link generation and validation |
| `app/Services/ResumableUploadService.php` | Chunked upload with offset tracking |
| `app/Services/DeduplicationService.php` | SHA256 dedup check |
| `app/Services/FamilyService.php` | Family pool and shared access |
| `app/NativeAppServiceProvider.php` | Desktop app entry point (NativePHP) |
| `app/Services/FolderWatcherService.php` | Desktop folder watch integration |
| `app/Services/OfflineQueueService.php` | Desktop upload queue (SQLite) |
| `app/Http/Controllers/Api/V2/` | All new API v2 controllers |
| `app/Models/ShareLink.php` | Shareable link model |
| `app/Models/StorageQuota.php` | Quota model |
| `app/Models/Family.php` | Family group model |
| `app/Models/DeviceSyncState.php` | Device sync state model |
| `database/migrations/2026_04_*` | All new migrations (see §5.2.1) |
| `routes/api_v2.php` | API v2 routes — registered in `bootstrap/app.php` alongside existing `api.php` |
| `mobile-app/AvinashEYE/` | Full NativePHP Mobile project (new) |
| `docs/MOBILE_SETUP.md` | Mobile install guide |
| `docs/DESKTOP_SETUP.md` | Desktop install guide |
| `docs/MULTI_USER_SETUP.md` | Multi-user + family setup guide |

### Modified Files

| File | Change |
|------|--------|
| `app/Jobs/ProcessImageAnalysis.php:441` | Fix config key (BUG-1) |
| `app/Http/Controllers/MediaController.php:47` | Fix range regex (BUG-2) |
| `.env` + `.env.example` | PostgreSQL + all missing vars (BUG-3) |
| `app/Providers/EventServiceProvider.php` | Register ImageProcessedListener (BUG-4) |
| `app/Services/AiService.php` + others | Standardise config key (BUG-5) |
| `app/Models/ArchiveFile.php:119` | Real hasPassword() (BUG-6) |
| `app/Services/CacheService.php:148` | Real getStats() (BUG-7) |
| `database/migrations/*_create_media_files_table.php` | Add user_id, starred_at, trashed_at, file_hash |
| `docker-compose.yml` | Add Reverb service |
| `start-production.sh` | Init Reverb config |
| `resources/views/layouts/app.blade.php` | Unified Hub sidebar nav |
| `resources/views/livewire/enhanced-image-gallery.blade.php` | Memories strip |
| `routes/web.php` | Add share link public route |
| `CLAUDE.md` | Document new architecture |
| `PROJECT_STATUS_REPORT.md` | Mark all items resolved |

---

## 11. Risks and Mitigations

| Risk | Likelihood | Mitigation |
|------|-----------|-----------|
| NativePHP Mobile (early access) API instability | Medium | Pin to specific version, isolate behind service layer |
| Capacitor background task killed by iOS | Medium | Implement polling fallback when app is opened |
| NativePHP Desktop Electron bundle size large | Low | Configure webpack to tree-shake, target ~150MB |
| Multi-user migration breaks existing single-user data | Medium | Migration assigns all existing media to default admin user |
| Reverb WebSocket port conflicts with existing services | Low | Use port 8081 on host, 8080 inside container |
| pgvector user_id scope degrades search performance | Low | Add composite index on (user_id, embedding) |
