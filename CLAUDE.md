# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Avinash-EYE is a self-hosted, AI-powered multimedia management system that runs 100% offline with local AI models. It combines Laravel 12 + Livewire 3 (frontend/backend), Python FastAPI (AI service), Node.js (image processing), PostgreSQL + pgvector (database/vector search), and Ollama (optional LLM) in a Docker Compose stack.

**Key Differentiator**: Multi-modal AI analysis (images, videos, audio, documents, archives) with semantic search, face recognition, and zero cloud dependencies.

## Development Commands

### Environment Setup & Initialization

```bash
# Production deployment (auto-initializes everything)
./start-production.sh

# Manual Docker Compose startup
docker compose up -d --build

# Stop all services
docker compose down

# Complete system reset (DESTRUCTIVE - deletes all data)
./fresh-start.sh
```

### Development Workflow

```bash
# Run Laravel development server (with queue, logs, and vite)
composer run dev

# Build frontend assets
npm run build

# Watch frontend changes
npm run dev
```

### Database Operations

```bash
# Run migrations
docker compose exec laravel-app php artisan migrate

# Fresh migration with seeders
docker compose exec laravel-app php artisan migrate:fresh --seed

# Seed settings only
docker compose exec laravel-app php artisan db:seed --class=SettingsSeeder

# Access PostgreSQL CLI
docker compose exec db psql -U avinash -d avinash_eye

# Create database backup
docker compose exec db pg_dump -U avinash avinash_eye > backup.sql
```

### Testing

```bash
# Run all tests
docker compose exec laravel-app php artisan test

# Run specific test file
docker compose exec laravel-app php artisan test --filter=AiServiceTest

# Run Pest tests
docker compose exec laravel-app ./vendor/bin/pest

# Run specific Pest test
docker compose exec laravel-app ./vendor/bin/pest --filter="can analyze image"
```

### Code Quality

```bash
# Format code with Laravel Pint
docker compose exec laravel-app ./vendor/bin/pint

# Check code without fixing
docker compose exec laravel-app ./vendor/bin/pint --test
```

### Media Processing Commands

```bash
# Reprocess media files (for updates to AI models)
docker compose exec laravel-app php artisan images:reprocess --batch=50

# Reprocess only missing features
docker compose exec laravel-app php artisan images:reprocess --only-missing

# Force reprocess all files
docker compose exec laravel-app php artisan images:reprocess --force

# Export AI training data
docker compose exec laravel-app php artisan export:training-data --limit=1000
```

### Queue Management

```bash
# Monitor queue worker logs
docker compose logs -f queue-worker

# Check failed jobs
docker compose exec laravel-app php artisan queue:failed

# Retry all failed jobs
docker compose exec laravel-app php artisan queue:retry all

# Clear failed jobs
docker compose exec laravel-app php artisan queue:flush

# Restart queue worker
docker compose restart queue-worker
```

### User Management

```bash
# Create default admin user
docker compose exec laravel-app php artisan user:create-default

# Create custom user
docker compose exec laravel-app php artisan user:create-default \
  --email=admin@example.com \
  --password=SecurePass123! \
  --name="Admin User"
```

### Service Management

```bash
# View all service logs
docker compose logs -f

# View specific service logs
docker compose logs -f python-ai
docker compose logs -f laravel-app
docker compose logs -f queue-worker
docker compose logs -f scheduler

# Check service health
curl http://localhost:8080              # Laravel app
curl http://localhost:8000/health       # Python AI
curl http://localhost:3000/health       # Node processor
curl http://localhost:11434/api/tags    # Ollama

# Restart specific service
docker compose restart python-ai
docker compose restart queue-worker
```

## Architecture Overview

### High-Level Structure

```
┌─────────────────────────────────────────────────────────────┐
│  Browser (Livewire 3 Reactive UI)                           │
└────────────┬────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────┐
│  NGINX (Port 8080) → Laravel 12 + Livewire 3 (PHP 8.4-FPM) │
│  ├─ Livewire Components (11 components)                     │
│  ├─ Services Layer (14+ services)                           │
│  ├─ Queue Jobs (background processing)                      │
│  └─ Models (STI hierarchy: MediaFile base class)            │
└────┬────────┬────────┬────────┬────────┬────────────────────┘
     │        │        │        │        │
  ┌──▼──┐  ┌─▼──┐  ┌──▼──┐  ┌──▼──┐  ┌──▼──┐
  │ PG  │  │ Py │  │Node │  │Elas-│  │Olla-│
  │+vec │  │ AI │  │Proc │  │tick │  │ ma  │
  └─────┘  └────┘  └─────┘  └─────┘  └─────┘
```

