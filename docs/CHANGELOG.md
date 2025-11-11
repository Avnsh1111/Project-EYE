# 📝 Changelog

All notable changes to Avinash-EYE will be documented in this file.

---

## [2.0.0] - 2024-11-10 🎉

### 🚀 Major Features Added

#### Enhanced Gallery (Google Photos-Style)

**Selection Mode**
- ✅ Multi-select photos with visual feedback
- ✅ Select/deselect with single click
- ✅ Select All / Deselect All buttons
- ✅ Blue outline on selected items
- ✅ Selection counter in header

**Bulk Operations**
- ✅ Bulk delete - Move multiple photos to trash
- ✅ Bulk download - Download multiple photos
- ✅ Bulk favorite - Star multiple photos
- ✅ Bulk unfavorite - Remove stars from multiple
- ✅ Staggered downloads to avoid browser blocking

**Favorites System**
- ✅ Star/unstar individual photos
- ✅ Favorites filter button
- ✅ Gold star indicator on thumbnails
- ✅ Favorites count in stats
- ✅ Dedicated favorites view

**Trash & Recovery**
- ✅ Soft delete (photos moved to trash)
- ✅ Trash view with count badge
- ✅ Restore deleted photos
- ✅ Permanently delete with confirmation
- ✅ Trash counter in UI

**View Tracking**
- ✅ View counter for each photo
- ✅ Last viewed timestamp
- ✅ Auto-increment on lightbox open
- ✅ Displayed in info sidebar

**Enhanced UI**
- ✅ Material Design 3 styling
- ✅ Smooth animations
- ✅ Status badges (trash, favorites)
- ✅ Empty states for all views
- ✅ Responsive design
- ✅ Loading indicators

**Keyboard Shortcuts**
- ✅ `Escape` - Close lightbox / Cancel selection
- ✅ `Delete` - Delete selected photos
- ✅ `Ctrl/Cmd + A` - Select all photos

**Download Functionality**
- ✅ Single photo download
- ✅ Bulk photo download
- ✅ Original quality preserved
- ✅ Original filenames preserved
- ✅ Browser-friendly download mechanism

### 🗄️ Database Changes

**New Columns in `image_files` table**:
```sql
- is_favorite (BOOLEAN) - Star status
- deleted_at (TIMESTAMP) - Soft delete timestamp
- view_count (INTEGER) - Number of views
- last_viewed_at (TIMESTAMP) - Last view time
- edit_history (JSONB) - Future: edit tracking
- album (VARCHAR) - Future: album organization
```

**New Migration**: `2024_01_05_000000_add_gallery_features_to_image_files.php`

### 📁 New Files

**Components**:
- `app/Livewire/EnhancedImageGallery.php` - Enhanced gallery component
- `resources/views/livewire/enhanced-image-gallery.blade.php` - Gallery view

**Documentation**:
- `GALLERY_FEATURES.md` - Comprehensive feature guide
- `QUICK_REFERENCE.md` - Quick reference cheat sheet
- `FEATURES_COMPARISON.md` - Comparison with Google Photos
- `CHANGELOG.md` - This file

**Migration**:
- `database/migrations/2024_01_05_000000_add_gallery_features_to_image_files.php`

### 🔧 Modified Files

**Models**:
- `app/Models/ImageFile.php` - Added SoftDeletes trait, new fillable fields, new casts

**Routes**:
- `routes/web.php` - Updated gallery route to use EnhancedImageGallery

### 🎨 UI Improvements

- Google Photos-style masonry grid
- Date separators for photo grouping
- Floating action bar in lightbox
- Enhanced empty states
- Status badges and counters
- Smooth transitions and animations
- Responsive toolbar

### ⚡ Performance

- Lazy loading for images
- Efficient database queries
- Client-side download handling
- Optimized Livewire updates
- Staggered bulk operations

---

## [1.5.0] - 2024-11-09

### Added Settings Page

**Model Selection**
- ✅ Choose captioning models (BLIP variants, ViT-GPT2)
- ✅ Choose embedding models (CLIP variants, DINOv2)
- ✅ Ollama integration toggle
- ✅ Select Ollama model (Llama2, Mistral, Mixtral, etc.)
- ✅ Face detection toggle
- ✅ AI service health check

**New Files**:
- `app/Livewire/Settings.php`
- `resources/views/livewire/settings.blade.php`
- `app/Models/Setting.php`
- `database/migrations/2024_01_04_000000_create_settings_table.php`

**Modified**:
- `app/Services/AiService.php` - Dynamic model loading
- `python-ai/main.py` - Accept model parameters

---

## [1.4.0] - 2024-11-08

### Metadata Preservation

**EXIF Extraction**
- ✅ Camera make and model
- ✅ Lens model
- ✅ Date taken
- ✅ Exposure settings (ISO, aperture, shutter speed)
- ✅ Focal length
- ✅ GPS coordinates
- ✅ Complete EXIF data stored as JSON

**File Metadata**
- ✅ Original filename preservation
- ✅ MIME type
- ✅ File size
- ✅ Image dimensions

**New Columns**:
```sql
- original_filename, mime_type, file_size, width, height
- exif_data (JSONB)
- camera_make, camera_model, lens_model
- date_taken, exposure_time, f_number, iso, focal_length
- gps_latitude, gps_longitude, gps_location_name
```

**New Migration**: `2024_01_03_000000_add_metadata_to_image_files.php`

**Modified**:
- `app/Livewire/ImageUploader.php` - Metadata extraction
- `app/Livewire/ImageGallery.php` - Metadata display
- `resources/views/livewire/image-gallery.blade.php` - Enhanced lightbox

---

## [1.3.0] - 2024-11-07

### Enhanced AI Analysis

