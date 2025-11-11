# 🚀 Production Deployment Guide

## Overview

This guide covers deploying Avinash-EYE to production with automatic setup, model pulling, and monitoring.

---

## ✨ Production Features

The production-ready docker-compose.yml includes:

### 🔄 Automatic Setup
- ✅ **Auto-migration**: Database migrations run automatically
- ✅ **Auto-seeding**: Default settings configured automatically
- ✅ **Auto-optimization**: Production cache enabled automatically
- ✅ **Auto-permissions**: Storage permissions set automatically

### 🤖 Automatic Model Management
- ✅ **Ollama models**: LLaVA pulled automatically in background
- ✅ **HuggingFace models**: BLIP, CLIP downloaded on first use
- ✅ **Model caching**: Models cached in Docker volumes
- ✅ **Non-blocking**: Models download in background

### 🔧 Production Services
- ✅ **Queue Worker**: Dedicated service for background jobs
- ✅ **Health Checks**: All services monitored
- ✅ **Auto-restart**: Services restart on failure
- ✅ **Resource Limits**: Memory and CPU limits configured
- ✅ **Logging**: JSON logs with rotation

### 🛡️ Security Features
- ✅ **Environment variables**: All secrets in .env
- ✅ **Read-only mounts**: Nginx uses read-only volumes
- ✅ **Isolated network**: Services in private network
- ✅ **Production mode**: Debug disabled by default

---

## 📦 Quick Start (Production)

### 1. One-Command Deployment

```bash
# Clone or navigate to project
cd /path/to/Avinash-EYE

# Run production startup script
./start-production.sh
```

That's it! The script will:
1. ✅ Check prerequisites (Docker, memory, disk space)
2. ✅ Create .env from .env.production if needed
3. ✅ Build all containers
4. ✅ Start all services
5. ✅ Wait for services to be healthy
6. ✅ Generate APP_KEY automatically
7. ✅ Pull Ollama models in background
8. ✅ Show service status and logs

### 2. Access Your Application

After 2-3 minutes:
```
🌐 http://localhost:8080
```

**Note**: First run takes 10-15 minutes to download AI models (~5GB).
Subsequent starts are under 2 minutes.

---

## 🔧 Manual Deployment

If you prefer manual control:

### Step 1: Prepare Environment

```bash
# Copy production config
cp .env.production .env

# Edit and update secrets
nano .env
```

**Important**: Update these values:
- `DB_PASSWORD` - Change from default
- `APP_KEY` - Will be generated automatically

### Step 2: Build and Start

```bash
# Build containers
docker compose build

# Start all services
docker compose up -d
```

### Step 3: Wait for Services

```bash
# Check service status
docker compose ps

# Wait for all services to be healthy
watch docker compose ps
```

### Step 4: Verify Setup

```bash
# Check Laravel
docker compose exec laravel-app php artisan about

# Check AI service
curl http://localhost:8000/health

# Check Ollama models
docker compose exec ollama ollama list

# Check queue worker
docker compose logs queue-worker
```

---

## 🏗️ What Happens on Startup

### Service Startup Order

```
1. Database (PostgreSQL)
   ├─ Creates database
   ├─ Enables pgvector extension
   └─ Ready in ~10 seconds

2. Ollama
   ├─ Starts service
   ├─ Pulls LLaVA model (background, ~5-10 minutes)
   └─ Ready in ~30 seconds (usable immediately)

3. Python AI
   ├─ Downloads BLIP model (background, ~2GB)
   ├─ Downloads CLIP model (background, ~400MB)
   ├─ Loads models into memory
   ├─ Auto-trains if training data exists
   └─ Ready in ~2-5 minutes

4. Laravel Application
   ├─ Waits for database
   ├─ Runs migrations (auto)
   ├─ Seeds settings (auto)
   ├─ Creates storage link (auto)
   ├─ Caches config/routes/views (auto)
   └─ Ready in ~30 seconds

5. Queue Worker
   ├─ Waits for Laravel + Python AI
   ├─ Starts processing queue
   └─ Ready in ~10 seconds

6. Nginx
   ├─ Waits for Laravel
   ├─ Configures reverse proxy
   └─ Ready in ~5 seconds
```

### First Run vs Subsequent Runs

