# Project Status Report — Avinash-EYE v2

**Branch:** feature/v2-implementation  
**Phase:** Phase 1 Complete  
**Date:** 2026-04-11

---

## Phase 1: Bug Fixes (7 bugs)

All 7 Phase 1 bugs have been resolved.

| ID    | Description                                      | Status     | Commit  |
|-------|--------------------------------------------------|------------|---------|
| BUG-1 | Config keys standardised in ProcessImageAnalysis | ✅ Fixed   | 5d11450 |
| BUG-2 | MediaController Range header guard + RFC 7233    | ✅ Fixed   | 635ab61 |
| BUG-3 | .env uses PostgreSQL, .env.example created       | ✅ Fixed   | 5b7f873 |
| BUG-4 | ImageProcessedListener created and registered    | ✅ Fixed   | daa8dd4 |
| BUG-5 | Config keys fixed in MonitorSystem.php           | ✅ Fixed   | 4b611aa |
| BUG-6 | ArchiveFile::hasPassword() uses is_encrypted     | ✅ Fixed   | 23331a1 |
| BUG-7 | CacheService::getStats() returns real counters   | ✅ Fixed   | 6805c93 |

### Bug fix details

- **BUG-1**: Replaced `services.python_ai.url` / `services.ollama.url` with the canonical `ai.api_url` / `ai.ollama_url` keys from `config/ai.php` in `app/Jobs/ProcessImageAnalysis.php`. Added missing `ai.ollama_url` entry to `config/ai.php`.
- **BUG-2**: Added guard against malformed Range headers, RFC 7233-compliant `Content-Range` header on 416 responses, suffix-range support (`bytes=-N`), and inverted-range rejection in `app/Http/Controllers/MediaController.php`. Five new tests in `tests/Feature/MediaControllerRangeTest.php`.
- **BUG-3**: Switched `DB_CONNECTION` from `mysql` to `pgsql` in `.env` and created a complete `.env.example` documenting all required variables.
- **BUG-4**: Created `app/Listeners/ImageProcessedListener.php` and registered it against `ImageProcessed` event in `app/Providers/EventServiceProvider.php`. Tests in `tests/Feature/ImageProcessedListenerTest.php`.
- **BUG-5**: Fixed stale `services.ollama.*` config key references in `app/Console/Commands/MonitorSystem.php` to use the correct `ai.*` keys.
- **BUG-6**: Replaced the always-false stub in `ArchiveFile::hasPassword()` with a read of the actual `is_encrypted` database column. Tests in `tests/Unit/ArchiveFileTest.php`.
- **BUG-7**: Replaced the hardcoded `['hits' => 0, 'misses' => 0]` stub in `CacheService::getStats()` with real atomic counters stored in cache. Tests in `tests/Unit/CacheServiceStatsTest.php`.

---

## Phase 1: Web Polish (3 features)

| Feature                          | Status       | Commits                  |
|----------------------------------|--------------|--------------------------|
| Unified Hub sidebar navigation   | ✅ Shipped   | 92956ac                  |
| Memories strip on gallery home   | ✅ Shipped   | 6dc787a, 1211754, 014b3a1|
| Star/trash actions on media cards| ✅ Shipped   | f3dc61f, 3480179         |

### Web polish details

- **Unified Hub sidebar**: Updated the Livewire sidebar to use consistent Unified Hub navigation with grouped sections (Home, Explore, Manage, System). Commit `92956ac`.
- **Memories strip**: Added a horizontal-scrolling strip of monthly memory cards above the photo grid, grouped by `date_taken`, limited to the last 2 years / 8 months. A regression (`taken_at` → `date_taken` column name) was caught and fixed during Task 11 verification (commit `014b3a1`).
- **Star/trash actions**: Added per-card star (favourite) and trash (soft-delete) action buttons to gallery media cards. Actions persist to the database and refresh the UI. Commits `f3dc61f`, `3480179`.

---

## Test Suite — Phase 1 Final State

**Run date:** 2026-04-11  
**Command:** `php artisan test` (local PostgreSQL 16, no Docker)

| Result  | Count |
|---------|-------|
| Passing | 71    |
| Failing | 25    |
| **Total** | **96** |

### Newly added Phase 1 tests (all passing)

| Test file                                      | Tests | Status |
|------------------------------------------------|-------|--------|
| `tests/Feature/MediaControllerRangeTest.php`   | 5     | ✅ All pass |
| `tests/Feature/ImageProcessedListenerTest.php` | 2     | ✅ All pass |
| `tests/Unit/ArchiveFileTest.php`               | 3     | ✅ All pass |
| `tests/Unit/CacheServiceStatsTest.php`         | 3     | ✅ All pass |

### Pre-existing failures (not introduced by Phase 1)

| Test class / description                            | Count | Root cause |
|-----------------------------------------------------|-------|------------|
| `AiServiceTest` (all 9)                             | 9     | Constructor argument count mismatch — pre-dates Phase 1 |
| `ImageFileModelTest` — `searchSimilar`              | 1     | Wrong arg type to `searchSimilar` — pre-dates Phase 1 |
| `SettingModelTest` — boolean/array casting (4)      | 4     | Boolean/array casting issues — pre-dates Phase 1 |
| `EnhancedImageGalleryTest` — `$images` property (5) | 5    | Property renamed from `$images` to `$files` in Dec 2025 (commit `b9c109f`), before Phase 1 |
| `RoutesTest` + `ExampleTest` — Vite manifest (6)   | 6     | `public/build/manifest.json` absent; Vite not built in test env |

### Regression found and fixed during Task 11

- **Issue**: Memories strip (`EnhancedImageGallery::memories()`) queried `taken_at` but the actual column is `date_taken`, causing all 27 EnhancedImageGallery tests to fail with PostgreSQL `SQLSTATE[42703]: Undefined column`.
- **Fix**: Changed all four `taken_at` references to `date_taken` in the computed property.
- **Commit**: `014b3a1` — "fix: use correct date_taken column in memories strip query"
- **Impact**: 22 previously failing tests now pass.

---

## Known Issues Tracked for Phase 2+

- `AiServiceTest`: Needs constructor signature update to match current `AiService`.
- `ImageFileModelTest::searchSimilar`: Argument type mismatch needs investigation.
- `SettingModelTest`: JSON/boolean/array casting behaviour differences on PostgreSQL.
- `EnhancedImageGalleryTest`: Tests reference `$images` property; component uses `$files` — test assertions need updating.
- `RoutesTest` / `ExampleTest`: Tests need `VITE_TESTING=true` or a stub manifest to run without a full build.
