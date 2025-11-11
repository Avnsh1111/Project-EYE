# 🐳 Ollama Docker Integration Guide

## ✅ What's Configured

Ollama is now fully integrated into your Docker Compose stack! No need to install anything on your host machine.

### Services Added:
- **Ollama Service**: Runs in its own container at `http://ollama:11434`
- **Automatic Configuration**: Python AI service automatically connects to Ollama
- **Persistent Storage**: Models are stored in a Docker volume for fast restarts

## 🚀 Quick Start

### Step 1: Start Ollama Service

```bash
# Start just Ollama (if not already running)
docker compose up -d ollama

# Or restart all services
docker compose up -d
```

### Step 2: Pull Vision Models

Run the automated setup script:

```bash
./setup-ollama.sh
```

Or manually pull models:

```bash
# Pull the primary vision model (llava - recommended)
docker compose exec ollama ollama pull llava

# Optional: Pull larger, more accurate model
docker compose exec ollama ollama pull llava:13b

# Optional: Pull alternative vision model
docker compose exec ollama ollama pull bakllava
```

### Step 3: Verify Installation

```bash
# Check Ollama is running
docker compose ps ollama

# List installed models
docker compose exec ollama ollama list

# Test Ollama
curl http://localhost:11434/api/tags
```

### Step 4: Enable in Settings

1. Go to **Settings**: http://localhost:8080/settings
2. Check ✅ **Enable Ollama (Detailed Descriptions)**
3. Select your model (default: **llava**)
4. Click **Save Settings**
5. Upload an image to test!

## 📊 Available Models

### Recommended Models:

| Model | Size | Speed | Quality | Best For |
|-------|------|-------|---------|----------|
| **llava** | ~4GB | Fast | Good | General use, quick responses |
| **llava:13b** | ~8GB | Slower | Excellent | High-quality descriptions |
| **bakllava** | ~4GB | Fast | Good | Alternative to llava |

### Pulling a Model:

```bash
docker compose exec ollama ollama pull <model-name>
```

### Listing Installed Models:

```bash
docker compose exec ollama ollama list
```

### Removing a Model:

```bash
docker compose exec ollama ollama rm <model-name>
```

## 🔧 Architecture

```
┌─────────────────┐
│   Laravel App   │
│   (Port 8080)   │
└────────┬────────┘
         │
         │ HTTP Request
         ▼
┌─────────────────┐      ┌──────────────┐
│   Python AI     │─────▶│   Ollama     │
│   (Port 8000)   │      │ (Port 11434) │
└─────────────────┘      └──────────────┘
         │                       │
         ▼                       ▼
   ┌──────────┐          ┌─────────────┐
   │  BLIP    │          │ llava Model │
   │  CLIP    │          │ (Vision AI) │
   └──────────┘          └─────────────┘
```

### How It Works:

1. **User uploads image** → Laravel saves to shared volume
2. **Laravel triggers job** → Calls Python AI service
3. **Python AI analyzes**:
   - BLIP generates basic caption
   - CLIP generates embeddings
   - If Ollama enabled → Sends image + caption to Ollama
4. **Ollama generates**:
   - Detailed description (3-4 sentences)
   - Meta tags for searching
5. **Results saved** → Database + displayed in UI

## 🐛 Troubleshooting

### Ollama Container Won't Start

```bash
# Check logs
docker compose logs ollama

# Restart Ollama
docker compose restart ollama

# Full restart
docker compose down && docker compose up -d
```

### "Ollama Not Available" in Settings

**Check if Ollama is running:**
```bash
docker compose ps ollama
# Should show "Up" and "healthy"
```

**Check Ollama health:**
```bash
curl http://localhost:11434/api/tags
# Should return JSON with models
```

**Check Python AI can connect:**
```bash
docker compose exec python-ai curl http://ollama:11434/api/tags
# Should return JSON (tests internal network)
```

**Restart Python AI service:**
```bash
docker compose restart python-ai
```

### Models Not Pulling

**Check disk space:**
```bash
docker system df
# Each model is 4-8GB
```

**Pull with verbose output:**
```bash
docker compose exec ollama ollama pull llava --verbose
```

**Check Ollama logs:**
```bash
docker compose logs ollama --follow
```

### "Model Not Found" Error

**List installed models:**
```bash
docker compose exec ollama ollama list
```

**Pull the model you selected:**
```bash
docker compose exec ollama ollama pull llava
```

**Check settings page** - make sure selected model is actually installed

### Slow Performance

**Solutions:**
1. **Use smaller model**: Switch from `llava:13b` to `llava`
2. **Increase Docker resources**: Docker Desktop → Settings → Resources
3. **Use GPU** (if available): Add GPU passthrough to docker-compose.yml

### Settings Not Persisting