### Service Communication Flow

1. **User Upload** → Nginx → Laravel → Storage
2. **Laravel** → Dispatches `ProcessImageAnalysis` job to queue
3. **Queue Worker** → Picks up job → Calls Services
4. **Services Layer** → Orchestrates AI analysis:
   - `AiService` → Python FastAPI (AI analysis)
   - `NodeImageProcessorService` → Node.js (thumbnails)
   - `FaceClusteringService` → Face detection/clustering
   - `MetadataService` → EXIF extraction
5. **Python AI** → Optionally calls Ollama for enhanced descriptions
6. **Results** → Stored in PostgreSQL with pgvector embeddings
7. **Search** → `SearchService` → pgvector cosine similarity

### Key Architectural Patterns

**Single Table Inheritance (STI)**: All media types inherit from `MediaFile` base model with `media_type` discriminator:
- `ImageFile` (images)
- `VideoFile` (videos)
- `AudioFile` (audio)
- `DocumentFile` (documents)
- `ArchiveFile` (archives)

**Service Layer Pattern**: Business logic isolated in dedicated services (14+ services in `app/Services/`):
- `AiService`: Handles all Python AI communication with circuit breaker + retry
- `CircuitBreakerService`: Prevents cascading failures
- `RetryService`: Exponential backoff with jitter
- `CacheService`: Smart caching for AI results
- `MediaProcessorService`: Coordinates multimedia processing
- `SearchService`: Semantic + text search with pgvector

**Queue-Based Processing**: All heavy AI operations run asynchronously:
- Queue: `database` driver
- Jobs: `ProcessImageAnalysis`, `ProcessBatchImages`, `ProcessBatchUpload`
- Workers: Dedicated Docker container (`queue-worker`) running 24/7
- Config: `--tries=3 --timeout=300 --sleep=3 --max-jobs=100`

**Microservices Architecture**:
- Laravel: Web UI, API, business logic, orchestration
- Python FastAPI: AI inference (Florence-2, CLIP, Whisper, Tesseract, PaddleOCR)
- Node.js: Fast image processing with Sharp library
- Ollama: Optional LLM enhancement (LLaVA, Qwen)
- Elasticsearch: Advanced search capabilities

## Important Conventions

### AI Model Configuration

AI models are configurable via `app/Models/Setting.php` key-value store:

```php
// Captioning models
'captioning_model' => 'florence' | 'blip'

// Embedding models (affects vector dimension)
'embedding_model' => 'clip' (512d) | 'siglip' (768d) | 'aimv2' (1024d)

// OCR engines
'ocr_engine' => 'auto' | 'paddleocr' | 'tesseract'

// Ollama integration
'ollama_enabled' => true | false
'ollama_model' => 'llava:13b-v1.6' | 'qwen2.5:7b' | etc.
```

**Critical**: Vector embeddings dimension MUST match the embedding model. Migration handles pgvector dimensions dynamically.

### Resilience Patterns

**Circuit Breaker** (`CircuitBreakerService`):
- Threshold: 10 failures → opens circuit
- Recovery: 30 seconds timeout
- Prevents cascade failures when AI service is down
- Used by: `AiService`, all AI-dependent services

**Retry Logic** (`RetryService`):
- Max attempts: 3
- Initial delay: 100ms
- Exponential backoff: 2x multiplier
- Max delay: 10 seconds
- Jitter: Enabled (prevents thundering herd)

**Adaptive Timeouts** (`config/ai.php`):
- Image analysis: 30s (180s with Ollama)
- Video analysis: 120s (240s with Ollama)
- Document OCR: 60s (180s with Ollama)
- Audio transcription: 120s (180s with Ollama)

### Face Recognition System

Uses `face_recognition` library (dlib-based, 99.38% accuracy):

