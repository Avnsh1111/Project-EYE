# 🚀 Avinash-EYE Quick Start v3.0

## 🎯 One-Time Setup (5 minutes)

```bash
# 1. Start all services
docker compose up -d

# 2. Setup Ollama and pull AI model
./setup-ollama.sh

# 3. Start background workers
./start-queue-worker.sh        # Terminal 1
./start-scheduler.sh           # Terminal 2

# 4. Open settings and enable features
# Go to: http://localhost:8080/settings
# ✅ Enable Ollama
# ✅ Enable Face Detection
# 💾 Save Settings

# 5. Upload some images (50+ recommended)
# Go to: http://localhost:8080/upload

# 6. Export data and train AI (automatic!)
php artisan export:training-data
docker compose restart python-ai  # Auto-trains in background!

# 7. (Optional) Reprocess existing images
php artisan images:reprocess --only-missing --batch=50
```

**Done! AI now learns from YOUR images automatically!** 🎉🧠

---

## 📸 Daily Usage

### Upload Images
1. Go to http://localhost:8080/upload
2. Drag & drop images
3. AI automatically:
   - Generates descriptions
   - Detects faces
   - Creates embeddings
   - Categorizes content

### Search Images (AI-Powered!)
- **"saree"** → Finds "sari" too!
- **"sunset"** → Finds "dusk", "evening"
- **"happy"** → Finds "smiling", "joyful"
- **"car"** → Finds "automobile", "vehicle"

### Browse Collections
- Go to http://localhost:8080/collections
- Auto-categorized by AI tags
- Face-based collections
- Click to filter gallery

---

## 🤖 What Runs Automatically

### On Container Start
- **Auto-Training** (if training data exists)
  - Learns from your images
  - Improves descriptions
  - Better categorization
  - Enhanced face recognition
  - *Runs in background, doesn't block service*

### Every 30 Minutes
- **Background Reprocessing**
  - Improves old images
  - Adds missing features
  - Updates with latest AI

### On Image Upload
- **Instant Processing**
  - AI description (BLIP)
  - Detailed description (Ollama)
  - Face detection
  - Semantic embeddings
  - Auto-categorization
  - **Applies learned patterns from YOUR images!**

---

## 🔧 Quick Commands

```bash
# Export training data and train AI
php artisan export:training-data
docker compose restart python-ai  # Auto-trains!

# View what's scheduled
php artisan schedule:list

# Manual reprocessing
php artisan images:reprocess

# Check services & training status
docker compose ps
docker compose logs python-ai | grep -i training

# View logs
docker compose logs python-ai --follow
tail -f storage/logs/laravel.log

# Restart a service
docker compose restart python-ai
```

---

## 📊 Ports & URLs

| Service | URL | Purpose |
|---------|-----|---------|
| **Web App** | http://localhost:8080 | Main application |
| **Python AI** | http://localhost:8000 | AI processing |
| **Ollama** | http://localhost:11434 | AI descriptions |
| **Database** | localhost:5432 | PostgreSQL |

---

## 🆘 Troubleshooting

### Settings Not Persisting
```bash
php artisan cache:clear
# Refresh page and re-save settings
```

### AI Service Offline
```bash
docker compose restart python-ai
# Wait 30 seconds for models to load
```

### Ollama Not Working
```bash
# Check if running
docker compose ps ollama

# Pull model if needed
docker compose exec ollama ollama pull llava

# Restart
docker compose restart ollama python-ai
```

### Queue Not Processing
```bash
# Check if running
ps aux | grep "queue:work"

# Restart if needed
./start-queue-worker.sh
```

---

## 📚 Documentation

- **Auto-Training**: `AUTO_TRAINING.md` ⭐ NEW!
- **AI Training Guide**: `AI_TRAINING_GUIDE.md`
- **Complete Setup**: `DOCKER_OLLAMA_SETUP.md`
- **Reprocessing**: `INTELLIGENT_REPROCESSING.md`
- **All Features**: `LATEST_IMPROVEMENTS.md`
- **Full Guide**: `README.md`

---

## ✨ Pro Tips

1. **Let it run!** The scheduler improves images automatically
2. **Enable Ollama** for amazing detailed descriptions
3. **Face detection** enables people-based collections
4. **Search semantically** - type what you mean, not exact words
5. **Collections page** shows AI-organized categories

---

**That's it! Your AI-powered gallery is ready!** 🎊📸

*Questions? Check the docs or logs above!*

