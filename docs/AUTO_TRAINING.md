# 🤖 Automatic AI Training

## Overview

Your Avinash-EYE installation now features **automatic AI training** that continuously learns from your images! The system automatically:

1. ✅ **Detects training data** on container start
2. ✅ **Trains models in background** without blocking service
3. ✅ **Applies learned patterns** to new images immediately
4. ✅ **Retrains automatically** when new data is exported

## 🎯 How It Works

### On Container Start

```
┌─────────────────────────────────────────┐
│  1. Python AI Container Starts          │
│     - Checks for training data          │
│     - Loads base AI models (BLIP, CLIP) │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  2. Training Data Check                 │
│     - Found? → Schedule training        │
│     - Not found? → Use base models      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  3. FastAPI Service Starts              │
│     - Immediately available             │
│     - Processes images with base models │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  4. Background Training (60s delay)     │
│     - Analyzes patterns                 │
│     - Clusters faces                    │
│     - Builds search index               │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  5. Learned Patterns Applied            │
│     - Enhanced descriptions             │
│     - Better categorization             │
│     - Improved search                   │
└─────────────────────────────────────────┘
```

## 🚀 Quick Start

### First Time Setup

```bash
# 1. Upload some images (at least 50-100)
# Go to: http://localhost:8080/upload

# 2. Export training data
php artisan export:training-data

# 3. Restart Python AI (training starts automatically!)
docker compose restart python-ai

# 4. Check logs to see training progress
docker compose logs -f python-ai
```

### Automatic Workflow

Once set up, the system is **completely automatic**:

1. **New images uploaded** → Processed with current knowledge
2. **More images added** → Export training data periodically
3. **Container restart** → Auto-trains if new data exists
4. **Models improve** → All new images benefit

## 📊 Training Triggers

Training happens automatically when:

### On Container Start
- ✅ Training data exists
- ✅ No trained models found
- ✅ Training data is newer than trained models

### Manual Trigger
```bash
# Export new training data
php artisan export:training-data

# Restart container (triggers auto-training)
docker compose restart python-ai
```

### Scheduled (Optional)
Add to cron for weekly retraining:
```bash
# Weekly: Sunday at 2 AM
0 2 * * 0 cd /path/to/Avinash-EYE && php artisan export:training-data && docker compose restart python-ai
```

## 🔍 Monitoring Training

### Check Training Status

```bash
# View live training logs
docker compose logs -f python-ai

# Check if training is running
docker compose exec python-ai ps aux | grep train_model

# View training history
docker compose exec python-ai cat /app/training_data/training_history.log
```

### Training Log Location

```bash
# Main training log
docker compose exec python-ai tail -f /app/training_data/training.log

# Export history
docker compose exec python-ai cat /app/training_data/export_timestamp.txt
```

### Verify Training Completed

```bash
# Check for trained model files
docker compose exec python-ai ls -la /app/training_data/

# Should see:
# - category_patterns.json
# - description_patterns.json
# - face_clusters.pkl
# - search_index.json
# - training_report.json
```

## 📈 What Gets Learned

### Category Patterns
```json
{
  "landscape": {
    "count": 245,
    "common_words": ["mountain", "sky", "scenic", "nature"],
    "co_occurring_tags": ["outdoor", "nature", "scenic"]
  }
}
```

### Description Improvements
```
Before:  "a woman wearing red dress"
After:   "a woman wearing red dress, commonly associated with fashion, portrait, formal"
```

### Face Clustering
```
Person 1: 45 images
Person 2: 32 images
Person 3: 28 images
→ Enables "People" collections
```

### Search Synonyms
```
"saree" → finds "sari", "traditional", "dress"
"car" → finds "automobile", "vehicle"
"happy" → finds "smiling", "joyful"
```

## ⚙️ Configuration

### Environment Variables

Set in `docker-compose.yml`:

```yaml
environment:
  - AUTO_TRAIN=true          # Enable auto-training
  - OLLAMA_HOST=http://ollama:11434
  - TRANSFORMERS_CACHE=/root/.cache/huggingface
```

### Disable Auto-Training

```yaml
environment:
  - AUTO_TRAIN=false  # Disable auto-training
```

Then restart:
```bash
docker compose up -d python-ai
```

### Training Delay

Edit `python-ai/startup.sh`:

```bash
# Change delay before training starts
sleep 60  # Default: 60 seconds
sleep 300 # 5 minutes for larger libraries
```

## 🎛️ Advanced Usage

### Force Retraining

```bash
# Delete existing trained models
docker compose exec python-ai rm -f /app/training_data/*.json /app/training_data/*.pkl

# Restart (will retrain from scratch)
docker compose restart python-ai
```

### Train Without Restart

```bash
# Export data
php artisan export:training-data

# Run training manually
docker compose exec python-ai python /app/train_model.py

# No restart needed! Changes apply immediately
```

### Check Training Performance

```bash
# View training report
docker compose exec python-ai cat /app/training_data/training_report.json | jq

# Example output:
# {
#   "total_images": 2450,
#   "total_tags": 187,
#   "images_with_faces": 892,
#   "top_categories": ["landscape", "portrait", "food"]
# }
```