1. **Detection**: Extracts face encodings (128-dimension vectors)
2. **Clustering**: Cosine similarity with 0.6 threshold
3. **Storage**: `face_clusters` table with reference face + all instances
4. **Naming**: User assigns names via UI → updates all photos

Models:
- `FaceCluster`: Represents a person/pet
- `DetectedFace`: Individual face instance in a photo
- Relationship: `MediaFile` hasMany `DetectedFace` belongsTo `FaceCluster`

### Vector Search Implementation

PostgreSQL + pgvector extension for semantic search:

```sql
-- Similarity search (cosine distance)
SELECT *, 1 - (embedding <=> query_vector) AS similarity
FROM media_files
WHERE 1 - (embedding <=> query_vector) >= 0.35
ORDER BY embedding <=> query_vector
LIMIT 20
```

- Index: IVFFlat for approximate nearest neighbor (fast)
- Minimum similarity: 0.35 (35%) - configurable via `MediaFile::MIN_SIMILARITY`
- Embedding models: CLIP (512d), SigLIP (768d), AIMv2 (1024d)

### Queue Job Patterns

All background processing jobs follow this pattern:

```php
class ProcessImageAnalysis implements ShouldQueue
{
    public $tries = 3;              // Retry failed jobs 3 times
    public $timeout = 300;          // 5-minute timeout
    public $maxExceptions = 3;      // Max exceptions before failing

    public function handle()
    {
        // 1. Fetch MediaFile
        // 2. Call AiService (handles circuit breaker + retry)
        // 3. Update MediaFile with results
        // 4. Fire events (ImageProcessed)
    }

    public function failed(Throwable $exception)
    {
        // Log failure, notify, mark media file as failed
    }
}
```

Queue worker runs 24/7 in dedicated Docker container with auto-restart.

## Testing Strategy

Project uses **Pest PHP** (modern testing framework):

```bash
# Run all tests
php artisan test

# Run specific suite
./vendor/bin/pest --filter=AiService

# Watch mode (auto-rerun on changes)
./vendor/bin/pest --watch
```

Test structure:
- `tests/Unit/`: Unit tests for models, services (isolated, fast)
- `tests/Feature/`: Integration tests for Livewire components, routes
- `tests/Pest.php`: Global helpers and custom matchers

Key test files:
- `tests/Unit/AiServiceTest.php`: AI service with circuit breaker
- `tests/Feature/EnhancedImageGalleryTest.php`: Gallery component
- `tests/Feature/SettingsTest.php`: Settings persistence

## Common Gotchas & Solutions

### 1. Circuit Breaker Open

**Symptom**: "Circuit breaker is OPEN - rejecting request"

**Cause**: AI service failed 10+ times in a row

**Fix**:
```bash
# Clear circuit breaker state
docker compose exec laravel-app php artisan cache:clear

# Or manually reset
php artisan tinker
Cache::forget('circuit_breaker:ai_service:state');
```

### 2. Queue Jobs Not Processing

**Symptom**: Jobs stuck in `pending` status

**Check**:
```bash
# Verify queue worker is running
docker compose ps queue-worker

# Check worker logs
docker compose logs -f queue-worker

# Restart if needed
docker compose restart queue-worker
```

### 3. NULL Model Configuration

**Symptom**: Jobs fail with "AI service returned error" or NULL captioning_model

**Fix**:
```bash
# Seed settings
docker compose exec laravel-app php artisan db:seed --class=SettingsSeeder

# Or manually set
php artisan tinker
App\Models\Setting::set('captioning_model', 'florence');
App\Models\Setting::set('embedding_model', 'clip');
```

### 4. Python AI Not Loading Models

**Symptom**: Python service crashes or takes forever to start

**Fix**:
```bash
# Increase Docker memory to 16GB minimum
# Check logs for specific error
docker compose logs python-ai | tail -100

# Models are cached in volume, first pull takes ~5-10 minutes
# Subsequent starts: 30-60 seconds
```

### 5. pgvector Dimension Mismatch

**Symptom**: "expected 512 dimensions, not 768"

**Cause**: Changed embedding model without updating database

**Fix**:
```bash
# Migration handles this automatically
# But if manually changed, update vector column:
ALTER TABLE media_files ALTER COLUMN embedding TYPE vector(768);
# Then rebuild index
```

