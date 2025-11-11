# 🔄 Intelligent Image Reprocessing

## Overview

Avinash-EYE now includes **intelligent background reprocessing** that automatically improves your image metadata when the system is idle. This ensures all your images benefit from the latest AI models and features.

## ✨ Features

### What Gets Improved

1. **📝 Detailed Descriptions**
   - When Ollama is enabled, images get rich, detailed descriptions
   - Replaces basic captions with comprehensive 3-4 sentence descriptions

2. **🏷️ Better Categorization**
   - Adds or improves meta tags for better searchability
   - Automatically categorizes images by content

3. **👤 Face Detection**
   - When enabled, detects faces and stores face encodings
   - Enables face-based collections and search

4. **🔍 AI-Powered Search**
   - Regenerates embeddings with the latest model
   - Ensures semantic search works optimally

### How It Works

**Smart Prioritization:**
- Images **without face detection** (if enabled) → Highest priority
- Images **without detailed descriptions** (if Ollama enabled) → High priority  
- Images **without meta tags** → Medium priority
- **Older images** (7+ days) → Low priority

**Automatic Schedule:**
- Runs every **30 minutes** in the background
- Processes **20 images** per batch by default
- Never overlaps (waits for previous batch to complete)
- Completely automatic - no manual intervention needed

## 🚀 Quick Start

### Option 1: Automatic (Recommended)

The system automatically reprocesses images every 30 minutes. Just ensure the Laravel scheduler is running:

```bash
# Add to your crontab (run: crontab -e)
* * * * * cd /path/to/Avinash-EYE && php artisan schedule:run >> /dev/null 2>&1
```

Or use the provided script:

```bash
# Start the scheduler daemon
./start-scheduler.sh
```

### Option 2: Manual Reprocessing

Run the command manually whenever you want:

```bash
# Smart mode (default) - processes 10 images that need improvement
php artisan images:reprocess

# Process more images in one batch
php artisan images:reprocess --batch=50

# Only reprocess images missing features
php artisan images:reprocess --only-missing --batch=100

# Force reprocess ALL images (use sparingly!)
php artisan images:reprocess --force --batch=20
```

## 📊 Modes Explained

### 1. Smart Mode (Default)

```bash
php artisan images:reprocess
```

**What it does:**
- Intelligently selects images that will benefit most from reprocessing
- Prioritizes images with missing features
- Balances between new and old images
- Most efficient for general improvements

**Best for:** Daily automatic runs

### 2. Only Missing Mode

```bash
php artisan images:reprocess --only-missing
```

**What it does:**
- ONLY processes images with completely missing features:
  - No face detection
  - No detailed description
  - No meta tags
- Skips images that already have basic metadata

**Best for:** Initial migration after enabling new features

### 3. Force Mode

```bash
php artisan images:reprocess --force
```

**What it does:**
- Reprocesses **every single image** regardless of current state
- Uses current AI settings for all images
- Can take a VERY long time for large libraries

**Best for:** 
- After major model upgrades
- After changing AI settings significantly
- Initial setup with pre-existing images

**⚠️ Warning:** Use force mode sparingly as it will reprocess ALL images!

## 🎯 Use Cases

### Scenario 1: Just Enabled Ollama

You enabled Ollama to get detailed descriptions. Run:

```bash
# Reprocess images that don't have detailed descriptions
php artisan images:reprocess --only-missing --batch=100
```

### Scenario 2: Just Enabled Face Detection

You enabled face detection and want to process existing images:

```bash
# Reprocess images without face data
php artisan images:reprocess --only-missing --batch=50
```

### Scenario 3: Regular Maintenance

Just let the automatic scheduler handle it! It runs every 30 minutes and processes 20 images per batch.

### Scenario 4: Upgraded AI Models

You switched to a better embedding or captioning model:

```bash
# Reprocess all images with new model (do this during off-hours)
php artisan images:reprocess --force --batch=10
```

### Scenario 5: Large Existing Library

You have 1000+ images uploaded before AI features:

```bash
# Start with missing features
php artisan images:reprocess --only-missing --batch=100

# Let automatic scheduler handle the rest over time
```

## ⚙️ Configuration

### Adjusting Batch Size

Edit `/routes/console.php`:

```php
// Process more images per run (faster, more resource intensive)
Schedule::command('images:reprocess --batch=50')
    ->everyThirtyMinutes()

// Process fewer images per run (slower, less resource intensive)
Schedule::command('images:reprocess --batch=5')
    ->everyThirtyMinutes()
```

### Adjusting Frequency

```php
// Run more frequently (every 15 minutes)
Schedule::command('images:reprocess --batch=20')
    ->everyFifteenMinutes()

// Run less frequently (every hour)
Schedule::command('images:reprocess --batch=50')
    ->hourly()

// Run only during off-hours (2 AM - 6 AM)
Schedule::command('images:reprocess --batch=100')
    ->hourly()
    ->between('2:00', '6:00')

// Run only on weekends
Schedule::command('images:reprocess --batch=200')
    ->hourly()
    ->weekends()
```

## 📋 Monitoring

### Check What's Being Processed

```bash
# View the Laravel log
tail -f storage/logs/laravel.log | grep "Reprocessing image"
```

### Check Queue Status

```bash
# View pending jobs
php artisan queue:monitor

# View failed jobs
php artisan queue:failed
```

### Database Query

Check which images still need reprocessing:

