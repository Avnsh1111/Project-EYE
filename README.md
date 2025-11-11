# 🔍 Avinash-EYE: AI-Powered Image Management System

[![Laravel](https://img.shields.io/badge/Laravel-11-red)](https://laravel.com) [![Livewire](https://img.shields.io/badge/Livewire-3-purple)](https://livewire.laravel.com) [![FastAPI](https://img.shields.io/badge/FastAPI-Latest-green)](https://fastapi.tiangolo.com) [![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docker.com) [![Python](https://img.shields.io/badge/Python-3.11-blue)](https://python.org)

> A complete, self-hosted, privacy-focused image management and AI-powered search system built with Laravel 11, Livewire 3, Python FastAPI, and Docker. Works 100% offline with local open-source AI models. No API keys, no cloud services, no tracking.

---

## 🌟 Key Features

### 📤 **Image Management**
- **Instant Upload**: Drag-and-drop multiple images with real-time progress
- **Smart Gallery**: Beautiful masonry grid with date grouping
- **Favorites System**: Star important photos for quick access
- **Trash & Recovery**: Soft delete with restore capability
- **Bulk Operations**: Select, download, delete, or favorite multiple images at once
- **Metadata Extraction**: Comprehensive EXIF data (camera, GPS, exposure settings)
- **View Counter**: Track how many times each photo has been viewed

### 🤖 **AI-Powered Analysis**
- **Automatic Descriptions**: AI-generated detailed captions for every image
- **Semantic Search**: Search images using natural language ("sunset over mountains", "person wearing glasses")
- **Face Detection**: Automatic face detection with clustering
- **People & Pets Recognition**: Group and name faces automatically
- **Smart Tagging**: AI-generated meta tags for categorization
- **Learning System**: AI trains itself on your images for better results over time

### 🔍 **Advanced Search**
- **Vector Similarity Search**: Find images by semantic meaning, not just keywords
- **Tag Filtering**: Filter by AI-generated categories
- **Favorite Filtering**: Quickly access your starred photos
- **Trash View**: Manage deleted photos separately
- **Real-time Results**: Fast search with PostgreSQL + pgvector

### 🎨 **Beautiful User Experience**
- **Modern Material Design**: Clean, intuitive interface
- **Responsive Layout**: Works perfectly on desktop, tablet, and mobile
- **Keyboard Shortcuts**: Navigate efficiently with keyboard
- **Loading States**: Clear feedback during operations
- **Empty States**: Helpful prompts when no content exists
- **Dark-mode Ready**: Elegant design that works in any lighting

### 🔒 **Privacy & Control**
- **100% Local Processing**: All AI runs on your hardware
- **No External APIs**: Zero internet calls after setup
- **Self-Hosted**: Complete control over your data
- **Open Source**: Transparent, auditable code
- **No Tracking**: Zero telemetry or analytics
- **Unlimited Storage**: Only limited by your disk space

---

## 📋 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT (Browser)                          │
│         Modern UI with Livewire 3 Reactive Components       │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                 NGINX Web Server (Port 8080)                │
│              Serves static files & proxies requests          │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│            Laravel 11 + Livewire 3 (PHP 8.3)                │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Livewire Components:                                │   │
│  │  • ImageGallery    • ImageUploader                   │   │
│  │  • ImageSearch     • PeopleAndPets                   │   │
│  │  • Settings        • Collections                     │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Services Layer:                                     │   │
│  │  • AiService       • FaceClusteringService           │   │
│  │  • FileService     • MetadataService                 │   │
│  │  • SearchService   • SystemMonitorService            │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Queue Jobs:                                         │   │
│  │  • ProcessImageAnalysis (Background AI processing)   │   │
│  └──────────────────────────────────────────────────────┘   │
└────┬──────────────────────────────────┬───────────────┬─────┘
     │                                  │               │
     │                                  │               │
┌────▼──────────────┐   ┌──────────────▼────┐   ┌─────▼──────┐
│  PostgreSQL 16    │   │  Python AI Service │   │  Ollama    │
│  + pgvector       │   │  FastAPI (Port 8000)│   │ (Optional) │
│                   │   │                    │   │  Port 11434│
│  • image_files    │   │  AI Models:        │   │            │
│  • face_clusters  │   │  • BLIP-2 Caption  │   │  • LLaVA   │
│  • detected_faces │   │  • CLIP Embeddings │   │  • Llama2  │
│  • settings       │   │  • Face Recognition│   │            │
│  • jobs/cache     │   │                    │   │            │
└───────────────────┘   └────────────────────┘   └────────────┘
         │                       │                      │
         └───────────────────────┴──────────────────────┘
                                 │
                        ┌────────▼────────┐
                        │  Docker Volumes │
                        │  • images       │
                        │  • models       │
                        │  • database     │
                        └─────────────────┘
```

---

## 🚀 Quick Start

### Prerequisites

- **Docker Desktop** (or Docker + Docker Compose)
- **8GB RAM minimum** (16GB recommended for optimal performance)
- **10GB free disk space** (for AI models and images)
- **Multi-core CPU** recommended (AI processing is CPU-intensive)

### 🎯 Production Deployment (Recommended)

**One-command deployment with automatic initialization:**

```bash
# 1. Navigate to project
cd /path/to/Avinash-EYE

# 2. Run production startup script
./start-production.sh
```

**That's it!** The script automatically:
- ✅ Checks system prerequisites
- ✅ Creates `.env` from template if needed
- ✅ Builds all Docker containers
- ✅ Starts all services in correct order
- ✅ Runs database migrations
- ✅ Generates APP_KEY automatically
- ✅ Pulls AI models in background (non-blocking)
- ✅ Starts dedicated queue worker
- ✅ Shows status and follows logs

**Access your application**: `http://localhost:8080`

> **⏱️ Timing**: System usable in 3-5 minutes. Full model download takes 10-15 minutes (background, non-blocking). Subsequent starts: 1-2 minutes.

---

### 📋 Manual Installation (Advanced)

If you prefer manual control:

1. **Clone or navigate to the project**:
   ```bash
   cd /path/to/Avinash-EYE
   ```

2. **Copy environment configuration**:
   ```bash
   cp .env.production .env
   # Edit .env and update DB_PASSWORD
   ```

3. **Start all services**:
   ```bash
   docker compose up -d --build
   ```
   
   > **✨ Auto-initialization**: Database migrations, settings seeding, storage links, and optimization happen automatically on startup!

4. **Access the application**:
   ```
   http://localhost:8080
   ```

> **💡 Note**: The queue worker service starts automatically as a dedicated container. No manual queue:work command needed!

**🎉 Your AI-powered image system is ready!**

---

## 📖 Complete Feature Guide

### 🖼️ Image Upload & Processing

#### Instant Upload
- Navigate to **Upload Images** page
- Drag & drop or click to select multiple images
- Supports: JPEG, PNG, GIF, WEBP, BMP
- Max size: 10MB per image
- Real-time progress tracking
- Images appear immediately in gallery

#### Background AI Processing
Every uploaded image is automatically:
1. ✅ Stored securely with original filename preserved
2. ✅ EXIF metadata extracted (camera, GPS, date, exposure)
3. ✅ AI caption generated (BLIP-2 model)
4. ✅ Detailed description created (Ollama LLaVA - optional)
5. ✅ Vector embedding generated (CLIP model for search)
6. ✅ Faces detected and clustered (face_recognition)
7. ✅ Meta tags extracted for categorization

#### Queue System
- **Dedicated Queue Worker**: Runs as separate Docker container
- **Auto-Start**: Queue worker starts automatically, no manual intervention
- **Background Processing**: All AI analysis happens in background
- **Non-blocking**: Upload and use images immediately
- **Status Tracking**: See "Processing...", "Completed", or "Failed"
- **Retry Mechanism**: Failed jobs retry automatically (3 attempts)
- **Resource Management**: Max 100 jobs per worker lifecycle
- **Auto-Restart**: Worker restarts automatically on failure
- **Monitor**: `docker compose logs -f queue-worker`

---

### 🔍 Semantic Search

#### How It Works
1. Enter natural language query: "sunset over mountains"
2. System converts query to 512-dimensional vector embedding
3. Compares with all image embeddings using cosine similarity
4. Returns most similar images ranked by relevance
5. Results in milliseconds using pgvector indexing

#### Search Examples
```
"person wearing glasses"        → Finds all photos with eyeglasses
"dog playing in snow"           → Finds winter dog photos
"sunset on beach"               → Finds beach sunset scenes
"red car"                       → Finds red vehicles
"family gathering"              → Finds group photos
"food on plate"                 → Finds meal photos
"mountain landscape"            → Finds scenic mountain views
```

#### Search Features
- **Vector Similarity**: Finds semantically similar images, not just keyword matches
- **Tag Filtering**: Filter by AI-generated categories
- **Result Limits**: Adjust number of results (default: 20)
- **Similarity Scores**: Toggle to see relevance percentages
- **Fast Results**: Sub-second search on thousands of images

---

### 🎨 Gallery Management

#### View Modes
- **All Photos**: Complete image library
- **Favorites**: Only starred photos
- **Trash**: Deleted photos (recoverable)

#### Bulk Operations
1. Click **"Select"** button to enter selection mode
2. Click images to select (blue outline indicates selection)
3. Use bulk actions:
   - **Select All**: Select every visible image
   - **Deselect All**: Clear selection
   - **Favorite**: Star selected images
   - **Download**: Download selected images
   - **Delete**: Move selected to trash

#### Individual Actions
- **Star/Unstar**: Mark as favorite (toggle ★ icon)
- **Download**: Save image to your computer
- **Delete**: Move to trash (soft delete, recoverable)
- **View Details**: See full metadata and AI analysis

#### Sorting & Filtering
- **Date Grouping**: Automatic date separators
- **Favorites Filter**: Show only starred photos
- **Trash View**: Manage deleted photos

---

### 👥 People & Pets (Face Recognition)

#### Automatic Face Clustering
- System detects faces in every uploaded image
- Uses face_recognition library (dlib-based, 99.38% accuracy)
- Automatically groups similar faces using cosine similarity
- Creates clusters for each unique person/pet
- Threshold: 0.6 (adjustable for stricter/looser matching)

#### Naming & Organization
1. Navigate to **People & Pets** page
2. See all detected face clusters
3. Click cluster name to rename:
   - "Mom", "Dad", "Sister"
   - "Max" (dog), "Luna" (cat)
   - Any custom name
4. Click cluster to view all photos of that person/pet

#### Features
- **Photo Count**: See how many photos each person appears in
- **Representative Thumbnail**: Best face sample shown
- **Type Badges**: Person/Pet/Unknown labels
- **Merge Clusters**: Combine duplicates if needed
- **Re-clustering**: Reprocess all faces with updated thresholds

---

### 🤖 AI Learning System

#### Automatic Training
The system trains itself on your image collection:

1. **Export Training Data**:
   ```bash
   php artisan export:training-data
   ```

2. **Automatic Training on Startup**:
   ```bash
   docker compose restart python-ai
   # Training starts automatically in background
   ```

3. **Watch Training Progress**:
   ```bash
   docker compose logs -f python-ai
   ```

#### What Gets Learned
- **Category Patterns**: Which tags appear together
- **Description Styles**: Common phrases for each category
- **Face Patterns**: Improved face similarity matching
- **Search Synonyms**: Related terms for better search

#### Benefits
- 📈 **Improved Descriptions**: More contextually relevant captions
- 🎯 **Better Categories**: More accurate tag assignments
- 🔍 **Smarter Search**: Better understanding of query intent
- 👤 **Enhanced Face Recognition**: Improved clustering accuracy

---

### ⚙️ Settings & Configuration

#### AI Model Configuration
- **Captioning Model**: Choose from BLIP, BLIP-2, ViT-GPT2
- **Embedding Model**: CLIP variants for search
- **Face Detection**: Enable/disable face recognition
- **Ollama Integration**: Optional LLM for detailed descriptions

#### Ollama Setup (Optional, for Better Descriptions)
```bash
# Pull the LLaVA vision model (recommended)
docker compose exec ollama ollama pull llava

# Or other models:
docker compose exec ollama ollama pull llama2
docker compose exec ollama ollama pull mistral
```

Then enable in Settings → Ollama section → Select model → Save

#### System Settings
- **Storage Path**: Configure image storage location
- **Queue Configuration**: Adjust worker settings
- **Cache Settings**: Control cache behavior
- **Backup Settings**: Configure automatic backups

---

## 🛠️ Technical Stack

### Backend
| Component | Technology | Purpose |
|-----------|-----------|---------|
| Framework | Laravel 11 | PHP framework with modern features |
| Frontend | Livewire 3 | Reactive components without JavaScript frameworks |
| Web Server | Nginx (Alpine) | High-performance reverse proxy |
| PHP | 8.3-FPM | Latest PHP with performance optimizations |
| Database | PostgreSQL 16 | Robust relational database |
| Vector Search | pgvector | High-performance vector similarity search |
| Queue | Laravel Queues | Background job processing |

### AI & Machine Learning
| Component | Technology | Purpose |
|-----------|-----------|---------|
| AI Framework | FastAPI | High-performance Python API framework |
| Python | 3.11 | Latest stable Python |
| Captioning | BLIP-2 (Salesforce) | Image-to-text generation |
| Embeddings | CLIP (OpenAI) | Image/text vector embeddings |
| Face Detection | face_recognition (dlib) | Facial recognition and clustering |
| LLM (Optional) | Ollama (LLaVA/Llama2) | Enhanced descriptions |

### Infrastructure
| Component | Technology | Purpose |
|-----------|-----------|---------|
| Containerization | Docker Compose | Multi-container orchestration |
| Reverse Proxy | Nginx | Request routing and static files |
| Volumes | Docker Volumes | Persistent data storage |
| Networks | Docker Networks | Service isolation and communication |

---

## 📂 Project Structure

```
Avinash-EYE/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   │   ├── ExportTrainingData.php
│   │   ├── ReprocessImages.php
│   │   └── SystemStatus.php
│   ├── Events/                  # Laravel events
│   │   └── ImageProcessed.php
│   ├── Jobs/                    # Queue jobs
│   │   └── ProcessImageAnalysis.php
│   ├── Livewire/                # Livewire components
│   │   ├── Collections.php
│   │   ├── ImageGallery.php
│   │   ├── ImageSearch.php
│   │   ├── ImageUploader.php
│   │   ├── InstantImageUploader.php
│   │   ├── PeopleAndPets.php
│   │   ├── Settings.php
│   │   └── SystemMonitor.php
│   ├── Models/                  # Eloquent models
│   │   ├── Collection.php
│   │   ├── DetectedFace.php
│   │   ├── FaceCluster.php
│   │   ├── ImageFile.php
│   │   └── Setting.php
│   ├── Repositories/            # Repository pattern
│   │   └── ImageRepository.php
│   └── Services/                # Business logic
│       ├── AiService.php
│       ├── FaceClusteringService.php
│       ├── FileService.php
│       ├── MetadataService.php
│       ├── SearchService.php
│       └── SystemMonitorService.php
├── database/
│   ├── migrations/              # Database schema
│   │   ├── *_enable_pgvector_extension.php
│   │   ├── *_create_image_files_table.php
│   │   ├── *_create_face_clusters_table.php
│   │   ├── *_create_detected_faces_table.php
│   │   ├── *_create_collections_table.php
│   │   └── *_create_settings_table.php
│   └── seeders/                 # Database seeders
│       └── SettingsSeeder.php
├── python-ai/
│   ├── main.py                  # FastAPI application
│   ├── ai_learning.py           # ML training module
│   ├── enhanced_analysis.py     # Learned pattern application
│   ├── requirements.txt         # Python dependencies
│   ├── Dockerfile               # Python container config
│   └── startup.sh               # Auto-training script
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php    # Main layout template
│       ├── livewire/            # Component views
│       │   ├── image-gallery.blade.php
│       │   ├── image-search.blade.php
│       │   ├── image-uploader.blade.php
│       │   ├── people-and-pets.blade.php
│       │   └── settings.blade.php
│       └── welcome.blade.php    # Home page
├── docker/
│   ├── laravel/
│   │   ├── Dockerfile           # Laravel container
│   │   └── init.sh              # 🏭 Auto-initialization script
│   ├── nginx/default.conf       # Nginx configuration
│   └── ollama/
│       └── init-models.sh       # 🤖 Auto-pull Ollama models
├── storage/
│   └── app/
│       ├── public/images/       # Uploaded images
│       └── training/            # AI training data
├── docs/                        # 📚 Documentation
│   ├── AI_LEARNING_COMPLETE.md
│   ├── DOCKER_OLLAMA_SETUP.md
│   ├── FACE_RECOGNITION_STATUS.md
│   ├── FEATURES_COMPARISON.md
│   ├── INSTANT_UPLOAD_GUIDE.md
│   ├── MODEL_SELECTION_GUIDE.md
│   ├── OLLAMA_SETUP.md
│   ├── PROJECT_SUMMARY.md
│   ├── QUICK_REFERENCE.md
│   └── ... (47 total documentation files)
├── docker-compose.yml           # 🏭 Production-ready Docker orchestration
├── .env.example                 # Environment template
├── .env.production              # 🏭 Production environment template
├── start-production.sh          # 🚀 One-command production deployment
├── setup-ollama.sh              # Ollama setup script
├── PRODUCTION_READY.md          # 📖 Production features guide
└── README.md                    # This file
```

---

## 🔧 Configuration Guide

### Environment Variables

Key configurations in `.env`:

```env
# Application
APP_NAME="Avinash-EYE"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database (PostgreSQL + pgvector)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=avinash_eye
DB_USERNAME=avinash
DB_PASSWORD=secret

# AI Service
AI_API_URL=http://python-ai:8000
AI_TIMEOUT=300

# Queue
QUEUE_CONNECTION=database

# Ollama (Optional)
OLLAMA_URL=http://ollama:11434
OLLAMA_ENABLED=true
OLLAMA_MODEL=llava
```

### Docker Services Configuration

Services defined in `docker-compose.yml`:

| Service | Port | Purpose | Memory | Features |
|---------|------|---------|--------|----------|
| **nginx** | 8080 | Web server (public access) | 256MB | Auto-restart, health checks |
| **laravel-app** | 9000 | PHP-FPM (internal) | 2GB | Auto-migration, auto-optimization |
| **queue-worker** | - | Background job processor | 1GB | Dedicated container, auto-restart |
| **python-ai** | 8000 | AI service (internal) | 8GB | Auto-download models, health checks |
| **db** | 5432 | PostgreSQL + pgvector | 1GB | Auto-backup ready, health checks |
| **ollama** | 11434 | LLM service (optional) | 8GB | Auto-pull LLaVA model |

**Production Features:**
- ✅ All services have health checks
- ✅ Automatic restart on failure
- ✅ Resource limits configured
- ✅ Log rotation enabled (10MB x 3 files)
- ✅ Background model downloads (non-blocking)
- ✅ Dedicated queue worker service

### AI Model Selection

Configure in Settings UI or directly in database:

```sql
-- Captioning Models
UPDATE settings SET value = 'Salesforce/blip2-opt-2.7b' 
WHERE key = 'captioning_model';

-- Embedding Models  
UPDATE settings SET value = 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K'
WHERE key = 'embedding_model';

-- Face Detection
UPDATE settings SET value = 'true' WHERE key = 'face_detection_enabled';

-- Ollama
UPDATE settings SET value = 'llava' WHERE key = 'ollama_model';
```

---

## 🎮 Usage Guide

### Command Line Operations

#### Image Management
```bash
# Reprocess images (update AI analysis)
php artisan images:reprocess --batch=50

# Reprocess only images missing certain features
php artisan images:reprocess --only-missing

# Force reprocess all images
php artisan images:reprocess --force

# Export training data for AI learning
php artisan export:training-data --limit=1000
```

#### System Monitoring
```bash
# Check system status
php artisan system:status

# Monitor queue
php artisan queue:monitor database:image-processing

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### Database Operations
```bash
# Run migrations
php artisan migrate

# Seed default settings
php artisan db:seed

# Fresh install (⚠️ deletes all data)
php artisan migrate:fresh --seed
```

#### Docker Operations
```bash
# Production Deployment
./start-production.sh              # One-command production start

# View logs
docker compose logs -f             # All services
docker compose logs -f python-ai   # Python AI service
docker compose logs -f queue-worker # Queue worker
docker compose logs -f laravel-app  # Laravel application

# Restart services
docker compose restart python-ai    # Restart Python AI
docker compose restart queue-worker # Restart queue worker
docker compose restart              # Restart all services

# Stop all services
docker compose down

# Start services (production mode)
docker compose up -d

# Rebuild containers
docker compose up -d --build

# Check service health
docker compose ps                   # Service status
docker stats                        # Resource usage
```

#### Production Management
```bash
# Check Ollama models (auto-pulled)
docker compose exec ollama ollama list

# Monitor queue worker
docker compose logs -f queue-worker

# Check AI service health
curl http://localhost:8000/health

# Manual queue commands (if needed)
docker compose exec laravel-app php artisan queue:monitor
docker compose exec laravel-app php artisan queue:failed
docker compose exec laravel-app php artisan queue:retry all
```

---

## 📊 Database Schema

### Core Tables

#### `image_files`
Primary table for all images:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| file_path | string | Path to stored image |
| original_filename | string | Original upload name |
| description | text | AI-generated caption |
| detailed_description | text | Extended description (Ollama) |
| meta_tags | jsonb | AI-generated categories |
| embedding | vector(512) | CLIP embedding for search |
| face_count | integer | Number of detected faces |
| is_favorite | boolean | Favorite status |
| view_count | integer | Number of views |
| deleted_at | timestamp | Soft delete timestamp |
| processing_status | string | pending/processing/completed/failed |
| **EXIF Data** | | |
| camera_make | string | Camera manufacturer |
| camera_model | string | Camera model |
| lens_model | string | Lens information |
| focal_length | string | Lens focal length |
| aperture | string | F-stop value |
| shutter_speed | string | Exposure time |
| iso | integer | ISO sensitivity |
| taken_at | timestamp | Photo capture date |
| gps_latitude | decimal | GPS latitude |
| gps_longitude | decimal | GPS longitude |
| file_size | bigint | File size in bytes |
| mime_type | string | Image MIME type |
| width | integer | Image width in pixels |
| height | integer | Image height in pixels |
| created_at | timestamp | Upload timestamp |
| updated_at | timestamp | Last modified |

**Indexes:**
- Primary key on `id`
- IVFFlat vector index on `embedding` for fast similarity search
- Index on `is_favorite` for favorite filtering
- Index on `deleted_at` for trash filtering
- Index on `created_at` for date sorting

#### `face_clusters`
Groups of similar faces (people/pets):

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Person/pet name (nullable) |
| representative_encoding | jsonb | Average face encoding |
| representative_image_id | bigint | Best example image |
| photo_count | integer | Number of photos |
| type | string | person/pet/unknown |
| created_at | timestamp | Cluster creation |
| updated_at | timestamp | Last update |

#### `detected_faces`
Individual face detections:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| image_file_id | bigint | Source image |
| face_cluster_id | bigint | Assigned cluster |
| encoding | jsonb | 128-dimensional face encoding |
| location | jsonb | Bounding box coordinates |
| confidence | float | Detection confidence |
| created_at | timestamp | Detection timestamp |

#### `collections`
User-created photo albums:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Collection name |
| description | text | Collection description |
| image_count | integer | Number of images |
| thumbnail_path | string | Cover image |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last modified |

#### `settings`
System configuration:

| Column | Type | Description |
|--------|------|-------------|
| key | string | Setting key (unique) |
| value | text | Setting value |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last modified |

---

## 🧪 Testing & Troubleshooting

### Health Checks

```bash
# Check all services
curl http://localhost:8080             # Laravel app
curl http://localhost:8000/health      # Python AI service
curl http://localhost:11434/api/tags   # Ollama (if enabled)

# Check database
docker compose exec db pg_isready -U avinash

# Check queue
php artisan queue:monitor
```

### Common Issues & Solutions

#### 1. **AI Service Not Loading Models**

**Symptoms**: Python service takes forever to start or crashes

**Solutions**:
```bash
# Check Python service logs
docker compose logs python-ai

# Increase Docker memory to 8GB minimum
# Docker Desktop → Settings → Resources → Memory → 8GB

# Clear model cache and restart
docker volume rm avinash-eye_model-cache
docker compose up -d --build python-ai
```

#### 2. **Queue Jobs Timing Out**

**Symptoms**: Jobs fail after 2 minutes with timeout errors

**Solutions**:
```bash
# Check if Ollama model matches configuration
curl http://localhost:11434/api/tags

# If wrong model, update settings
docker exec avinash-eye-db psql -U avinash -d avinash_eye -c "UPDATE settings SET value = 'llava' WHERE key = 'ollama_model';"

# Restart Python AI service
docker compose restart python-ai

# Restart queue worker
php artisan queue:restart
```

#### 3. **Permission Errors**

**Symptoms**: Laravel can't write to storage directories

**Solutions**:
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Inside Docker
docker compose exec laravel chown -R www-data:www-data storage bootstrap/cache
```

#### 4. **Database Connection Issues**

**Symptoms**: SQLSTATE errors or connection refused

**Solutions**:
```bash
# Check database status
docker compose ps db

# Restart database
docker compose restart db

# Check environment variables
cat .env | grep DB_

# Test connection
docker compose exec laravel php artisan tinker
>>> DB::connection()->getPdo();
```

#### 5. **Images Not Displaying**

**Symptoms**: Broken image thumbnails in gallery

**Solutions**:
```bash
# Recreate storage symlink
docker compose exec laravel php artisan storage:link

# Check file permissions
ls -la storage/app/public/images/

# Verify nginx configuration
docker compose exec nginx cat /etc/nginx/conf.d/default.conf
```

#### 6. **Search Returns No Results**

**Symptoms**: Semantic search always empty

**Solutions**:
```bash
# Check if images have embeddings
docker exec avinash-eye-db psql -U avinash -d avinash_eye -c "SELECT COUNT(*) FROM image_files WHERE embedding IS NOT NULL;"

# Reprocess images to generate embeddings
php artisan images:reprocess --only-missing

# Verify Python AI is responding
curl -X POST http://localhost:8000/embed-text \
  -H "Content-Type: application/json" \
  -d '{"query":"test"}'
```

#### 7. **Out of Memory**

**Symptoms**: Services crash, Docker becomes unresponsive

**Solutions**:
- Increase Docker Desktop memory allocation (Settings → Resources)
- Disable Ollama if not needed (frees ~4GB)
- Process fewer images at once
- Use smaller AI models in settings

---

## 📚 Documentation

Comprehensive documentation available in the `/docs` folder:

### Getting Started
- 📖 **[QUICK_REFERENCE.md](docs/QUICK_REFERENCE.md)** - Quick feature reference
- 🚀 **[QUICKSTART.md](docs/QUICKSTART.md)** - Rapid deployment guide
- 🏭 **[PRODUCTION_DEPLOYMENT.md](docs/PRODUCTION_DEPLOYMENT.md)** - Production deployment guide
- 📊 **[PROJECT_SUMMARY.md](docs/PROJECT_SUMMARY.md)** - Complete project overview
- 🎯 **[PRODUCTION_READY.md](PRODUCTION_READY.md)** - Production features overview

### Features
- 🎨 **[GALLERY_FEATURES.md](docs/GALLERY_FEATURES.md)** - Gallery capabilities
- 🔍 **[SEARCH_IMPROVEMENTS.md](docs/SEARCH_IMPROVEMENTS.md)** - Search functionality
- 📤 **[INSTANT_UPLOAD_GUIDE.md](docs/INSTANT_UPLOAD_GUIDE.md)** - Upload system
- 🎭 **[FACE_RECOGNITION_STATUS.md](docs/FACE_RECOGNITION_STATUS.md)** - Face detection
- 🏷️ **[FEATURES_COMPARISON.md](docs/FEATURES_COMPARISON.md)** - vs Google Photos

### AI & Models
- 🤖 **[AI_LEARNING_COMPLETE.md](docs/AI_LEARNING_COMPLETE.md)** - Learning system
- 🧠 **[MODEL_SELECTION_GUIDE.md](docs/MODEL_SELECTION_GUIDE.md)** - Choose AI models
- 🦙 **[OLLAMA_SETUP.md](docs/OLLAMA_SETUP.md)** - LLM integration
- 🐳 **[DOCKER_OLLAMA_SETUP.md](docs/DOCKER_OLLAMA_SETUP.md)** - Ollama in Docker

### Technical
- 🏗️ **[MASTER_DESIGN_REFERENCE.md](docs/MASTER_DESIGN_REFERENCE.md)** - Architecture
- 🔧 **[REFACTORING_COMPLETE.md](docs/REFACTORING_COMPLETE.md)** - Code structure
- 📝 **[CHANGELOG.md](docs/CHANGELOG.md)** - Version history
- 🧪 **[TESTING.md](docs/TESTING.md)** - Test suite

---

## 🚀 Performance Expectations

| Metric | Expected Value | Notes |
|--------|----------------|-------|
| **Initial Setup** | 10-15 minutes | One-time model downloads (~5GB) |
| **Subsequent Starts** | 1-2 minutes | Models cached in Docker volume |
| **Image Upload** | < 1 second | Instant UI feedback |
| **AI Analysis** | 5-15 seconds | Background processing per image |
| **Semantic Search** | < 500ms | With thousands of images |
| **Face Detection** | 2-5 seconds | Per image, background |
| **Gallery Load** | < 2 seconds | With lazy loading |
| **Max Upload Size** | 10MB | Configurable in Laravel |
| **Embedding Dimension** | 512 | CLIP ViT-B/32 standard |
| **Face Encoding** | 128 | dlib face_recognition |

### Scaling Considerations

| Collection Size | RAM Required | Storage | Performance | Notes |
|----------------|--------------|---------|-------------|-------|
| < 1,000 images | 8GB | ~5GB | Excellent | Default config |
| 1,000-10,000 | 16GB | ~10-50GB | Good | Recommended config |
| 10,000-50,000 | 32GB | ~50-250GB | Fair | Increase resources |
| > 50,000 | 64GB+ | 250GB+ | Requires optimization | Multiple workers |

**Resource Allocation (Configured in docker-compose.yml)**:
- **Database**: 1GB (can increase to 2GB for large collections)
- **Python AI**: 8GB (required for model loading)
- **Ollama**: 8GB (optional, can disable to save memory)
- **Laravel**: 2GB (sufficient for most workloads)
- **Queue Worker**: 1GB per worker (can scale horizontally)
- **Nginx**: 256MB (lightweight proxy)

**Optimization Tips for Large Collections**:
- Scale queue workers: Add more queue-worker containers
- Enable Redis for caching and queue management
- Increase pgvector index lists in database
- Use batch processing for bulk uploads
- Adjust resource limits in `docker-compose.yml`
- Implement lazy loading everywhere

---

## 🔒 Security & Privacy

### Privacy Features
- ✅ **100% Local Processing**: All AI computation on your hardware
- ✅ **No External API Calls**: Zero internet requests after setup
- ✅ **No Telemetry**: No tracking, analytics, or data collection
- ✅ **Open Source**: Full transparency, auditable code
- ✅ **Self-Hosted**: Complete data sovereignty

### Security Best Practices
- 🔐 **Change DB_PASSWORD**: Update from 'secret' in `.env` (critical!)
- 🔐 **APP_KEY**: Auto-generated on first run, or run `php artisan key:generate`
- 🔐 **HTTPS/SSL**: Use reverse proxy (nginx/Caddy) with Let's Encrypt
- 🔐 **Authentication**: Implement Laravel auth for multi-user setups
- 🔐 **Firewall Rules**: Restrict ports, only expose 8080 (or 443 for HTTPS)
- 🔐 **Regular Backups**: Automated backup script included in docs
- 🔐 **Docker Updates**: Run `docker compose pull` regularly
- 🔐 **Environment Variables**: Never commit `.env` to version control
- 🔐 **Production Mode**: Ensure `APP_DEBUG=false` in production
- 🔐 **Resource Limits**: Configured to prevent DoS attacks

**Production Security Checklist**:
See `docs/PRODUCTION_DEPLOYMENT.md` for complete security hardening guide.

### Backup Strategy
```bash
# Backup database
docker exec avinash-eye-db pg_dump -U avinash avinash_eye > backup.sql

# Backup images
tar -czf images_backup.tar.gz storage/app/public/images/

# Restore database
docker exec -i avinash-eye-db psql -U avinash avinash_eye < backup.sql

# Restore images
tar -xzf images_backup.tar.gz -C storage/app/public/
```

---

## 🎯 Roadmap & Future Features

### Planned Features
- [ ] **Mobile Apps**: iOS and Android native apps
- [ ] **Albums**: Custom collections and organization
- [ ] **Sharing**: Generate secure share links
- [ ] **Duplicate Detection**: Find and merge similar images
- [ ] **Batch Editing**: Bulk rename, tag, categorize
- [ ] **Timeline View**: Visual chronological browser
- [ ] **Map View**: GPS-based photo map
- [ ] **Slideshow**: Automatic photo presentations
- [ ] **Export**: ZIP archives of selections
- [ ] **RAW Support**: Professional photo formats
- [ ] **Video Support**: Video upload and analysis
- [ ] **Multi-User**: User accounts and permissions
- [ ] **API**: RESTful API for integrations
- [ ] **Plugins**: Extension system for custom features

### Contributing
Contributions are welcome! Please feel free to:
- 🐛 Report bugs via GitHub issues
- 💡 Suggest features
- 📝 Improve documentation
- 🔧 Submit pull requests
- ⭐ Star the repository if you find it useful

---

## 📄 License

MIT License - See LICENSE file for details

---

## 🙏 Acknowledgments

### AI Models
- **Salesforce** - BLIP & BLIP-2 image captioning models
- **OpenAI** - CLIP vision-language embeddings
- **Meta** - LLaVA multimodal model
- **dlib** - Face recognition library

### Frameworks & Tools
- **Laravel** - PHP framework excellence
- **Livewire** - Reactive PHP components
- **FastAPI** - Modern Python web framework
- **PostgreSQL** - Robust database system
- **pgvector** - Vector similarity search extension
- **Docker** - Containerization platform
- **HuggingFace** - AI model hosting

### Special Thanks
To the open-source community for making projects like this possible! 🎉

---

## 📞 Support & Community

### Getting Help
1. 📖 Check documentation in `/docs` folder
2. 🔍 Search existing GitHub issues
3. 💬 Open a new issue with details
4. 📧 Contact maintainers

### Reporting Bugs
Please include:
- System information (OS, Docker version)
- Steps to reproduce
- Expected vs actual behavior  
- Relevant logs from `docker compose logs`
- Screenshots if applicable

---

## 📈 Comparison with Other Solutions

### vs Google Photos
✅ **Better**: Privacy, cost, storage limits, customization, advanced AI search  
❌ **Missing**: Cloud backup, multi-device sync, easy sharing (coming soon)

### vs Apple Photos
✅ **Better**: Cross-platform, self-hosted, unlimited storage, open-source  
❌ **Missing**: Device integration, iCloud sync, native apps (coming soon)

### vs Adobe Lightroom
✅ **Better**: Free, AI search, automatic organization, no subscription  
❌ **Missing**: Advanced editing, professional tools (not the goal)

### vs Plex Photos
✅ **Better**: AI search, face recognition, better UI, faster  
❌ **Missing**: Video support (coming soon), mobile apps (coming soon)

---

<div align="center">

**Built with ❤️ using Laravel, Livewire, FastAPI, and Docker**

---

### ⭐ Star this repo if you found it helpful!

[📚 Documentation](docs/) • [🐛 Report Bug](https://github.com/yourusername/Avinash-EYE/issues) • [💡 Request Feature](https://github.com/yourusername/Avinash-EYE/issues)

---

**Made with privacy and control in mind. Your photos, your way.** 🔒

</div>