## Environment Variables

Key variables in `.env`:

```bash
# Database (PostgreSQL + pgvector)
DB_CONNECTION=pgsql
DB_HOST=db
DB_DATABASE=avinash_eye
DB_USERNAME=avinash
DB_PASSWORD=secret  # CHANGE IN PRODUCTION!

# AI Service
AI_API_URL=http://python-ai:8000
AI_DEFAULT_TIMEOUT=120
AI_CIRCUIT_BREAKER_THRESHOLD=10
AI_CIRCUIT_BREAKER_RECOVERY=30

# Node Image Processor
NODE_PROCESSOR_URL=http://node-processor:3000

# Ollama (Optional)
OLLAMA_URL=http://ollama:11434
OLLAMA_ENABLED=true
OLLAMA_MODEL=llava:13b-v1.6

# Queue
QUEUE_CONNECTION=database

# Elasticsearch
SCOUT_DRIVER=elasticsearch
ELASTICSEARCH_HOST=http://elasticsearch:9200

# Default Admin User
DEFAULT_USER_EMAIL=admin@avinash-eye.local
DEFAULT_USER_PASSWORD=Admin@123  # CHANGE IMMEDIATELY!
DEFAULT_USER_NAME=Administrator
```

## File Locations

### Laravel Application
- **Livewire Components**: `app/Livewire/` (11 components)
- **Services**: `app/Services/` (14+ services)
- **Models**: `app/Models/` (12 models, STI pattern)
- **Jobs**: `app/Jobs/` (background processing)
- **Commands**: `app/Console/Commands/` (9 Artisan commands)
- **Views**: `resources/views/livewire/` (Blade templates)
- **Routes**: `routes/web.php`, `routes/api.php`
- **Config**: `config/ai.php` (AI service configuration)

### AI Services
- **Python FastAPI**: `python-ai/main.py` (main service)
- **Multimedia Analysis**: `python-ai/main_multimedia.py`
- **Comprehensive Analyzer**: `python-ai/comprehensive_analyzer.py`
- **Node.js Processor**: `node-image-processor/server.js`

### Infrastructure
- **Docker Compose**: `docker-compose.yml` (8 services)
- **Laravel Dockerfile**: `docker/laravel/Dockerfile`
- **Init Script**: `docker/laravel/init.sh` (auto-initialization)
- **Nginx Config**: `docker/nginx/default.conf`

### Documentation
- **Main README**: `README.md` (comprehensive user guide)
- **Docs Folder**: `docs/` (47 documentation files)

## Key Dependencies

### PHP/Laravel
- **Laravel**: 12.x (latest)
- **Livewire**: 3.x (reactive components)
- **PHP**: 8.4 (with JIT compiler)
- **pgvector**: ^0.2.2 (vector operations)
- **Laravel Scout**: ^10.23 (Elasticsearch integration)
- **Laravel Sanctum**: ^4.0 (API authentication)
- **Laravel Telescope**: ^5.15 (debugging, dev only)

### Python AI
- **FastAPI**: Latest (high-performance API)
- **Transformers**: HuggingFace (Florence-2, BLIP, CLIP, SigLIP, AIMv2)
- **Whisper**: OpenAI (audio transcription)
- **face_recognition**: dlib-based (face detection)
- **Tesseract + PaddleOCR**: OCR engines
- **Ollama**: Optional LLM integration

### Frontend
- **Vite**: ^6.0.11 (build tool)
- **Tailwind CSS**: ^3.4.13 (styling)
- **Axios**: ^1.7.4 (HTTP client)
- **Alpine.js**: Included with Livewire (reactive behavior)

## Production Deployment Notes

- All services have health checks with auto-restart
- Resource limits defined in docker-compose.yml
- Log rotation: 10MB x 3 files per service
- Queue worker runs 24/7 with heartbeat monitoring
- Scheduler container for automated tasks (training, reanalysis, monitoring)
- Circuit breaker prevents cascading failures
- Retry mechanisms with exponential backoff
- Auto-initialization on first run (migrations, seeders, storage links, user creation)
- Background model downloads (non-blocking startup)

Initial deployment: `./start-production.sh` (one command, fully automated)