| Metric | First Run | Subsequent Runs |
|--------|-----------|-----------------|
| **Total Time** | 10-15 minutes | 1-2 minutes |
| **Model Downloads** | ~5GB | None (cached) |
| **Database Setup** | Migrations run | Already ready |
| **Laravel Init** | Full setup | Optimized cache |
| **Queue Worker** | Starts fresh | Resumes queue |

---

## 📊 Service Configuration

### Resource Limits

Default production limits:

| Service | Memory Limit | Memory Reserved | Purpose |
|---------|--------------|-----------------|---------|
| **Database** | 1GB | 512MB | PostgreSQL + pgvector |
| **Ollama** | 8GB | 4GB | LLM processing |
| **Python AI** | 8GB | 4GB | Image analysis models |
| **Laravel** | 2GB | 512MB | PHP application |
| **Queue Worker** | 1GB | 256MB | Background jobs |
| **Nginx** | 256MB | 64MB | Web server |

**Total Required**: Minimum 8GB RAM, 16GB recommended

### Adjust for Your Hardware

Edit `docker-compose.yml` to adjust limits:

```yaml
deploy:
  resources:
    limits:
      memory: 4G  # Reduce if needed
    reservations:
      memory: 2G  # Minimum required
```

---

## 🔄 Service Management

### Start/Stop Services

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Restart specific service
docker compose restart python-ai

# Restart all
docker compose restart
```

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f python-ai
docker compose logs -f queue-worker
docker compose logs -f laravel-app

# Last 100 lines
docker compose logs --tail=100

# Since specific time
docker compose logs --since 10m
```

### Monitor Resources

```bash
# Real-time stats
docker stats

# Service status
docker compose ps

# Detailed info
docker compose ps --all
```

---

## 🛠️ Maintenance Commands

### Database

```bash
# Run migrations
docker compose exec laravel-app php artisan migrate

# Create backup
docker exec avinash-eye-db pg_dump -U avinash avinash_eye > backup.sql

# Restore backup
docker exec -i avinash-eye-db psql -U avinash avinash_eye < backup.sql

# Check database size
docker compose exec db psql -U avinash -d avinash_eye -c "SELECT pg_size_pretty(pg_database_size('avinash_eye'));"
```

### Queue Management

```bash
# Check queue status
docker compose exec laravel-app php artisan queue:monitor

# Restart queue worker
docker compose restart queue-worker

# View failed jobs
docker compose exec laravel-app php artisan queue:failed

# Retry failed jobs
docker compose exec laravel-app php artisan queue:retry all

# Clear queue
docker compose exec laravel-app php artisan queue:flush
```

### Cache Management

```bash
# Clear all cache
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan route:clear
docker compose exec laravel-app php artisan view:clear

# Rebuild cache (production)
docker compose exec laravel-app php artisan config:cache
docker compose exec laravel-app php artisan route:cache
docker compose exec laravel-app php artisan view:cache
```

### AI Models

```bash
# Check Ollama models
docker compose exec ollama ollama list

# Pull additional model
docker compose exec ollama ollama pull llama2

# Remove model
docker compose exec ollama ollama rm llama2

# Check Python AI models
docker compose exec python-ai ls -lh /root/.cache/huggingface

# Clear model cache (will redownload)
docker volume rm avinash-eye_model-cache
```

---

## 🔍 Troubleshooting

### Services Won't Start

```bash
# Check service status
docker compose ps

# Check logs for errors
docker compose logs

# Restart everything
docker compose down
docker compose up -d
```

### Database Connection Issues

```bash
# Check database health
docker compose exec db pg_isready -U avinash

# Test connection from Laravel
docker compose exec laravel-app php artisan db:show

# Check credentials in .env
docker compose exec laravel-app env | grep DB_
```

### Queue Jobs Not Processing

```bash
# Check queue worker is running
docker compose ps queue-worker

# Check queue worker logs
docker compose logs -f queue-worker

# Restart queue worker
docker compose restart queue-worker

# Check for failed jobs
docker compose exec laravel-app php artisan queue:failed
```

### AI Models Not Loading

```bash
# Check Python AI logs
docker compose logs python-ai | grep -i error

# Check model cache
docker compose exec python-ai ls /root/.cache/huggingface

# Restart Python AI
docker compose restart python-ai

# Clear and redownload models
docker compose down
docker volume rm avinash-eye_model-cache
docker compose up -d python-ai
```