```sql
-- Images without face detection
SELECT COUNT(*) FROM image_files 
WHERE processing_status = 'completed' 
AND deleted_at IS NULL 
AND (face_count IS NULL OR face_encodings IS NULL);

-- Images without detailed descriptions
SELECT COUNT(*) FROM image_files 
WHERE processing_status = 'completed' 
AND deleted_at IS NULL 
AND (detailed_description IS NULL OR detailed_description = '');

-- Images without meta tags
SELECT COUNT(*) FROM image_files 
WHERE processing_status = 'completed' 
AND deleted_at IS NULL 
AND (meta_tags IS NULL OR JSON_ARRAY_LENGTH(meta_tags) = 0);
```

## 🔧 Troubleshooting

### Images Not Being Reprocessed

**Check 1: Is the scheduler running?**

```bash
# Check if cron job exists
crontab -l | grep schedule:run

# If not, add it:
crontab -e
# Add: * * * * * cd /path/to/Avinash-EYE && php artisan schedule:run >> /dev/null 2>&1
```

**Check 2: Is the queue worker running?**

```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# If not, start it:
php artisan queue:work
# Or use the start script:
./start-queue-worker.sh
```

**Check 3: Are there jobs in the queue?**

```bash
# Check queue
php artisan queue:monitor
```

**Check 4: Check logs**

```bash
# View Laravel log
tail -f storage/logs/laravel.log

# View scheduler log
php artisan schedule:list
```

### Reprocessing Takes Too Long

**Solution 1: Reduce batch size**

```php
// In routes/console.php
Schedule::command('images:reprocess --batch=10') // Smaller batch
```

**Solution 2: Run less frequently**

```php
Schedule::command('images:reprocess --batch=20')
    ->hourly() // Instead of every 30 minutes
```

**Solution 3: Run during off-hours only**

```php
Schedule::command('images:reprocess --batch=50')
    ->hourly()
    ->between('2:00', '6:00') // 2 AM to 6 AM
```

### AI Service Overloaded

If the AI service is slow or timing out:

**Solution 1: Reduce batch size**

```bash
php artisan images:reprocess --batch=5
```

**Solution 2: Add delays**

The command already includes 100ms delays between jobs. If needed, increase in:
`app/Console/Commands/ReprocessImages.php`:

```php
usleep(500000); // 500ms instead of 100ms
```

**Solution 3: Run queue with less concurrency**

```bash
# Single worker
php artisan queue:work

# Multiple workers (only if you have resources)
php artisan queue:work --queue=default --tries=3
```

## 📈 Performance Tips

### For Small Libraries (< 1000 images)

```php
// Aggressive reprocessing
Schedule::command('images:reprocess --batch=50')
    ->everyFifteenMinutes()
```

### For Medium Libraries (1000-10,000 images)

```php
// Balanced approach (default)
Schedule::command('images:reprocess --batch=20')
    ->everyThirtyMinutes()
```

### For Large Libraries (10,000+ images)

```php
// Conservative approach
Schedule::command('images:reprocess --batch=10')
    ->hourly()
    ->between('2:00', '6:00') // Off-hours only
```

## 🎉 Benefits

### Automatically Improves Over Time

- New images get processed immediately
- Old images get improved gradually
- No manual intervention required

### Always Up-to-Date

- When you enable new features, they're automatically added to existing images
- When models improve, all images benefit
- When settings change, old images get updated

### Resource-Efficient

- Processes in small batches
- Runs during idle time
- Never overlaps or overwhelms the system
- Graceful fallbacks if AI service is busy

### Smart Prioritization

- Most important images first
- Recently uploaded images prioritized
- Images with missing features get attention
- Gradually improves entire library

## 🔍 Example Improvement Journey

**Day 1:** Upload 100 images
- All images get initial processing
- Basic captions, embeddings generated

**Day 2:** Enable Ollama for detailed descriptions
- Next 30-minute cycle: 20 images get detailed descriptions
- 30 minutes later: Another 20 images
- Continues automatically...

**Day 5:** All images now have detailed descriptions
- Scheduler moves to improving older images
- Adds better tags, updates embeddings

**Day 7+:** Continuous maintenance
- Keeps improving as new models/features added
- Your library gets better every day!

## 📝 Command Reference

```bash
# Smart mode (default)
php artisan images:reprocess

# Custom batch size
php artisan images:reprocess --batch=50

# Only missing features
php artisan images:reprocess --only-missing

# Force reprocess all
php artisan images:reprocess --force

# Combine options
php artisan images:reprocess --only-missing --batch=100

# View help
php artisan images:reprocess --help

# View scheduled tasks
php artisan schedule:list

# Run scheduler once (for testing)
php artisan schedule:run
```

## 🚀 Quick Setup Checklist

- [x] ✅ Command created: `images:reprocess`
- [x] ✅ Scheduled task configured: Every 30 minutes
- [ ] 🔲 Add cron job for scheduler
- [ ] 🔲 Ensure queue worker is running
- [ ] 🔲 Test manual reprocessing
- [ ] 🔲 Monitor logs for first few runs
- [ ] 🔲 Adjust batch size/frequency if needed

## 🎯 Next Steps

1. **Enable the scheduler:**
   ```bash
   crontab -e
   # Add: * * * * * cd /path/to/Avinash-EYE && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Start queue worker:**
   ```bash
   ./start-queue-worker.sh
   ```

3. **Test manually:**
   ```bash
   php artisan images:reprocess --batch=5
   ```

4. **Monitor:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Enjoy!** Your images will automatically improve over time! 🎉

---

**Your images are now learning and improving continuously!** 🧠✨