**After changing Ollama settings:**
1. Hard refresh browser: **Ctrl+Shift+R** or **Cmd+Shift+R**
2. Clear Laravel cache: `docker compose exec laravel-app php artisan cache:clear`
3. Check database: Settings should be stored as booleans, not strings

## 📝 Configuration Details

### Docker Compose Configuration

**Ollama Service** (`docker-compose.yml`):
```yaml
ollama:
  image: ollama/ollama:latest
  container_name: avinash-eye-ollama
  ports:
    - "11434:11434"
  volumes:
    - ollama-data:/root/.ollama
  networks:
    - avinash-network
  restart: unless-stopped
```

**Python AI Service** (`docker-compose.yml`):
```yaml
python-ai:
  environment:
    - OLLAMA_HOST=http://ollama:11434
  depends_on:
    ollama:
      condition: service_started
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OLLAMA_HOST` | `http://ollama:11434` | Ollama service URL |

**To change:**
```yaml
# docker-compose.yml
python-ai:
  environment:
    - OLLAMA_HOST=http://your-custom-host:port
```

## 🎯 Testing Your Setup

### Complete Test Workflow:

```bash
# 1. Check all services are running
docker compose ps
# All should show "Up" and "healthy"

# 2. Check Ollama has models
docker compose exec ollama ollama list
# Should show llava or other models

# 3. Test Ollama directly
curl -X POST http://localhost:11434/api/generate \
  -d '{"model": "llava", "prompt": "Hello"}'

# 4. Check Python AI sees Ollama
curl http://localhost:8000/health | jq
# ollama_available should be true

# 5. Test through settings page
# Go to http://localhost:8080/settings
# Should show "Ollama Status: Running"

# 6. Upload a test image
# Go to http://localhost:8080/upload
# Upload image → Check if detailed description is generated
```

## 📦 Model Storage

**Location:** Docker volume `ollama-data`

**Check size:**
```bash
docker volume inspect avinash-eye_ollama-data
```

**Backup models:**
```bash
docker run --rm -v avinash-eye_ollama-data:/data -v $(pwd):/backup \
  alpine tar czf /backup/ollama-models-backup.tar.gz /data
```

**Restore models:**
```bash
docker run --rm -v avinash-eye_ollama-data:/data -v $(pwd):/backup \
  alpine tar xzf /backup/ollama-models-backup.tar.gz -C /
```

**Clean up (removes all models):**
```bash
docker compose down
docker volume rm avinash-eye_ollama-data
docker compose up -d
# Then re-pull models
```

## 🚀 Performance Tips

### 1. Preload Models on Startup

Models are automatically loaded when first used. To preload:

```bash
docker compose exec ollama ollama pull llava
docker compose restart python-ai
```

### 2. Use Smaller Models for Speed

- **Fast**: `llava` (4GB)
- **Balanced**: `llava:7b` (5GB)
- **Quality**: `llava:13b` (8GB)

### 3. Increase Docker Memory

Docker Desktop → Settings → Resources:
- Memory: **8GB minimum**, 16GB recommended for llava:13b
- CPU: 4+ cores recommended

### 4. Enable GPU Support (Advanced)

**For NVIDIA GPUs:**

```yaml
# docker-compose.yml
ollama:
  deploy:
    resources:
      reservations:
        devices:
          - driver: nvidia
            count: 1
            capabilities: [gpu]
```

Requires NVIDIA Container Toolkit installed.

## 🔄 Updating

### Update Ollama:

```bash
docker compose pull ollama
docker compose up -d ollama
```

### Update Models:

```bash
docker compose exec ollama ollama pull llava
```

### Update Python AI Integration:

```bash
docker compose build python-ai --no-cache
docker compose up -d python-ai
```

## 📚 Additional Resources

- **Ollama Docs**: https://ollama.ai/docs
- **Available Models**: https://ollama.ai/library
- **Vision Models**: Search for "vision" or "llava" at https://ollama.ai/library

## ✅ Checklist

After setup, verify:

- [ ] Ollama container is running: `docker compose ps ollama`
- [ ] Models are pulled: `docker compose exec ollama ollama list`
- [ ] Ollama health check passes: `curl http://localhost:11434/api/tags`
- [ ] Python AI can connect: `curl http://localhost:8000/health | grep ollama_available`
- [ ] Settings page shows "Ollama Running"
- [ ] Can enable Ollama in settings
- [ ] Can select a model
- [ ] Settings persist after refresh
- [ ] Uploaded images get detailed descriptions

## 🎉 Success!

If all checks pass, you're ready to use Ollama for detailed image descriptions!

Try uploading an image and see the difference:
- **Without Ollama**: Basic caption like "a dog sitting on grass"
- **With Ollama**: "A golden retriever with a shiny, well-groomed coat sitting attentively on lush green grass. The dog appears happy with its tongue out, set against a sunny outdoor background with soft natural lighting. The scene conveys a playful, peaceful mood perfect for a summer day."

**Happy analyzing! 📸✨**