### Out of Memory

```bash
# Check memory usage
docker stats --no-stream

# Reduce memory limits in docker-compose.yml
# Or increase Docker Desktop memory allocation

# Disable Ollama if not needed (saves 4-8GB)
docker compose stop ollama
```

### Slow Performance

```bash
# Check resource usage
docker stats

# Optimize database
docker compose exec laravel-app php artisan queue:prune-batches
docker compose exec db vacuumdb -U avinash -d avinash_eye

# Clear old logs
docker compose exec laravel-app find storage/logs -name "*.log" -mtime +7 -delete

# Restart services
docker compose restart
```

---

## 📈 Monitoring & Health Checks

### Health Check Endpoints

```bash
# Laravel application
curl http://localhost:8080

# Python AI service
curl http://localhost:8000/health

# Ollama service
curl http://localhost:11434/api/tags

# Database
docker compose exec db pg_isready -U avinash
```

### Service Health Status

```bash
# All services
docker compose ps

# Detailed health
docker inspect avinash-eye-laravel | grep -A 10 "Health"
docker inspect avinash-eye-python-ai | grep -A 10 "Health"
```

### Log Rotation

Logs are automatically rotated:
- **Max size**: 10MB per log file
- **Max files**: 3 files retained
- **Total**: ~30MB per service

Manual cleanup:
```bash
# Clear all logs
docker compose down
rm -rf $(docker inspect --format='{{.LogPath}}' $(docker compose ps -q))
docker compose up -d
```

---

## 🔐 Security Hardening

### Production Security Checklist

- [ ] Change `DB_PASSWORD` from default
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong `APP_KEY` (auto-generated)
- [ ] Enable HTTPS with SSL certificates
- [ ] Set up firewall rules
- [ ] Use reverse proxy with rate limiting
- [ ] Implement backup strategy
- [ ] Regular security updates
- [ ] Monitor logs for suspicious activity
- [ ] Restrict database access
- [ ] Use Docker secrets for sensitive data

### SSL/HTTPS Setup

For production with HTTPS:

1. **Get SSL Certificate**:
   ```bash
   # Using Let's Encrypt
   certbot certonly --standalone -d yourdomain.com
   ```

2. **Update nginx config**:
   ```nginx
   server {
       listen 443 ssl http2;
       ssl_certificate /path/to/cert.pem;
       ssl_certificate_key /path/to/key.pem;
       # ... rest of config
   }
   ```

3. **Update docker-compose.yml**:
   ```yaml
   nginx:
     ports:
       - "443:443"
     volumes:
       - /path/to/certs:/etc/ssl/certs:ro
   ```

---

## 💾 Backup Strategy

### Automated Backup Script

Create `backup.sh`:

```bash
#!/bin/bash
# Daily backup script

BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
docker exec avinash-eye-db pg_dump -U avinash avinash_eye | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup images
tar -czf "$BACKUP_DIR/images_$DATE.tar.gz" storage/app/public/images/

# Keep only last 7 days
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Add to cron:
```bash
# Run daily at 2 AM
0 2 * * * /path/to/backup.sh
```

---

## 🎯 Performance Optimization

### For Large Image Collections (10,000+)

1. **Add Redis** for caching:
   ```yaml
   redis:
     image: redis:alpine
     ports:
       - "6379:6379"
   ```

2. **Update .env**:
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   ```

3. **Increase resources**:
   - Laravel: 4GB memory
   - Queue workers: 2 workers, 2GB each
   - Database: 2GB memory

4. **Optimize database**:
   ```sql
   -- Increase pgvector index lists
   ALTER INDEX image_files_embedding_idx
   SET (lists = 200);
   ```

---

## 📞 Support

### Getting Help

1. Check logs: `docker compose logs -f`
2. Check documentation: `docs/README.md`
3. Check troubleshooting section above
4. Open GitHub issue with:
   - Docker version
   - System specs
   - Error logs
   - Steps to reproduce

---

## 🎉 Success!

Your production deployment is complete! The system will:

✅ **Automatically start** on system reboot  
✅ **Pull models** in background  
✅ **Process images** via queue worker  
✅ **Monitor health** and restart on failure  
✅ **Log everything** with rotation  
✅ **Scale automatically** based on load  

Enjoy your production-ready AI image management system! 🚀

