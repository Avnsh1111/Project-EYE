# 🚀 Quick Start Guide

Get Avinash-EYE up and running in 5 minutes!

## Option 1: Automated Setup (Recommended)

Simply run the setup script:

```bash
./setup.sh
```

This will:
- ✅ Create .env file
- ✅ Create storage directories
- ✅ Build Docker containers
- ✅ Generate application key
- ✅ Run migrations
- ✅ Create storage symlink
- ✅ Verify AI service

Then open: **http://localhost:8080**

## Option 2: Manual Setup

### Step 1: Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Create storage directories
mkdir -p storage/app/public/images
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Step 2: Start Services

```bash
# Build and start all containers (first run takes 10-15 minutes)
docker-compose up -d --build

# Watch the logs (optional)
docker-compose logs -f
```

### Step 3: Initialize Application

```bash
# Generate app key
docker-compose exec laravel-app php artisan key:generate

# Run migrations
docker-compose exec laravel-app php artisan migrate

# Create storage symlink
docker-compose exec laravel-app php artisan storage:link
```

### Step 4: Access the Application

Open your browser: **http://localhost:8080**

## Verify Installation

### Check Service Health

```bash
# Check all services
docker-compose ps

# Test AI service
curl http://localhost:8000/health

# Test web application
curl http://localhost:8080
```

You should see all services as "Up" or "healthy".

## First Steps

1. **Upload Images** 
   - Go to http://localhost:8080/upload
   - Upload 5-10 images
   - Wait for AI analysis to complete

2. **Search Images**
   - Go to http://localhost:8080/search
   - Try queries like:
     - "person wearing glasses"
     - "outdoor scene"
     - "blue car"
     - "sunset"

3. **Enjoy!** 🎉

## Common Issues

### Issue: AI service takes too long to start
**Solution**: The first run downloads ~2GB of AI models. Check progress:
```bash
docker-compose logs -f python-ai
```

### Issue: Permission errors
**Solution**: Fix storage permissions:
```bash
chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:$USER storage bootstrap/cache
```

### Issue: Port 8080 already in use
**Solution**: Change the port in `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8081:80"  # Change 8080 to 8081
```

### Issue: Database connection failed
**Solution**: Wait 30 seconds for PostgreSQL to initialize, then retry:
```bash
docker-compose restart laravel-app
```

## Stopping the System

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (complete reset)
docker-compose down -v
```

## Restarting After Stop

```bash
# Just start services (much faster than first time)
docker-compose up -d

# Check status
docker-compose ps
```

## Need Help?

- Check the full [README.md](README.md) for detailed documentation
- View logs: `docker-compose logs -f [service-name]`
- Restart a service: `docker-compose restart [service-name]`

---

**Happy Searching! 🔍✨**

