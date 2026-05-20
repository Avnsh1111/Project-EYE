# Phase 2 — API Layer + New Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the shared API v2 layer used by all three clients — multi-user accounts, family sharing, storage quotas, shareable links, resumable uploads, deduplication, and Laravel Reverb WebSocket. No client apps are built in this phase.

**Architecture:** New routes in `routes/api_v2.php` registered alongside existing `routes/api.php`. All new models go in `app/Models/`. New services in `app/Services/`. All resources scoped to `auth()->id()`. Admin users bypass user scoping. Migrations run sequentially — existing single-user data is assigned to the first admin user.

**Tech Stack:** Laravel 12, Sanctum, Laravel Reverb (WebSocket), PostgreSQL, Pest PHP

**Prerequisite:** Phase 1 must be complete (bug fixes applied, PostgreSQL configured).

---

## File Map

| Action | File |
|--------|------|
| Create | `database/migrations/2026_04_10_*_create_families_table.php` |
| Create | `database/migrations/2026_04_10_*_create_storage_quotas_table.php` |
| Create | `database/migrations/2026_04_10_*_create_share_links_table.php` |
| Create | `database/migrations/2026_04_10_*_create_device_sync_states_table.php` |
| Create | `database/migrations/2026_04_10_*_add_user_id_to_media_files.php` |
| Create | `app/Models/Family.php` |
| Create | `app/Models/StorageQuota.php` |
| Create | `app/Models/ShareLink.php` |
| Create | `app/Models/DeviceSyncState.php` |
| Modify | `app/Models/User.php` |
| Modify | `app/Models/MediaFile.php` |
| Create | `app/Services/QuotaService.php` |
| Create | `app/Services/ShareLinkService.php` |
| Create | `app/Services/ResumableUploadService.php` |
| Create | `app/Services/DeduplicationService.php` |
| Create | `app/Services/FamilyService.php` |
| Create | `app/Http/Controllers/Api/V2/AuthController.php` |
| Create | `app/Http/Controllers/Api/V2/MediaController.php` |
| Create | `app/Http/Controllers/Api/V2/ShareLinkController.php` |
| Create | `app/Http/Controllers/Api/V2/SyncController.php` |
| Create | `app/Http/Controllers/Api/V2/QuotaController.php` |
| Create | `app/Http/Controllers/Api/V2/AdminController.php` |
| Create | `app/Http/Controllers/Api/V2/FamilyController.php` |
| Create | `routes/api_v2.php` |
| Modify | `bootstrap/app.php` |
| Create | `tests/Feature/Api/V2/QuotaTest.php` |
| Create | `tests/Feature/Api/V2/ShareLinkTest.php` |
| Create | `tests/Feature/Api/V2/SyncDeltaTest.php` |
| Create | `tests/Feature/Api/V2/MultiUserIsolationTest.php` |
| Create | `tests/Feature/Api/V2/DeduplicationTest.php` |
| Create | `tests/Feature/Api/V2/ResumableUploadTest.php` |

---

## Task 1: Database migrations

- [ ] Create all 5 migrations (see schema in design spec §5.2.1)
- [ ] Run `php artisan migrate`
- [ ] Assign existing media_files rows to admin user: `UPDATE media_files SET user_id = (SELECT id FROM users WHERE id = 1) WHERE user_id IS NULL`
- [ ] Add `NOT NULL` constraint after backfill: alter column
- [ ] Commit migrations

## Task 2: Models + relationships

- [ ] Create `Family`, `StorageQuota`, `ShareLink`, `DeviceSyncState` models with fillable + casts
- [ ] Add `hasOne(StorageQuota::class)`, `hasMany(ShareLink::class)`, `belongsTo(Family::class)` to `User`
- [ ] Add `belongsTo(User::class)` to `MediaFile`
- [ ] Add global scope to `MediaFile` that filters by `auth()->id()` (skips for admin)
- [ ] Write unit tests for each model relationship
- [ ] Commit models

## Task 3: QuotaService

- [ ] Implement `QuotaService::getUsage(User $user): array` — returns `['used' => bytes, 'total' => bytes, 'percent' => float]`
- [ ] Implement `QuotaService::checkBeforeUpload(User $user, int $bytes): void` — throws `QuotaExceededException` if over limit
- [ ] Implement `QuotaService::increment(User $user, int $bytes): void` — called after successful upload
- [ ] Implement `QuotaService::decrement(User $user, int $bytes): void` — called after delete
- [ ] Write `tests/Feature/Api/V2/QuotaTest.php` — test enforcement at boundary (99%, 100%, over)
- [ ] Commit

