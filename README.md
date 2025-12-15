# 🔍 Avinash-EYE: AI-Powered Multimedia Management System

[![Laravel](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com) [![Livewire](https://img.shields.io/badge/Livewire-3-purple)](https://livewire.laravel.com) [![FastAPI](https://img.shields.io/badge/FastAPI-Latest-green)](https://fastapi.tiangolo.com) [![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docker.com) [![Python](https://img.shields.io/badge/Python-3.12-blue)](https://python.org) [![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://php.net)

> A complete, production-ready, self-hosted multimedia management and AI-powered search system built with Laravel 12, Livewire 3, Python FastAPI, and Docker. Works 100% offline with local open-source AI models. No API keys, no cloud services, no tracking.

---

## 🌟 Key Features

### 📤 **Multimedia Management**
- **📷 Images**: JPEG, PNG, GIF, WEBP, BMP, HEIF/HEIC support
- **🎬 Videos**: MP4, AVI, MOV, MKV with frame extraction and analysis
- **🎵 Audio**: MP3, WAV, FLAC, OGG with transcription (Whisper AI)
- **📄 Documents**: PDF, DOCX, XLSX, PPTX, ODT, ODS with OCR (Tesseract/PaddleOCR)
- **📦 Archives**: ZIP, RAR, 7Z, TAR automatic extraction and content analysis
- **💾 Batch Upload**: Upload hundreds of files simultaneously with progress tracking
- **🚀 Instant Processing**: Drag-and-drop with real-time progress and immediate gallery display
- **📊 Comprehensive Metadata**: EXIF, GPS, camera settings, file properties extraction

### 🤖 **Advanced AI-Powered Analysis**
- **🖼️ Image Captioning**: Florence-2 & BLIP models for detailed descriptions
- **🎯 Semantic Search**: CLIP/SigLIP/AIMv2 embeddings (512/768/1024 dims)
- **👤 Face Recognition**: Automatic detection, clustering, and naming (99.38% accuracy)
- **🎬 Video Intelligence**: Scene detection, object tracking, activity recognition
- **📝 Document OCR**: Text extraction with multi-language support (PaddleOCR + Tesseract)
- **🗣️ Audio Transcription**: OpenAI Whisper for speech-to-text
- **🏷️ Smart Tagging**: AI-generated meta tags and categories
- **🦙 LLM Enhancement**: Optional Ollama integration (LLaVA, Llama2, Qwen) for rich descriptions

### 🔍 **Intelligent Search & Discovery**
- **🔎 Vector Similarity Search**: Find multimedia by semantic meaning, not just keywords
- **⚡ Sub-second Results**: pgvector indexing for instant search on thousands of files
- **🎯 Multi-modal Search**: Search across images, videos, documents, and audio
- **📊 Relevance Scoring**: See similarity percentages for each result
- **🏷️ Tag Filtering**: Filter by AI-generated categories
- **⭐ Smart Filters**: Favorites, file types, date ranges, processing status

### 🎨 **Beautiful Modern UI**
- **🎨 Material Design 3**: Clean, intuitive interface with smooth animations
- **📱 Fully Responsive**: Perfect on desktop, tablet, and mobile
- **⚡ Real-time Updates**: Livewire 3 reactive components without JavaScript frameworks
- **⌨️ Keyboard Shortcuts**: Navigate efficiently (coming soon)
- **🌙 Dark Mode Ready**: Elegant design for any lighting condition
- **♿ Accessible**: WCAG 2.1 compliant interface

### 🛡️ **Production-Grade Reliability**
- **🔄 Circuit Breaker**: Automatic failure detection and recovery
- **♻️ Retry Mechanism**: Exponential backoff with jitter for transient failures
- **💾 Smart Caching**: Redis-compatible caching for instant responses
- **📊 Health Monitoring**: Comprehensive health checks for all services
- **⚡ Queue Management**: Dedicated queue worker with auto-restart
- **🔧 Graceful Degradation**: System continues working even if AI service is down
- **📝 Comprehensive Logging**: Structured logs for easy debugging
- **🏭 Auto-initialization**: Zero-configuration startup with automatic setup

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
┌──────────────────────────────────────────────────────────────────┐
│                      CLIENT (Browser)                             │
│           Modern UI with Livewire 3 Reactive Components          │
└────────────────────┬─────────────────────────────────────────────┘
                     │
┌────────────────────▼─────────────────────────────────────────────┐
│                 NGINX Web Server (Port 8080)                     │
│      Serves static files & proxies requests (256MB RAM)          │
└────────────────────┬─────────────────────────────────────────────┘
                     │
┌────────────────────▼─────────────────────────────────────────────┐
│            Laravel 12 + Livewire 3 (PHP 8.4-FPM)                 │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │  Livewire Components:                                     │   │
│  │  • EnhancedImageGallery  • InstantImageUploader           │   │
│  │  • ImageSearch           • PeopleAndPets                  │   │
│  │  • Settings              • ProcessingStatus               │   │
│  │  • SystemMonitor         • Collections                    │   │
│  └───────────────────────────────────────────────────────────┘   │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │  Services Layer (17 Services):                            │   │
│  │  • AiService            • MediaProcessorService           │   │
│  │  • CircuitBreakerService • RetryService                   │   │
│  │  • CacheService         • FaceClusteringService           │   │
│  │  • FileService          • MediaFileService                │   │
│  │  • MetadataService      • SearchService                   │   │
│  │  • VideoProcessor       • DocumentProcessor               │   │
│  │  • AudioProcessor       • ArchiveProcessor                │   │
│  └───────────────────────────────────────────────────────────┘   │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │  Queue Jobs (Background Processing):                      │   │
│  │  • ProcessImageAnalysis  • ProcessBatchUpload             │   │
│  │  • ProcessBatchImages                                     │   │
│  └───────────────────────────────────────────────────────────┘   │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │  Models (STI Hierarchy):                                  │   │
│  │  • MediaFile (base)      • ImageFile                      │   │
│  │  • VideoFile             • AudioFile                      │   │
│  │  • DocumentFile          • ArchiveFile                    │   │
│  │  • BatchUpload           • FaceCluster                    │   │
│  └───────────────────────────────────────────────────────────┘   │
└──┬─────────────┬─────────────┬─────────────┬─────────────┬───────┘
   │             │             │             │             │
┌──▼────────┐ ┌─▼──────────┐ ┌▼───────────┐ ┌▼──────────┐ ┌▼──────────┐
│PostgreSQL │ │Python AI   │ │ Node.js    │ │ Ollama    │ │Queue      │
│16+pgvector│ │FastAPI     │ │Processor   │ │(Optional) │ │Worker     │
│(Port 5432)│ │(Port 8000) │ │(Port 3000) │ │Port 11434 │ │(Dedicated)│
│           │ │            │ │            │ │           │ │           │
│• media_   │ │AI Models:  │ │Sharp Image │ │LLM Models:│ │• 24/7     │
│  files    │ │• Florence-2│ │Processing  │ │• LLaVA    │ │  Running  │
│• face_    │ │• CLIP/     │ │Thumbnail   │ │  13B v1.6 │ │• Auto-    │
│  clusters │ │  SigLIP/   │ │Generation  │ │• Llama2   │ │  Restart  │
│• detected_│ │  AIMv2     │ │Format      │ │• Qwen 2.5 │ │• Max 100  │
│  faces    │ │• Face Rec  │ │Conversion  │ │• Mistral  │ │  jobs     │
│• batch_   │ │• Whisper   │ │            │ │           │ │• Health   │
│  uploads  │ │• Tesseract │ │            │ │           │ │  Checks   │
│• settings │ │• PaddleOCR │ │            │ │           │ │           │
│• jobs/    │ │            │ │            │ │           │ │           │
│  cache    │ │8GB RAM     │ │512MB RAM   │ │8GB RAM    │ │1GB RAM    │
└───────────┘ └────────────┘ └────────────┘ └───────────┘ └───────────┘
     │              │              │              │              │
     └──────────────┴──────────────┴──────────────┴──────────────┘
                                   │
                          ┌────────▼────────┐
                          │ Docker Volumes   │
                          │ • images         │
                          │ • models (~5GB)  │
                          │ • database       │
                          │ • ollama (~10GB) │
                          │ • node cache     │
                          └──────────────────┘
```

---

## 🚀 Quick Start

### Prerequisites

- **Docker Desktop** 4.20+ (or Docker 24+ + Docker Compose 2.20+)
- **16GB RAM minimum** (8GB might work but not recommended)
- **20GB free disk space** (for AI models and multimedia files)
- **Modern CPU** (multi-core recommended, AI processing is CPU-intensive)
- **GPU** (optional, for faster AI processing)

### 🎯 Production Deployment (Recommended)

**One-command deployment with automatic initialization:**

```bash
# 1. Clone and navigate to project
git clone https://github.com/yourusername/Avinash-EYE.git
cd Avinash-EYE

# 2. Run production startup script
chmod +x start-production.sh
./start-production.sh
```

**That's it!** The script automatically:
- ✅ Checks system prerequisites and Docker installation
- ✅ Creates `.env` from production template if needed
- ✅ Generates secure APP_KEY automatically
- ✅ Builds all Docker containers with optimizations
- ✅ Starts all services in correct dependency order
- ✅ Runs database migrations and seeds settings
- ✅ Creates default admin user with secure credentials
- ✅ Pulls AI models in background (non-blocking)
- ✅ Configures dedicated queue worker (24/7)
- ✅ Pulls Ollama LLaVA model automatically
- ✅ Performs health checks on all services
- ✅ Shows status dashboard and follows logs

**Access your application**: `http://localhost:8080`

**Default Credentials:**
- Email: `admin@avinash-eye.local`
- Password: `Admin@123`
- ⚠️ **Change password immediately after first login!**

> **⏱️ Timing**: System usable in 5-7 minutes. Full model downloads take 15-20 minutes (background, non-blocking). Subsequent starts: 30-60 seconds.

---

### 📋 Manual Installation (Advanced)

If you prefer manual control:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/Avinash-EYE.git
   cd Avinash-EYE
   ```

2. **Copy environment configuration**:
   ```bash
   cp .env.production .env
   # Edit .env and update sensitive values
   nano .env  # or use your preferred editor
   ```

3. **Start all services**:
   ```bash
   docker compose up -d --build
   ```
   
   > **✨ Auto-initialization**: Database migrations, settings seeding, storage links, optimization, and user creation happen automatically!

4. **Monitor startup**:
   ```bash
   docker compose logs -f
   # Press Ctrl+C when you see "Application is ready!"
   ```

5. **Access the application**:
   ```
   http://localhost:8080
   ```

**🎉 Your AI-powered multimedia system is ready!**

---

## 🔐 Authentication & Security

### Default Admin User

The system automatically creates a default admin user on first run with these credentials (configurable via `.env`):

- **Email**: `admin@avinash-eye.local`
- **Password**: `Admin@123`
- **Name**: `Administrator`

### Environment Configuration

Customize default user in `.env`:
```env
DEFAULT_USER_EMAIL=admin@yourdomain.com
DEFAULT_USER_PASSWORD=YourSecurePassword123!
DEFAULT_USER_NAME=Administrator
```

### Security Features

- ✅ **Bcrypt Password Hashing**: Industry-standard secure hashing
- ✅ **Rate Limiting**: 5 login attempts per email/IP
- ✅ **Account Locking**: Automatic lockout after failed attempts
- ✅ **Session Management**: Secure session handling
- ✅ **CSRF Protection**: Built-in Laravel CSRF tokens
- ✅ **Remember Me**: Secure persistent login
- ✅ **Password Reset**: Email-based password recovery
- ✅ **Circuit Breaker**: Prevents cascading failures
- ✅ **Sanctum API**: Secure API authentication

> **⚠️ Security Note**: Always change default credentials after first login and use a strong password!

---

## 📖 Complete Feature Guide

### 🖼️ Multimedia Upload & Processing

#### Instant Batch Upload
- Navigate to **Upload** page
- Drag & drop or click to select files
- **Supported Formats**:
  - Images: JPEG, PNG, GIF, WEBP, BMP, HEIF/HEIC
  - Videos: MP4, AVI, MOV, MKV, WEBM
  - Audio: MP3, WAV, FLAC, OGG, M4A
  - Documents: PDF, DOCX, XLSX, PPTX, ODT, ODS, ODP
  - Archives: ZIP, RAR, 7Z, TAR, GZ
- **Max size**: 100MB per file (configurable)
- Real-time progress tracking with speed indicators
- Files appear immediately in gallery

#### Intelligent Background Processing

Every uploaded file is automatically:

**Images:**
1. ✅ Stored securely with original filename preserved
2. ✅ EXIF metadata extracted (camera, GPS, date, settings)
3. ✅ AI caption generated (Florence-2/BLIP)
4. ✅ Detailed description created (Ollama LLaVA - optional)
5. ✅ Vector embedding generated (CLIP/SigLIP/AIMv2 for search)
6. ✅ Faces detected and clustered (face_recognition)
7. ✅ Thumbnails generated (multiple sizes)
8. ✅ Meta tags extracted for categorization

**Videos:**
1. ✅ Frame extraction at key moments
2. ✅ Scene detection and analysis
3. ✅ Object tracking and recognition
4. ✅ Activity classification
5. ✅ Thumbnail generation from representative frames
6. ✅ Duration, resolution, codec metadata extraction
7. ✅ Optional subtitle extraction

**Audio:**
1. ✅ Speech-to-text transcription (Whisper AI)
2. ✅ Speaker identification
3. ✅ Duration, bitrate, format metadata
4. ✅ Waveform generation
5. ✅ Audio fingerprinting

**Documents:**
1. ✅ Full text extraction (OCR for PDFs)
2. ✅ Multi-language support (PaddleOCR + Tesseract)
3. ✅ Page count and document structure
4. ✅ Thumbnail generation from first page
5. ✅ Table and form recognition
6. ✅ Summary generation (Ollama)

**Archives:**
1. ✅ Automatic extraction
2. ✅ Content inventory and analysis
3. ✅ Recursive processing of nested archives
4. ✅ File type distribution analysis

#### Production-Grade Queue System

- **Dedicated Queue Worker**: Runs as separate Docker container (24/7)
- **Auto-Start**: No manual intervention required
- **Circuit Breaker**: Automatic failure detection and recovery
- **Retry Mechanism**: Exponential backoff (3 attempts, 100ms-10s delays)
- **Smart Caching**: Results cached for instant retrieval
- **Status Tracking**: Real-time progress monitoring
- **Health Monitoring**: Continuous service health checks
- **Resource Management**: Memory limits and automatic cleanup
- **Graceful Degradation**: System continues working during AI service issues
- **Monitor**: `docker compose logs -f queue-worker`

---

### 🔍 Semantic Search

#### How It Works
1. Enter natural language query: "sunset over mountains"
2. System converts query to vector embedding (512/768/1024 dims)
3. Compares with all file embeddings using cosine similarity
4. pgvector performs fast approximate nearest neighbor search
5. Returns most similar files ranked by relevance
6. Results in milliseconds using IVFFlat indexing

#### Search Examples
```
"person wearing glasses"        → Finds all photos with eyeglasses
"dog playing in snow"           → Finds winter dog photos
"sunset on beach"               → Finds beach sunset scenes
"meeting presentation slides"   → Finds PowerPoint presentations
"acoustic guitar music"         → Finds audio recordings
"family vacation video"         → Finds video recordings
"invoice from 2024"             → Finds specific documents
"mountain landscape"            → Finds scenic photos/videos
```

#### Advanced Features
- **Multi-modal Search**: Search across all media types simultaneously
- **Vector Similarity**: Finds semantically similar content
- **Tag Filtering**: Filter by AI-generated categories
- **File Type Filter**: Search only images, videos, etc.
- **Date Range Filter**: Find files from specific periods
- **Similarity Scores**: See relevance percentages
- **Fast Indexing**: Sub-second search on 100,000+ files

---

### 🎨 Gallery Management

#### View Modes
- **All Files**: Complete multimedia library
- **Images Only**: Photo gallery
- **Videos**: Video library
- **Documents**: Document browser
- **Audio**: Music and recordings library
- **Favorites**: Only starred content
- **Trash**: Deleted items (recoverable)

#### Bulk Operations
1. Click **"Select"** button to enter selection mode
2. Click files to select (blue outline indicates selection)
3. Use bulk actions:
   - **Select All**: Select every visible file
   - **Deselect All**: Clear selection
   - **Favorite**: Star selected files
   - **Download**: Download selected files as ZIP
   - **Delete**: Move selected to trash
   - **Add to Collection**: Organize into albums

#### Individual Actions
- **Star/Unstar**: Mark as favorite (★ icon)
- **Download**: Save file to your computer
- **Delete**: Move to trash (recoverable for 30 days)
- **View Details**: See full metadata and AI analysis
- **Edit Tags**: Modify AI-generated tags
- **Rename**: Change filename

---

### 👥 People & Pets (Face Recognition)

#### Automatic Face Clustering

- **Detection**: Uses face_recognition library (dlib-based)
- **Accuracy**: 99.38% face recognition accuracy
- **Automatic Grouping**: Cosine similarity clustering
- **Threshold**: 0.6 (adjustable for stricter/looser matching)
- **Multi-face Support**: Detects and clusters multiple faces per image
- **Pet Support**: Works with pets (dogs, cats) too!

#### Naming & Organization

1. Navigate to **People & Pets** page
2. See all detected face clusters with thumbnails
3. Click cluster name to rename:
   - "Mom", "Dad", "Sister"
   - "Max" (dog), "Luna" (cat)
   - Any custom name
4. Click cluster to view all photos/videos of that person/pet
5. Merge clusters if duplicates detected

---

### ⚙️ Settings & Configuration

#### AI Model Configuration

**Captioning Models:**
- `florence` - Microsoft Florence-2 (recommended, most accurate)
- `blip` - Salesforce BLIP (faster, good quality)

**Embedding Models:**
- `clip` - OpenAI CLIP (512 dims, balanced)
- `siglip` - Google SigLIP (768 dims, better accuracy)
- `aimv2` - Apple AIMv2 (1024 dims, best quality, slower)

**OCR Engines:**
- `auto` - Automatic selection based on content
- `paddleocr` - PaddleOCR (faster, good for Asian languages)
- `tesseract` - Tesseract (better for Latin scripts)

**Face Detection:**
- Enable/disable face recognition
- Adjust clustering threshold
- Configure detection sensitivity

#### Ollama Setup (Optional, for Enhanced Descriptions)

```bash
# LLaVA vision model (recommended for images/videos)
docker compose exec ollama ollama pull llava:13b-v1.6

# Qwen for document analysis
docker compose exec ollama ollama pull qwen2.5:7b

# Other models
docker compose exec ollama ollama pull mistral
docker compose exec ollama ollama pull llama2
```

Enable in Settings → AI Configuration → Ollama → Select model → Save

#### System Settings

- **Storage Path**: Configure storage location
- **Queue Configuration**: Worker settings
- **Cache Settings**: Redis/file cache behavior
- **Upload Limits**: Max file size and batch limits
- **Processing Options**: Enable/disable specific features
- **Backup Settings**: Automatic backup configuration

---

## 🛠️ Technical Stack

### Backend Framework
| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Framework | Laravel | 12.x | Modern PHP framework |
| Frontend | Livewire | 3.x | Reactive components without JavaScript frameworks |
| PHP | PHP-FPM | 8.4 | Latest PHP with JIT compiler |
| Web Server | Nginx | Alpine | High-performance reverse proxy |
| Database | PostgreSQL | 16+ | Robust relational database |
| Vector Search | pgvector | Latest | High-performance similarity search |
| Queue | Laravel Queues | Database | Background job processing |
| Cache | Laravel Cache | File/Redis | Performance optimization |
| Authentication | Sanctum | 4.x | API token authentication |

### AI & Machine Learning
| Component | Technology | Purpose |
|-----------|-----------|---------|
| AI Framework | FastAPI | High-performance Python API |
| Python | 3.12 | Latest stable Python |
| **Image AI** | | |
| Captioning | Florence-2/BLIP | Image-to-text generation |
| Embeddings | CLIP/SigLIP/AIMv2 | Vector embeddings (512/768/1024d) |
| Face Detection | face_recognition (dlib) | Facial recognition and clustering |
| **Video AI** | | |
| Scene Analysis | OpenCV | Frame extraction and analysis |
| Object Detection | YOLO (via Florence-2) | Object tracking |
| **Audio AI** | | |
| Transcription | OpenAI Whisper | Speech-to-text |
| Audio Analysis | librosa | Audio feature extraction |
| **Document AI** | | |
| OCR | Tesseract + PaddleOCR | Text extraction |
| PDF Processing | PyMuPDF + pdf2image | Document analysis |
| **LLM (Optional)** | | |
| Enhancement | Ollama (LLaVA/Qwen) | Rich descriptions and summaries |

### Infrastructure & DevOps
| Component | Technology | Purpose |
|-----------|-----------|---------|
| Containerization | Docker Compose | Multi-container orchestration |
| Reverse Proxy | Nginx | Request routing |
| Image Processing | Node.js + Sharp | Fast thumbnail generation |
| Volumes | Docker Volumes | Persistent storage |
| Networks | Docker Networks | Service isolation |
| Health Checks | Docker Healthcheck | Service monitoring |
| Logging | Docker JSON | Structured logging (10MB x 3 files) |

### Resilience & Reliability
| Component | Purpose |
|-----------|---------|
| Circuit Breaker | Prevents cascading failures |
| Retry Mechanism | Exponential backoff for transient failures |
| Health Monitoring | Continuous service health checks |
| Auto-restart | Automatic service recovery |
| Resource Limits | Memory and CPU constraints |
| Graceful Shutdown | Clean service termination |

---

## 📂 Project Structure

```
Avinash-EYE/
├── app/
│   ├── Console/Commands/        # Artisan commands (8 files)
│   │   ├── CreateDefaultUser.php
│   │   ├── ExportTrainingData.php
│   │   ├── MonitorSystem.php
│   │   ├── QueueWorkerHeartbeat.php
│   │   ├── ReprocessImages.php
│   │   └── ResetSystem.php
│   ├── Events/                  # Laravel events
│   │   └── ImageProcessed.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/BatchUploadController.php
│   │       ├── DocumentController.php
│   │       └── MediaController.php
│   ├── Jobs/                    # Queue jobs
│   │   ├── ProcessBatchImages.php
│   │   ├── ProcessBatchUpload.php
│   │   └── ProcessImageAnalysis.php
│   ├── Livewire/                # Livewire components (11 files)
│   │   ├── Auth/               # Authentication components
│   │   ├── Collections.php
│   │   ├── EnhancedImageGallery.php
│   │   ├── ImageGallery.php
│   │   ├── ImageSearch.php
│   │   ├── ImageUploader.php
│   │   ├── InstantImageUploader.php
│   │   ├── PeopleAndPets.php
│   │   ├── ProcessingStatus.php
│   │   ├── Settings.php
│   │   └── SystemMonitor.php
│   ├── Models/                  # Eloquent models (11 files)
│   │   ├── MediaFile.php       # Base model (STI)
│   │   ├── ImageFile.php
│   │   ├── VideoFile.php
│   │   ├── AudioFile.php
│   │   ├── DocumentFile.php
│   │   ├── ArchiveFile.php
│   │   ├── BatchUpload.php
│   │   ├── DetectedFace.php
│   │   ├── FaceCluster.php
│   │   ├── Setting.php
│   │   └── User.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── TelescopeServiceProvider.php
│   ├── Repositories/            # Repository pattern
│   │   └── ImageRepository.php
│   └── Services/                # Business logic (17 services)
│       ├── AiService.php
│       ├── CacheService.php
│       ├── CircuitBreakerService.php
│       ├── FaceClusteringService.php
│       ├── FileService.php
│       ├── ImageService.php
│       ├── MediaFileService.php
│       ├── MediaProcessorService.php
│       ├── MetadataService.php
│       ├── NodeImageProcessorService.php
│       ├── RetryService.php
│       ├── SearchService.php
│       ├── SystemMonitorService.php
│       └── Processors/
│           ├── ArchiveProcessor.php
│           ├── AudioProcessor.php
│           ├── DocumentProcessor.php
│           └── VideoProcessor.php
├── config/
│   ├── ai.php                   # AI service configuration
│   ├── sanctum.php              # API authentication
│   └── telescope.php            # Debugging (dev only)
├── database/
│   ├── migrations/              # Database schema (18 files)
│   │   ├── *_enable_pgvector_extension.php
│   │   ├── *_create_media_files_table.php
│   │   ├── *_create_face_clusters_table.php
│   │   ├── *_create_detected_faces_table.php
│   │   ├── *_create_batch_uploads_table.php
│   │   ├── *_create_personal_access_tokens_table.php
│   │   └── *_add_analysis_coverage_fields.php
│   └── seeders/
│       ├── SettingsSeeder.php
│       └── UserSeeder.php
├── docker/
│   ├── laravel/
│   │   ├── Dockerfile           # Laravel container
│   │   ├── init.sh              # Auto-initialization
│   │   └── uploads.ini          # PHP upload configuration
│   ├── nginx/
│   │   └── default.conf         # Nginx configuration
│   └── ollama/
│       ├── init-models.sh       # Auto-pull Ollama models
│       └── healthcheck.sh       # Health monitoring
├── node-image-processor/        # Node.js microservice
│   ├── server.js
│   ├── processors/
│   │   ├── imageProcessor.js
│   │   └── thumbnailGenerator.js
│   ├── Dockerfile
│   └── package.json
├── python-ai/                   # Python AI microservice
│   ├── main.py                  # FastAPI application
│   ├── main_multimedia.py       # Multimedia analysis
│   ├── comprehensive_analyzer.py # Enhanced analysis
│   ├── prompts.py               # LLM prompts
│   ├── train_model.py           # ML training
│   ├── requirements.txt         # Python dependencies
│   ├── Dockerfile               # Python container
│   └── startup.sh               # Auto-training script
├── resources/
│   └── views/
│       ├── layouts/app.blade.php
│       └── livewire/            # Component views (17 files)
├── storage/
│   └── app/
│       ├── public/              # Public storage
│       │   └── images/          # Uploaded multimedia
│       └── training/            # AI training data
├── docs/                        # 📚 Comprehensive documentation (47 files)
│   ├── AI_LEARNING_COMPLETE.md
│   ├── DOCKER_OLLAMA_SETUP.md
│   ├── FACE_RECOGNITION_STATUS.md
│   ├── INSTANT_UPLOAD_GUIDE.md
│   ├── MODEL_SELECTION_GUIDE.md
│   ├── PRODUCTION_DEPLOYMENT.md
│   ├── PROJECT_SUMMARY.md
│   ├── QUICK_REFERENCE.md
│   └── ... (39 more documentation files)
├── tests/
│   ├── Feature/                 # Feature tests (8 files)
│   ├── Unit/                    # Unit tests (4 files)
│   ├── Pest.php
│   └── TestCase.php
├── docker-compose.yml           # 🏭 Production-ready orchestration
├── .env.example
├── .env.production              # Production template
├── .dockerignore                # Docker build optimization
├── start-production.sh          # 🚀 One-command deployment
├── fresh-start.sh               # Complete reset script
├── setup-ollama.sh              # Ollama setup
├── PRODUCTION_READY.md
└── README.md                    # This file
```

---

## 🔧 Configuration Guide

### Environment Variables

Key configurations in `.env`:

```env
# Application
APP_NAME="Avinash-EYE"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080
APP_KEY=base64:... # Auto-generated

# Database (PostgreSQL + pgvector)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=avinash_eye
DB_USERNAME=avinash
DB_PASSWORD=YourSecurePassword123!  # CHANGE THIS!

# AI Service
AI_API_URL=http://python-ai:8000
AI_DEFAULT_TIMEOUT=120
AI_CIRCUIT_BREAKER_THRESHOLD=5
AI_CIRCUIT_BREAKER_RECOVERY=60

# Queue
QUEUE_CONNECTION=database

# Ollama (Optional)
OLLAMA_URL=http://ollama:11434
OLLAMA_ENABLED=true
OLLAMA_MODEL=llava:13b-v1.6

# Node Image Processor
NODE_PROCESSOR_URL=http://node-processor:3000

# Default Admin User
DEFAULT_USER_EMAIL=admin@avinash-eye.local
DEFAULT_USER_PASSWORD=Admin@123
DEFAULT_USER_NAME=Administrator
```

### Docker Services Overview

| Service | Port | Memory | Purpose | Features |
|---------|------|--------|---------|----------|
| **nginx** | 8080 | 256MB | Web server (public access) | Auto-restart, health checks, log rotation |
| **laravel-app** | 9000 | 2GB | PHP-FPM (internal) | Auto-init, migrations, optimization |
| **queue-worker** | - | 1GB | Background jobs (24/7) | Auto-restart, max 100 jobs, heartbeat |
| **scheduler** | - | 512MB | Cron jobs | Handles scheduled tasks |
| **python-ai** | 8000 | 8GB | AI service (internal) | Auto-download models, health checks |
| **node-processor** | 3000 | 512MB | Image processing | Fast thumbnails, Sharp library |
| **db** | 5432 | 1GB | PostgreSQL + pgvector | Auto-backup ready, health checks |
| **ollama** | 11434 | 8GB | LLM service (optional) | Auto-pull LLaVA, 24/7 uptime |
| **adminer** | 8081 | 128MB | Database admin | Optional, for debugging |

**Production Features:**
- ✅ All services have comprehensive health checks
- ✅ Automatic restart on failure (24/7 reliability)
- ✅ Resource limits prevent memory leaks
- ✅ Log rotation (10MB x 3 files per service)
- ✅ Background model downloads (non-blocking startup)
- ✅ Dedicated queue worker with heartbeat monitoring
- ✅ Circuit breaker for graceful degradation
- ✅ Retry mechanisms with exponential backoff

---

## 🎮 Usage Guide

### Command Line Operations

#### System Management
```bash
# Check system status
docker compose ps
docker compose logs -f

# Individual service logs
docker compose logs -f python-ai
docker compose logs -f queue-worker
docker compose logs -f laravel-app

# Restart services
docker compose restart python-ai
docker compose restart queue-worker

# Stop all services
docker compose down

# Start services
docker compose up -d

# Rebuild containers
docker compose up -d --build
```

#### Database Operations
```bash
# Access database CLI
docker compose exec db psql -U avinash -d avinash_eye

# Create backup
docker compose exec db pg_dump -U avinash avinash_eye > backup.sql

# Restore backup
docker compose exec -T db psql -U avinash avinash_eye < backup.sql

# Run migrations
docker compose exec laravel-app php artisan migrate

# Seed settings
docker compose exec laravel-app php artisan db:seed --class=SettingsSeeder
```

#### Image/Media Management
```bash
# Reprocess media files
docker compose exec laravel-app php artisan images:reprocess --batch=50

# Reprocess only missing features
docker compose exec laravel-app php artisan images:reprocess --only-missing

# Force reprocess all
docker compose exec laravel-app php artisan images:reprocess --force

# Export AI training data
docker compose exec laravel-app php artisan export:training-data --limit=1000
```

#### Queue Management
```bash
# Monitor queue
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

#### User Management
```bash
# Create default user
docker compose exec laravel-app php artisan user:create-default

# Create custom user
docker compose exec laravel-app php artisan user:create-default \
  --email=admin@example.com \
  --password=SecurePass123! \
  --name="Admin User"

# List users
docker compose exec laravel-app php artisan tinker \
  --execute="User::all()->each(fn(\$u) => echo \$u->email . PHP_EOL);"
```

#### Complete System Reset
```bash
# DANGER: This deletes ALL data!
./fresh-start.sh

# Manual reset (alternative)
docker compose down -v
rm -rf storage/app/public/images/*
rm -rf storage/logs/*
docker compose up -d --build
docker compose exec laravel-app php artisan migrate:fresh --seed
```

---

## 🧪 Testing & Troubleshooting

### Health Checks

```bash
# Check all services
curl http://localhost:8080              # Laravel app
curl http://localhost:8000/health       # Python AI
curl http://localhost:3000/health       # Node processor
curl http://localhost:11434/api/tags    # Ollama

# Check database
docker compose exec db pg_isready -U avinash

# Check queue worker
docker compose exec laravel-app php artisan queue:monitor
```

### Common Issues & Solutions

#### 1. **ProcessImageAnalysis Jobs Failing**

**Symptoms**: Jobs fail with "AI service returned error" or NULL model configuration

**Solutions**:
```bash
# Clear cache and reset circuit breaker
docker compose exec laravel-app php artisan cache:clear

# Check/update AI model settings
docker compose exec laravel-app php artisan tinker --execute="
App\Models\Setting::set('captioning_model', 'florence');
App\Models\Setting::set('embedding_model', 'clip');
App\Models\Setting::set('ollama_model', 'llava:latest');
echo 'Settings updated';
"

# Restart services
docker compose restart python-ai queue-worker
```

#### 2. **Circuit Breaker Open**

**Symptoms**: "Circuit breaker is OPEN - rejecting request"

**Solutions**:
```bash
# Clear circuit breaker state
docker compose exec laravel-app php artisan cache:clear

# Or manually
docker compose exec laravel-app php artisan tinker --execute="
Cache::forget('circuit_breaker:ai_service:state');
Cache::forget('circuit_breaker:ai_service:failures');
Cache::forget('circuit_breaker:ai_service:last_failure_time');
echo 'Circuit breaker reset';
"
```

#### 3. **AI Models Not Loading**

**Symptoms**: Python service crashes or takes forever to start

**Solutions**:
```bash
# Check Python service logs
docker compose logs python-ai | tail -50

# Increase Docker memory to 16GB
# Docker Desktop → Settings → Resources → Memory → 16GB

# Clear model cache and restart
docker volume rm avinash-eye_model-cache
docker compose up -d --build python-ai
```

#### 4. **Queue Jobs Not Processing**

**Symptoms**: Jobs stay in pending status forever

**Solutions**:
```bash
# Check queue worker is running
docker compose ps queue-worker

# Check queue worker logs
docker compose logs queue-worker --tail=50

# Verify queue configuration
docker compose exec laravel-app php artisan tinker --execute="
echo 'Pending jobs: ' . DB::table('jobs')->count() . PHP_EOL;
echo 'Failed jobs: ' . DB::table('failed_jobs')->count() . PHP_EOL;
"

# Restart queue worker
docker compose restart queue-worker
```

#### 5. **Permission Errors**

**Symptoms**: Laravel can't write to storage

**Solutions**:
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Inside Docker
docker compose exec laravel-app chown -R www-data:www-data storage bootstrap/cache
```

#### 6. **Out of Memory**

**Symptoms**: Services crash, Docker unresponsive

**Solutions**:
- Increase Docker Desktop memory to 16GB+
- Disable Ollama if not needed (frees ~8GB)
- Process fewer files simultaneously
- Use lighter AI models (clip instead of aimv2)

---

## 📊 Performance & Scaling

### Performance Expectations

| Metric | Expected Value | Notes |
|--------|----------------|-------|
| **Initial Setup** | 15-20 minutes | One-time model downloads (~15GB) |
| **Subsequent Starts** | 30-60 seconds | Models cached in volumes |
| **Image Upload** | < 500ms | Instant UI feedback |
| **AI Analysis (Image)** | 5-20 seconds | Background processing |
| **AI Analysis (Video)** | 30-120 seconds | Depends on duration |
| **AI Analysis (Document)** | 10-60 seconds | Depends on pages |
| **Semantic Search** | < 300ms | With 10,000+ files |
| **Face Detection** | 3-8 seconds | Per image, background |
| **Gallery Load** | < 1 second | With lazy loading |
| **Thumbnail Generation** | < 200ms | Node.js Sharp processor |

### Scaling Guidelines

| Collection Size | RAM | Storage | Workers | Performance |
|----------------|-----|---------|---------|-------------|
| < 5,000 files | 16GB | ~50GB | 1 | Excellent |
| 5,000-20,000 | 24GB | ~200GB | 2 | Good |
| 20,000-50,000 | 32GB | ~500GB | 3-4 | Fair |
| > 50,000 | 64GB+ | 1TB+ | 5+ | Requires optimization |

### Optimization Tips

**For Large Collections:**
- Scale queue workers horizontally (add more containers)
- Enable Redis for caching and queues
- Increase pgvector index lists
- Use batch processing for bulk uploads
- Adjust resource limits in docker-compose.yml
- Implement CDN for static assets
- Use lighter AI models

**Resource Allocation:**
- **Database**: 1-2GB (scale with collection size)
- **Python AI**: 8GB (required for models)
- **Ollama**: 8GB (optional, disable to save memory)
- **Laravel**: 2GB (sufficient for most workloads)
- **Queue Workers**: 1GB each (scale horizontally)
- **Node Processor**: 512MB (very efficient)

---

## 🔒 Security & Privacy

### Privacy Guarantees

- ✅ **100% Local Processing**: All AI runs on your hardware
- ✅ **No External APIs**: Zero internet calls after initial setup
- ✅ **No Telemetry**: Absolutely no tracking or analytics
- ✅ **Open Source**: Fully transparent and auditable
- ✅ **Self-Hosted**: Complete data sovereignty
- ✅ **No Third Parties**: No dependencies on external services

### Production Security Checklist

- [ ] Change `DB_PASSWORD` from default value
- [ ] Change default admin password after first login
- [ ] Set `APP_DEBUG=false` in production
- [ ] Ensure `APP_KEY` is unique (auto-generated)
- [ ] Use HTTPS with reverse proxy (nginx/Caddy + Let's Encrypt)
- [ ] Configure firewall to only expose port 443 (HTTPS)
- [ ] Enable rate limiting on sensitive endpoints
- [ ] Regular backups (database + images)
- [ ] Keep Docker images updated (`docker compose pull`)
- [ ] Review access logs regularly
- [ ] Implement fail2ban for brute force protection
- [ ] Use strong passwords for all accounts
- [ ] Enable 2FA (future feature)

### Backup Strategy

```bash
# Full backup script
#!/bin/bash
BACKUP_DIR="/backups/avinash-eye"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
docker compose exec -T db pg_dump -U avinash avinash_eye \
  > "$BACKUP_DIR/db_$DATE.sql"

# Backup images and multimedia
tar -czf "$BACKUP_DIR/media_$DATE.tar.gz" \
  storage/app/public/images/

# Backup environment
cp .env "$BACKUP_DIR/env_$DATE.backup"

echo "Backup completed: $DATE"
```

---

## 🎯 Roadmap & Contributing

### Planned Features

- [ ] **Mobile Apps**: iOS and Android native apps
- [ ] **Live Photos**: Apple Live Photo support
- [ ] **Sharing**: Secure share links with expiration
- [ ] **Duplicate Detection**: Find and merge similar files
- [ ] **Advanced Editing**: Cropping, filters, adjustments
- [ ] **Timeline View**: Visual chronological browser
- [ ] **Map View**: GPS-based photo map
- [ ] **Slideshow**: Automatic presentations
- [ ] **RAW Support**: Professional photo formats (CR2, NEF, ARW)
- [ ] **Video Editing**: Basic trim and clip features
- [ ] **Multi-User**: User accounts and permissions
- [ ] **API v2**: Comprehensive RESTful API
- [ ] **Webhooks**: Event notifications
- [ ] **Plugins**: Extension system
- [ ] **GPU Acceleration**: CUDA support for faster AI

### Contributing

Contributions are welcome! Please:

1. 🐛 Report bugs via GitHub issues
2. 💡 Suggest features
3. 📝 Improve documentation
4. 🔧 Submit pull requests
5. ⭐ Star if you find it useful

**Development Setup:**
```bash
git clone https://github.com/yourusername/Avinash-EYE.git
cd Avinash-EYE
cp .env.example .env
docker compose up -d --build
```

---

## 📄 License

MIT License - See LICENSE file for details

Free for personal and commercial use.

---

## 🙏 Acknowledgments

### AI Models & Libraries
- **Microsoft** - Florence-2 vision model
- **Salesforce** - BLIP image captioning
- **OpenAI** - CLIP embeddings, Whisper transcription
- **Meta** - LLaVA multimodal LLM
- **Google** - SigLIP embeddings
- **Apple** - AIMv2 embeddings
- **dlib** - Face recognition library
- **PaddlePaddle** - PaddleOCR text extraction
- **Tesseract** - OCR engine

### Frameworks & Tools
- **Laravel** - Elegant PHP framework
- **Livewire** - Reactive PHP components
- **FastAPI** - Modern Python API framework
- **PostgreSQL** - World's most advanced open source database
- **pgvector** - Vector similarity search
- **Docker** - Containerization platform
- **Sharp** - High-performance Node.js image processing
- **HuggingFace** - AI model repository

### Special Thanks

To the open-source community for making privacy-focused AI accessible to everyone! 🎉

---

## 📞 Support & Community

### Getting Help

1. 📖 **Documentation**: Check `/docs` folder (47 comprehensive guides)
2. 🔍 **Search Issues**: GitHub issues for existing solutions
3. 💬 **New Issue**: Open detailed bug report or feature request
4. 📧 **Email**: Contact maintainers for urgent issues

### Reporting Bugs

Include:
- System info (OS, Docker version, RAM)
- Steps to reproduce
- Expected vs actual behavior
- Relevant logs (`docker compose logs`)
- Screenshots if applicable

---

## 📈 Comparison Matrix

### vs Google Photos

| Feature | Avinash-EYE | Google Photos |
|---------|-------------|---------------|
| **Privacy** | ✅ 100% local | ❌ Cloud-based |
| **Cost** | ✅ Free forever | ⚠️ Storage limits ($) |
| **Offline** | ✅ Fully offline | ❌ Requires internet |
| **AI Search** | ✅ Advanced semantic | ✅ Good |
| **Face Recognition** | ✅ Yes (local) | ✅ Yes (cloud) |
| **Video Support** | ✅ Yes | ✅ Yes |
| **Document OCR** | ✅ Yes | ⚠️ Limited |
| **Audio Transcription** | ✅ Yes | ❌ No |
| **Self-hosted** | ✅ Yes | ❌ No |
| **Unlimited Storage** | ✅ Yes | ❌ Paid plans |
| **Open Source** | ✅ Yes | ❌ No |
| **Customizable** | ✅ Fully | ❌ No |

### vs Immich

| Feature | Avinash-EYE | Immich |
|---------|-------------|--------|
| **AI Models** | ✅ Florence-2, CLIP, Whisper | ⚠️ Basic CLIP |
| **Document OCR** | ✅ Full support | ❌ No |
| **Audio Transcription** | ✅ Whisper AI | ❌ No |
| **Archive Support** | ✅ ZIP, RAR, 7Z | ❌ No |
| **Batch Upload** | ✅ Advanced | ✅ Basic |
| **Face Clustering** | ✅ Advanced | ✅ Good |
| **Mobile Apps** | ⏳ Planned | ✅ Yes |
| **Circuit Breaker** | ✅ Yes | ❌ No |
| **Multi-pass Analysis** | ✅ Yes | ❌ No |

---

<div align="center">

**Built with ❤️ for privacy, control, and intelligence**

---

### ⭐ If you find this project helpful, please star the repository!

[📚 Documentation](docs/) • [🐛 Report Bug](https://github.com/yourusername/Avinash-EYE/issues) • [💡 Request Feature](https://github.com/yourusername/Avinash-EYE/issues) • [💬 Discussions](https://github.com/yourusername/Avinash-EYE/discussions)

---

**Made with privacy and control in mind. Your files, your AI, your way.** 🔒🤖

</div>
