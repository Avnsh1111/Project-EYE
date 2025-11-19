# 🚀 Node.js Parallel Image Processing

## Overview

A high-performance Node.js service has been added to process multiple images in parallel, significantly speeding up batch uploads and image processing.

## Architecture

```
Laravel (Upload) → Node.js Processor → Python AI Service
                      ↓
              Parallel Processing (10 concurrent)
                      ↓
              Metadata + AI Analysis
```

## Features

### ⚡ **Parallel Processing**
- Processes up to 10 images simultaneously (configurable)
- Uses `p-queue` for efficient concurrency control
- Non-blocking I/O operations

### 🎯 **Batch Operations**
- `/process/batch` - Process multiple images at once
- `/metadata/batch` - Extract metadata for multiple images
- Automatic fallback to individual processing if Node.js unavailable

### 🔧 **Image Optimization**
- Automatic image optimization (compression)
- Thumbnail generation
- Metadata extraction (EXIF, GPS, camera settings)

### 🔗 **Integration**
- Seamlessly integrates with Python AI service
- Coordinates with Laravel queue system
- Automatic fallback if service unavailable

## Service Details

### **Node.js Service**
- **Port**: 3000 (configurable via `NODE_PROCESSOR_PORT`)
- **Concurrency**: 10 images (configurable via `NODE_MAX_CONCURRENT`)
- **Memory**: 2GB limit, 512MB reserved
- **Health Check**: `/health` endpoint

### **API Endpoints**

#### `POST /process`
Process a single image
```json
{
  "filename": "image.jpg",
  "path": "/app/shared/image.jpg",
  "metadata": {...},
  "ai_analysis": {...},
  "optimized_path": "...",
  "thumbnail_path": "..."
}
```

#### `POST /process/batch`
Process multiple images in parallel
```json
{
  "success": true,
  "processed": 5,
  "results": [...]
}
```

#### `POST /metadata`
Extract metadata only (fast, no AI)
```json
{
  "filename": "image.jpg",
  "file_size": 1234567,
  "width": 1920,
  "height": 1080,
  "camera": {...},
  "gps": {...}
}
```

#### `GET /health`
Service health check
```json
{
  "status": "healthy",
  "service": "image-processor",
  "queue": {
    "size": 0,
    "pending": 0,
    "concurrency": 10
  }
}
```

## Configuration

### Environment Variables

Add to `.env`:
```env
NODE_PROCESSOR_URL=http://node-processor:3000
NODE_PROCESSOR_PORT=3000
NODE_PROCESSOR_TIMEOUT=600
NODE_MAX_CONCURRENT=10
```

### Docker Compose

The Node.js service is automatically included in `docker-compose.yml`:
- Service name: `node-processor`
- Depends on: `python-ai`
- Shared volume: `./storage/app/public/images:/app/shared`

## Usage

### Automatic Batch Processing

When uploading multiple images via `InstantImageUploader`:
- **2+ images**: Automatically uses Node.js batch processing via queue (background processing)
- **1 image**: Uses individual processing via queue (background processing)
- **Node.js unavailable**: Falls back to individual processing

**Important**: All image processing happens in the background via Laravel queue. Batch uploads (2+ images) use Node.js for parallel processing, but jobs are dispatched to the queue for asynchronous background processing. The queue worker is required for all image processing.

### Manual Usage

```php
use App\Services\NodeImageProcessorService;

$processor = app(NodeImageProcessorService::class);

// Process single image
$result = $processor->processImage('/path/to/image.jpg');

// Process batch
$results = $processor->processBatch([
    '/path/to/image1.jpg',
    '/path/to/image2.jpg',
    '/path/to/image3.jpg'
]);

// Extract metadata only
$metadata = $processor->extractMetadata('/path/to/image.jpg');
```

## Performance Improvements

### Before (Sequential)
- 10 images × 30 seconds = **5 minutes**

### After (Parallel - 10 concurrent)
- 10 images ÷ 10 concurrent = **~30 seconds**

**Speed Improvement: ~10x faster for batch uploads!**

## Technical Details

### Dependencies
- **Express**: Web framework
- **Sharp**: High-performance image processing
- **Multer**: File upload handling
- **exifr**: Fast EXIF extraction
- **p-queue**: Concurrency control
- **axios**: HTTP client for Python AI service

### Processing Pipeline

1. **Upload** → Images stored temporarily
2. **Metadata Extraction** → Fast EXIF parsing
3. **Image Optimization** → Compression & thumbnails (parallel)
4. **AI Analysis** → Python service call (parallel)
5. **Result Aggregation** → Combine all data
6. **Database Update** → Save to PostgreSQL

### Error Handling

- Individual image failures don't stop batch
- Automatic retry for failed images
- Comprehensive error logging
- Graceful fallback to individual processing

## Monitoring

### Health Check
```bash
curl http://localhost:3000/health
```

### Logs
```bash
docker compose logs -f node-processor
```

### Queue Status
The health endpoint shows:
- Queue size
- Pending jobs
- Concurrency limit

## Troubleshooting

### Service Not Starting
1. Check Node.js container logs: `docker compose logs node-processor`
2. Verify port 3000 is available
3. Check memory limits (needs 512MB+)

### Slow Processing
1. Increase `NODE_MAX_CONCURRENT` (default: 10)
2. Check Python AI service response times
3. Monitor system resources

### Images Not Processing
1. Verify Node.js service is healthy: `/health`
2. Check shared volume mount
3. Verify Python AI service is accessible
4. Check Laravel logs for errors

## Future Enhancements

- [ ] Image format conversion
- [ ] Progressive JPEG generation
- [ ] WebP optimization
- [ ] Image resizing on upload
- [ ] Watermarking support
- [ ] Distributed processing across multiple Node.js instances