**Ollama Integration**
- ✅ Detailed descriptions using Ollama
- ✅ Meta tag generation
- ✅ Multiple Ollama models supported

**Face Detection**
- ✅ Face count per image
- ✅ Face encodings stored
- ✅ Face search capability (backend ready)

**New Columns**:
```sql
- detailed_description (TEXT)
- meta_tags (JSONB)
- face_count (INTEGER)
- face_encodings (JSONB)
```

**New Migration**: `2024_01_02_000000_add_enhanced_fields_to_image_files.php`

**Modified**:
- `python-ai/requirements.txt` - Added face-recognition, ollama
- `python-ai/main.py` - Face detection and Ollama
- `app/Services/AiService.php` - Handle new fields

---

## [1.2.0] - 2024-11-06

### UI Overhaul

**Google Material Design 3**
- ✅ Material Design color scheme
- ✅ Material Symbols icons
- ✅ Google Sans font
- ✅ Clean, modern interface
- ✅ Responsive navigation

**Gallery Redesign**
- ✅ Masonry grid layout
- ✅ Date separators
- ✅ Hover overlays
- ✅ Enhanced lightbox
- ✅ Metadata sidebar

**Modified**:
- `resources/views/layouts/app.blade.php` - Material Design
- `resources/views/livewire/image-gallery.blade.php` - Google Photos style
- `resources/views/livewire/image-uploader.blade.php` - Clean upload UI
- `resources/views/livewire/image-search.blade.php` - Modern search
- `resources/views/welcome.blade.php` - Hero section

---

## [1.1.0] - 2024-11-05

### Search Improvements

**Better Vector Index**
- ✅ Switched from IVFFlat to HNSW
- ✅ Better performance with small datasets
- ✅ Faster similarity search
- ✅ Added similarity threshold

**Modified**:
- `database/migrations/2024_01_01_000001_create_image_files_table.php`
- `app/Models/ImageFile.php` - Added MIN_SIMILARITY constant

---

## [1.0.0] - 2024-11-04 🎊

### Initial Release

**Core Features**
- ✅ Multi-image upload
- ✅ AI-powered image captioning (BLIP)
- ✅ Vector embeddings (CLIP)
- ✅ Semantic search
- ✅ PostgreSQL with pgvector
- ✅ Docker Compose orchestration
- ✅ Laravel 12 + Livewire 3
- ✅ Python FastAPI
- ✅ 100% offline capable

**Components**:
- Laravel application
- Python AI service
- PostgreSQL with pgvector
- Nginx web server
- Docker Compose setup

**Files Created**:
- Full Laravel project structure
- FastAPI service (`python-ai/`)
- Docker configuration
- Database migrations
- Livewire components

---

## Feature Timeline

```
v1.0.0  [Core]                     ████████████░░░░░░░░░░░░
v1.1.0  [Search Fix]               ░░░░░░░░░░░░████░░░░░░░░
v1.2.0  [UI Overhaul]              ░░░░░░░░░░░░░░░░████████
v1.3.0  [Enhanced AI]              ░░░░░░░░░░░░░░░░░░░░████
v1.4.0  [Metadata]                 ░░░░░░░░░░░░░░░░░░░░░░██
v1.5.0  [Settings]                 ░░░░░░░░░░░░░░░░░░░░░░░█
v2.0.0  [Enhanced Gallery] 🎉      ░░░░░░░░░░░░░░░░░░░░░░░░
```

---

## Statistics

### Lines of Code

| Version | Total LOC | Laravel | Python | Views | Docs |
|---------|-----------|---------|--------|-------|------|
| v1.0.0 | ~5,000 | ~2,500 | ~800 | ~1,200 | ~500 |
| v2.0.0 | ~12,000 | ~5,500 | ~1,500 | ~3,500 | ~1,500 |

### Files Count

| Version | Total Files | Components | Migrations | Docs |
|---------|-------------|------------|------------|------|
| v1.0.0 | ~50 | 3 | 1 | 2 |
| v2.0.0 | ~75 | 6 | 5 | 7 |

### Features Count

| Version | Total Features | Gallery | AI | Search | Settings |
|---------|----------------|---------|-----|--------|----------|
| v1.0.0 | 10 | 3 | 3 | 2 | 0 |
| v2.0.0 | 35+ | 18 | 7 | 5 | 5 |

---

## Upgrade Guide

### From v1.x to v2.0.0

1. **Pull Latest Code**
   ```bash
   git pull origin main
   ```

2. **Run New Migrations**
   ```bash
   docker-compose exec laravel-app php artisan migrate
   ```

3. **Clear Caches**
   ```bash
   docker-compose exec laravel-app php artisan route:clear
   docker-compose exec laravel-app php artisan view:clear
   docker-compose exec laravel-app php artisan cache:clear
   ```

4. **Rebuild Containers** (if needed)
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

5. **Done!** Access enhanced gallery at `http://localhost:8080/gallery`

### Breaking Changes

- ❌ None! Fully backwards compatible
- ✅ Old gallery still works
- ✅ New route uses EnhancedImageGallery
- ✅ All existing data preserved

---

## Coming Soon

### v2.1.0 - Image Editor
- Rotate images
- Crop and resize
- Apply filters
- Adjust brightness/contrast
- Save edits

### v2.2.0 - Albums
- Create albums
- Organize photos
- Album covers
- Share albums

### v2.3.0 - Advanced Sharing
- Generate share links
- Password protection
- Expiring links
- Download limits

### v2.4.0 - Multi-User
- User accounts
- Permissions
- Shared galleries
- Comments

---

## Contributors

- **Avinash** - Original developer
- **AI Assistant** - Code generation & documentation

---

## License

MIT License - See LICENSE file

---

**Keep Building! 🚀**