## Task 4: DeduplicationService

- [ ] Implement `DeduplicationService::hashFile(string $path): string` — SHA256 of file contents
- [ ] Implement `DeduplicationService::isDuplicate(string $hash, int $userId): bool` — query media_files by file_hash + user_id
- [ ] Add `file_hash` migration to media_files (VARCHAR 64, nullable, indexed)
- [ ] Write `tests/Feature/Api/V2/DeduplicationTest.php` — upload same file twice, second call returns existing ID
- [ ] Commit

## Task 5: ShareLinkService

- [ ] Implement `ShareLinkService::create(array $params): ShareLink` — generates secure random token, hashes optional password
- [ ] Implement `ShareLinkService::validate(string $token, ?string $password): ShareLink` — checks expiry, password, view limit
- [ ] Implement `ShareLinkService::revoke(string $token, int $userId): void`
- [ ] Write `tests/Feature/Api/V2/ShareLinkTest.php` — test expiry, wrong password, max views, valid access
- [ ] Commit

## Task 6: ResumableUploadService

- [ ] Implement `initUpload(User $user, string $filename, int $totalBytes): array` — returns `['upload_id' => uuid, 'chunk_size' => 5242880]`
- [ ] Implement `appendChunk(string $uploadId, int $offset, string $data): array` — writes chunk to temp file, returns `['received' => bytes]`
- [ ] Implement `finalise(string $uploadId): MediaFile` — moves temp file to storage, creates MediaFile, dispatches job
- [ ] Write `tests/Feature/Api/V2/ResumableUploadTest.php` — upload in 3 chunks, verify assembled file matches
- [ ] Commit

## Task 7: API v2 routes + controllers

- [ ] Create `routes/api_v2.php` with all routes from spec §5.2.3
- [ ] Register in `bootstrap/app.php`: `->withRouting(apiPrefix: 'api', then: fn() => Route::middleware('api')->prefix('api/v2')->group(base_path('routes/api_v2.php')))`
- [ ] Implement all V2 controllers (thin — delegate to services)
- [ ] Write `tests/Feature/Api/V2/MultiUserIsolationTest.php` — user A cannot read user B's media
- [ ] Commit controllers

## Task 8: SyncController + delta endpoint

- [ ] Implement `GET /api/v2/sync/delta?since=ISO8601` — returns media_files created/updated after `since` for authenticated user
- [ ] Implement `POST /api/v2/sync/state` — upsert DeviceSyncState for device_id + user
- [ ] Write `tests/Feature/Api/V2/SyncDeltaTest.php` — upload 3 files, call delta with timestamp before uploads, verify all 3 returned
- [ ] Commit

## Task 9: Laravel Reverb WebSocket

- [ ] `composer require laravel/reverb`
- [ ] `php artisan reverb:install`
- [ ] Add `reverb` service to `docker-compose.yml` (port 8081, health check)
- [ ] Update `config/broadcasting.php` to use reverb as default
- [ ] Verify `ImageProcessed` event broadcasts to `image-processing` channel
- [ ] Test: dispatch event, verify it appears on WebSocket channel via tinker
- [ ] Commit Reverb config + docker-compose update

## Task 10: Admin dashboard API

- [ ] `GET /api/v2/admin/users` — paginated list with quota usage per user
- [ ] `GET /api/v2/admin/stats` — total files, total storage, per-user breakdown
- [ ] `PATCH /api/v2/admin/users/{id}/quota` — update quota_bytes for a user
- [ ] Gate all admin routes behind `middleware('can:admin')` policy
- [ ] Write admin tests with non-admin user asserting 403
- [ ] Commit

---

## Phase 2 Complete Checklist

- [ ] All 5 migrations run clean
- [ ] Existing data backfilled to admin user
- [ ] Multi-user isolation verified by tests
- [ ] Quota enforcement tested at boundaries
- [ ] Deduplication prevents double uploads
- [ ] Resumable upload assembles correctly from chunks
- [ ] Share links respect expiry + password + view limit
- [ ] Sync delta returns correct set of files
- [ ] Reverb WebSocket broadcasting verified
- [ ] Admin endpoints return 403 for non-admin users
- [ ] All new tests passing