## 🔄 Persistent Training Data

Training data is stored in a **persistent Docker volume**:

```yaml
volumes:
  - training-data:/app/training_data:rw
```

### Benefits:
- ✅ Survives container restarts
- ✅ Survives container rebuilds  
- ✅ Faster subsequent starts
- ✅ No data loss

### Backup Training Data

```bash
# Backup
docker run --rm -v avinash-eye_training-data:/data -v $(pwd):/backup \
  alpine tar czf /backup/training-backup.tar.gz /data

# Restore
docker run --rm -v avinash-eye_training-data:/data -v $(pwd):/backup \
  alpine tar xzf /backup/training-backup.tar.gz -C /
```

## 📊 Training Timeline

### Small Library (< 500 images)

```
00:00 - Container starts
00:01 - FastAPI service ready
00:02 - Training starts (background)
00:05 - Training completes
00:06 - Enhanced analysis available
```

### Medium Library (500-2000 images)

```
00:00 - Container starts
00:01 - FastAPI service ready
00:02 - Training starts (background)
00:10 - Training completes
00:11 - Enhanced analysis available
```

### Large Library (2000+ images)

```
00:00 - Container starts
00:01 - FastAPI service ready
00:02 - Training starts (background)
00:20 - Training completes
00:21 - Enhanced analysis available
```

**Note:** Service is immediately available! Training happens in background.

## 🐛 Troubleshooting

### Training Not Starting

**Check logs:**
```bash
docker compose logs python-ai | grep -i training
```

**Possible causes:**
1. No training data exported
   ```bash
   # Solution: Export data
   php artisan export:training-data
   ```

2. Training already completed
   ```bash
   # Check for trained models
   docker compose exec python-ai ls /app/training_data/*.json
   ```

3. AUTO_TRAIN disabled
   ```bash
   # Check environment
   docker compose exec python-ai env | grep AUTO_TRAIN
   ```

### Training Takes Too Long

**Reduce training data:**
```bash
# Export fewer images
php artisan export:training-data --limit=500
```

**Check resources:**
```bash
# Increase Docker memory
# Docker Desktop → Settings → Resources → Memory: 8GB+
```

### Training Failed

**View error log:**
```bash
docker compose exec python-ai cat /app/training_data/training.log
```

**Common issues:**
1. **Out of memory** → Reduce batch size or increase Docker memory
2. **Missing dependencies** → Rebuild container
3. **Corrupted data** → Re-export training data

**Solution:**
```bash
# Clear and retry
docker compose exec python-ai rm -rf /app/training_data/*
php artisan export:training-data
docker compose restart python-ai
```

### No Improvements Visible

**Verify training completed:**
```bash
docker compose exec python-ai cat /app/training_data/training_report.json
```

**Check if enhanced analysis is loaded:**
```bash
docker compose logs python-ai | grep "Enhanced analysis"
# Should see: "Enhanced analysis module loaded"
```

**Reprocess existing images:**
```bash
php artisan images:reprocess --batch=20
```

## 💡 Best Practices

### 1. Regular Exports

Export training data periodically:

```bash
# Weekly
php artisan export:training-data

# After bulk uploads
php artisan export:training-data --limit=5000
```

### 2. Gradual Training

Start small, grow over time:

```bash
# Week 1: 100 images
php artisan export:training-data --limit=100

# Week 2: 500 images
php artisan export:training-data --limit=500

# Week 3: All images
php artisan export:training-data --limit=10000
```

### 3. Monitor Performance

Track improvements:

```bash
# Before training
# Note: Description quality, tag accuracy

# After training
# Compare: Are descriptions better?
# Check: Are tags more relevant?
```

### 4. Scheduled Retraining

Automate with cron:

```bash
# Add to crontab -e:
0 2 * * 0 cd /path/to/Avinash-EYE && php artisan export:training-data && docker compose restart python-ai >> /var/log/ai-training.log 2>&1
```

## 🎯 Success Indicators

### Training is Working If:

✅ **Startup logs show:**
```
✓ Training data found
🔄 Will retrain in background...
✓ Training scheduled in background
```

✅ **Service logs show:**
```
Enhanced analysis module loaded - using learned patterns
Applied learned patterns to improve analysis
```

✅ **New images have:**
- More detailed descriptions
- Better categorization
- Relevant tag suggestions
- Improved search results

## 📚 Related Documentation

- **Training Guide:** `AI_TRAINING_GUIDE.md`
- **Enhanced Analysis:** `python-ai/enhanced_analysis.py`
- **Training Script:** `python-ai/train_model.py`
- **Startup Script:** `python-ai/startup.sh`

---

## 🎊 Summary

Your AI now **trains automatically**:

1. 🚀 **Zero config** - Just works on container start
2. 🔄 **Continuous learning** - Improves over time
3. ⚡ **No downtime** - Service available immediately
4. 💾 **Persistent** - Training survives restarts
5. 📈 **Better results** - Personalized to your images

**Just upload images, export data, and watch it learn!** 🧠✨


