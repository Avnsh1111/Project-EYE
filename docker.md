# Docker Development Guide

Complete guide for developing with Docker in the Avinash-EYE project.

---

## 📋 Table of Contents

- [Overview](#overview)
- [File Changes & Live Reload](#file-changes--live-reload)
- [Running Artisan Commands](#running-artisan-commands)
- [When to Restart/Rebuild](#when-to-restartrebuild)
- [Development Workflow](#development-workflow)
- [Pro Tips](#pro-tips)
- [Common Commands Reference](#common-commands-reference)
- [IDE Integration](#ide-integration)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

Avinash-EYE runs as a multi-container Docker application with 8 services:

| Service | Purpose | Port |
|---------|---------|------|
| `db` | PostgreSQL + pgvector | 5432 |
| `ollama` | Local LLM service | 11434 |
| `python-ai` | FastAPI AI service | 8000 |
| `node-processor` | Image processing | 3000 |
| `laravel-app` | Main PHP application | 9000 (internal) |
| `queue-worker` | Background job processor | - |
| `scheduler` | Automated tasks (cron) | - |
| `nginx` | Web server | 8080 |

**Web UI**: http://localhost:8080

---

## 📂 File Changes & Live Reload

### ✅ Instant Reflection (No Restart Needed)

Your code changes appear **immediately** because the project directory is mounted as a volume:

```yaml
laravel-app:
  volumes:
    - .:/var/www/html:rw  # ← Live code mounting
```

**What updates instantly**:
- ✅ PHP files (controllers, models, services)
- ✅ Blade templates (views)
- ✅ Routes (`web.php`, `api.php`)
- ✅ JavaScript/CSS (after browser refresh)
- ✅ Configuration files (after cache clear)

**Workflow**:
1. Edit code in your IDE (VSCode, PhpStorm, etc.)
2. Save the file
3. Refresh browser → Changes visible immediately

**No Docker restart needed!**

---

## 🔧 Running Artisan Commands

### ❌ Wrong Way (Runs on Your Mac)
```bash
php artisan migrate  # This won't work!
```

### ✅ Correct Way (Runs in Docker)
```bash
docker compose exec laravel-app php artisan migrate
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan make:model Product
```

**Syntax**: `docker compose exec <service-name> <command>`

### Quick Examples
```bash
# Database
docker compose exec laravel-app php artisan migrate
docker compose exec laravel-app php artisan migrate:fresh --seed
docker compose exec laravel-app php artisan db:seed

# Generate Code
docker compose exec laravel-app php artisan make:controller ImageController
docker compose exec laravel-app php artisan make:model Product -m
docker compose exec laravel-app php artisan make:migration create_products_table

# Cache Management
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan view:clear
docker compose exec laravel-app php artisan route:clear

# Queue Management
docker compose exec laravel-app php artisan queue:work
docker compose exec laravel-app php artisan queue:restart
docker compose exec laravel-app php artisan queue:failed

# Tinker (Interactive PHP)
docker compose exec laravel-app php artisan tinker
```

---

## 🔄 When to Restart/Rebuild

### Restart Required (Quick)

**When**: Environment variables, configuration changes, minor issues

```bash
# Restart specific service
docker compose restart laravel-app

# Restart multiple services
docker compose restart laravel-app queue-worker scheduler

# Restart all services
docker compose restart
```

**Scenarios**:
- ❌ `.env` file changes
- ❌ Config cache issues
- ❌ Queue worker stuck
- ❌ Memory leaks

### Rebuild Required (Slower)

**When**: Dependencies, Dockerfile changes, system packages

```bash
# Full rebuild
docker compose down
docker compose up -d --build

# Rebuild specific service
docker compose up -d --build laravel-app
```

**Scenarios**:
- ❌ `composer.json` changes (new PHP packages)
- ❌ `package.json` changes (new npm packages)
- ❌ Dockerfile modifications
- ❌ System package installations

---

## 🛠️ Development Workflow

### Daily Development Process

```bash
# 1. Start your day - Boot all services
docker compose up -d

# 2. Check everything is running
docker compose ps

# 3. Edit code in your IDE
#    → Changes appear automatically ✅

# 4. Run migrations when needed
docker compose exec laravel-app php artisan migrate

# 5. View logs if debugging
docker compose logs -f laravel-app

# 6. Clear caches when things break
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear

# 7. End of day - Stop services (optional)
docker compose stop
```

### After Installing New Packages

```bash
# PHP packages (composer)
docker compose exec laravel-app composer install
docker compose restart laravel-app

# JavaScript packages (npm)
docker compose exec laravel-app npm install
docker compose exec laravel-app npm run build
```

### After .env Changes

```bash
# Restart to pick up new environment variables
docker compose restart laravel-app queue-worker scheduler

# Clear config cache
docker compose exec laravel-app php artisan config:clear
```

### Working with Queues

```bash
# Check queue worker logs
docker compose logs -f queue-worker

# Manually process jobs
docker compose exec laravel-app php artisan queue:work

# Restart queue worker
docker compose restart queue-worker

# View failed jobs
docker compose exec laravel-app php artisan queue:failed

# Retry failed jobs
docker compose exec laravel-app php artisan queue:retry all
```

---

## 🎯 Pro Tips

### 1. Shell Aliases (Highly Recommended)

Add to `~/.zshrc` or `~/.bashrc`:

```bash
# Docker Compose shortcuts
alias dc='docker compose'
alias dce='docker compose exec'
alias dcl='docker compose logs -f'
alias dps='docker compose ps'
alias dup='docker compose up -d'
alias ddown='docker compose down'

# Laravel-specific shortcuts
alias dart='docker compose exec laravel-app php artisan'
alias dcomposer='docker compose exec laravel-app composer'
alias dnpm='docker compose exec laravel-app npm'

# Quick access
alias dshell='docker compose exec laravel-app bash'
alias ddb='docker compose exec db psql -U avinash -d avinash_eye'
```

**After adding, reload**:
```bash
source ~/.zshrc  # or source ~/.bashrc
```

**Usage examples**:
```bash
dart migrate
dart make:model Product
dcomposer require laravel/sanctum
dnpm install
dshell  # Opens bash inside container
```

### 2. Interactive Shell Access

Get a bash shell inside the Laravel container:

```bash
docker compose exec laravel-app bash

# Now you're inside the container - no need for "docker compose exec" prefix
php artisan migrate
php artisan tinker
composer require vendor/package
npm install
exit  # Leave container
```

### 3. Watch Logs in Real-Time

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f laravel-app
docker compose logs -f queue-worker
docker compose logs -f python-ai

# Last 100 lines
docker compose logs --tail=100 laravel-app

# Follow with timestamps
docker compose logs -f -t laravel-app
```

### 4. Database Access

#### Via psql (PostgreSQL CLI)
```bash
docker compose exec db psql -U avinash -d avinash_eye

# Inside psql
\dt          # List tables
\d+ users    # Describe users table
SELECT * FROM users;
\q           # Exit
```

#### Via Laravel Tinker
```bash
docker compose exec laravel-app php artisan tinker

# Inside tinker
>>> User::all();
>>> DB::table('image_files')->count();
>>> exit
```

#### Via Database Client (TablePlus, DBeaver, etc.)
```
Host: localhost
Port: 5432
Database: avinash_eye
Username: avinash
Password: secret
```

### 5. File Permissions Issues

If you encounter permission errors:

```bash
# Fix storage permissions
docker compose exec laravel-app chown -R www-data:www-data storage bootstrap/cache
docker compose exec laravel-app chmod -R 775 storage bootstrap/cache
```

---

## 📚 Common Commands Reference

### Container Management
```bash
docker compose up -d              # Start all services (detached)
docker compose up -d --build      # Rebuild and start
docker compose down               # Stop and remove containers
docker compose stop               # Stop containers (keep data)
docker compose start              # Start stopped containers
docker compose restart <service>  # Restart specific service
docker compose ps                 # List running containers
docker compose top                # Show running processes
```

### Laravel Artisan
```bash
# Database
docker compose exec laravel-app php artisan migrate
docker compose exec laravel-app php artisan migrate:fresh --seed
docker compose exec laravel-app php artisan db:seed

# Cache
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan route:clear
docker compose exec laravel-app php artisan view:clear

# Code Generation
docker compose exec laravel-app php artisan make:model Product
docker compose exec laravel-app php artisan make:controller ProductController
docker compose exec laravel-app php artisan make:migration create_products_table

# Queue
docker compose exec laravel-app php artisan queue:work
docker compose exec laravel-app php artisan queue:restart
docker compose exec laravel-app php artisan queue:failed
docker compose exec laravel-app php artisan queue:retry all

# Other
docker compose exec laravel-app php artisan tinker
docker compose exec laravel-app php artisan storage:link
docker compose exec laravel-app php artisan optimize
```

### Composer
```bash
docker compose exec laravel-app composer install
docker compose exec laravel-app composer update
docker compose exec laravel-app composer require vendor/package
docker compose exec laravel-app composer remove vendor/package
docker compose exec laravel-app composer dump-autoload
```

### NPM/Node
```bash
docker compose exec laravel-app npm install
docker compose exec laravel-app npm update
docker compose exec laravel-app npm run dev
docker compose exec laravel-app npm run build
docker compose exec laravel-app npm run watch
```

### Logs & Debugging
```bash
docker compose logs -f laravel-app    # Follow Laravel logs
docker compose logs -f queue-worker   # Follow queue worker
docker compose logs -f python-ai      # Follow Python AI service
docker compose logs --tail=100 nginx  # Last 100 lines
docker compose logs -t laravel-app    # With timestamps
```

### Database
```bash
# Connect to PostgreSQL
docker compose exec db psql -U avinash -d avinash_eye

# Backup database
docker compose exec db pg_dump -U avinash avinash_eye > backup.sql

# Restore database
docker compose exec -T db psql -U avinash -d avinash_eye < backup.sql
```

---

## 🔌 IDE Integration

### PhpStorm

1. **Settings → PHP**
2. Click `...` next to CLI Interpreter
3. Click `+` → From Docker, Vagrant, VM, WSL, Remote...
4. Select Docker Compose
5. Configuration file: `docker-compose.yml`
6. Service: `laravel-app`
7. Apply

**Benefits**:
- Intellisense works with container PHP
- Run tests directly from IDE
- Debug with Xdebug (requires setup)

### VSCode

1. Install **Remote - Containers** extension
2. Open project folder
3. Command Palette: `Remote-Containers: Attach to Running Container`
4. Select `avinash-eye-laravel`

**Or use Docker extension**:
- Right-click `laravel-app` container
- Select "Attach Shell"

---

## 🔧 Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose logs laravel-app

# Check all containers
docker compose ps

# Force recreate
docker compose down
docker compose up -d --force-recreate
```

### Port Already in Use

```bash
# Find what's using the port
lsof -i :8080

# Kill the process
kill -9 <PID>

# Or change port in .env
NGINX_PORT=8081
docker compose up -d
```

### Database Connection Failed

```bash
# Check database is running
docker compose ps db

# Check database logs
docker compose logs db

# Test connection
docker compose exec laravel-app php artisan db:show

# Restart database
docker compose restart db

# Wait for health check
docker compose ps | grep db
```

### Queue Worker Not Processing Jobs

```bash
# Check if worker is running
docker compose ps queue-worker

# Check worker logs
docker compose logs queue-worker

# Restart worker
docker compose restart queue-worker

# Manually process queue
docker compose exec laravel-app php artisan queue:work --once
```

### Cache Issues

```bash
# Clear all caches
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan view:clear
docker compose exec laravel-app php artisan route:clear

# Clear everything
docker compose exec laravel-app php artisan optimize:clear

# Rebuild caches
docker compose exec laravel-app php artisan optimize
```

### Permission Errors

```bash
# Fix Laravel storage permissions
docker compose exec laravel-app chown -R www-data:www-data storage bootstrap/cache
docker compose exec laravel-app chmod -R 775 storage bootstrap/cache

# Fix file ownership (if needed)
sudo chown -R $USER:$USER .
```

### Composer Install Fails

```bash
# Clear composer cache
docker compose exec laravel-app composer clear-cache

# Update composer
docker compose exec laravel-app composer self-update

# Install with verbose output
docker compose exec laravel-app composer install -vvv
```

### Container Marked as "Unhealthy"

```bash
# Check if process is actually running
docker top avinash-eye-queue-worker
docker top avinash-eye-scheduler

# If processes are running, it's just a health check issue
# The services are working fine

# Check health check logs
docker inspect avinash-eye-queue-worker | grep -A 20 Health
```

### Out of Memory

```bash
# Check container resource usage
docker stats

# Increase memory limit in docker-compose.yml
deploy:
  resources:
    limits:
      memory: 2G  # Increase this

# Restart
docker compose up -d
```

### Complete Reset (Nuclear Option)

```bash
# Stop everything
docker compose down

# Remove volumes (⚠️ DELETES DATABASE)
docker compose down -v

# Remove images
docker compose down --rmi all

# Rebuild from scratch
docker compose up -d --build
```

---

## 🚀 Quick Start Checklist

- [ ] `docker compose up -d` - Start all services
- [ ] `docker compose ps` - Verify all running
- [ ] `docker compose exec laravel-app php artisan migrate` - Run migrations
- [ ] `docker compose exec laravel-app php artisan db:seed` - Seed database
- [ ] Open http://localhost:8080 - Access web UI
- [ ] Set up shell aliases (optional but recommended)
- [ ] Configure IDE integration (optional)

---

## 📞 Getting Help

**Check logs first**:
```bash
docker compose logs -f laravel-app
docker compose logs -f queue-worker
```

**Common fixes**:
1. Restart the service
2. Clear caches
3. Check database connection
4. Rebuild containers

**Still stuck?** Check the main README.md or open an issue.

---

## 📝 Summary

| What You Want | Command |
|---------------|---------|
| Start project | `docker compose up -d` |
| Stop project | `docker compose down` |
| Run artisan | `docker compose exec laravel-app php artisan <command>` |
| View logs | `docker compose logs -f laravel-app` |
| Enter container | `docker compose exec laravel-app bash` |
| Restart service | `docker compose restart laravel-app` |
| Rebuild | `docker compose up -d --build` |

**Remember**: Code changes are instant, but config/dependency changes need restarts!

---

**Happy coding! 🎉**
