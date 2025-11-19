#!/bin/bash

# Fresh Start Script for Avinash-EYE
# This script completely resets the application to a fresh state
# WARNING: This will delete ALL data including images, database, and cache

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     Avinash-EYE Fresh Start Script                    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${RED}⚠️  WARNING: This will delete ALL data!${NC}"
echo -e "${RED}   - All uploaded images${NC}"
echo -e "${RED}   - Database data${NC}"
echo -e "${RED}   - Cache files${NC}"
echo -e "${RED}   - All user accounts and settings${NC}"
echo ""

# Confirmation prompt
read -p "Are you sure you want to continue? (type 'yes' to confirm): " confirm
if [ "$confirm" != "yes" ]; then
    echo -e "${YELLOW}❌ Cancelled. No changes made.${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}🛑 Step 1/8: Stopping all containers...${NC}"
docker compose down || true
echo -e "${GREEN}✅ Containers stopped${NC}"

echo ""
echo -e "${BLUE}🗑️  Step 2/8: Removing containers...${NC}"
docker compose rm -f || true
echo -e "${GREEN}✅ Containers removed${NC}"

echo ""
echo -e "${BLUE}💾 Step 3/8: Removing Docker volumes (database, ollama data)...${NC}"
# Get project name from directory name
PROJECT_NAME=$(basename "$(pwd)" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/-/g')
# Try different volume name patterns
docker volume rm "${PROJECT_NAME}_pgdata" "${PROJECT_NAME}_ollama-data" 2>/dev/null || true
docker volume rm "avinash-eye_pgdata" "avinash-eye_ollama-data" 2>/dev/null || true
docker volume rm "pgdata" "ollama-data" 2>/dev/null || true
# Find and remove volumes by name pattern (more reliable)
docker volume ls -q | grep -E "(pgdata|ollama)" | while read vol; do
    docker volume rm "$vol" 2>/dev/null || true
done
echo -e "${GREEN}✅ Volumes removed${NC}"

echo ""
echo -e "${BLUE}📁 Step 4/8: Clearing Laravel storage and cache...${NC}"
# Remove storage files (but keep directory structure)
if [ -d "storage/app/public/images" ]; then
    find storage/app/public/images -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✅ Image files cleared${NC}"
fi

# Clear Laravel cache directories
rm -rf storage/framework/cache/* 2>/dev/null || true
rm -rf storage/framework/sessions/* 2>/dev/null || true
rm -rf storage/framework/views/* 2>/dev/null || true
rm -rf storage/logs/*.log 2>/dev/null || true
echo -e "${GREEN}✅ Cache cleared${NC}"

echo ""
echo -e "${BLUE}🔨 Step 5/8: Rebuilding Docker images...${NC}"
docker compose build --no-cache
echo -e "${GREEN}✅ Images rebuilt${NC}"

echo ""
echo -e "${BLUE}🚀 Step 6/8: Starting services...${NC}"
docker compose up -d
echo -e "${GREEN}✅ Services started${NC}"

echo ""
echo -e "${BLUE}⏳ Step 7/8: Waiting for services to be ready...${NC}"
echo "   Waiting for database..."
sleep 10

# Wait for database to be ready
TIMEOUT=60
COUNTER=0
# Load DB credentials from .env if available
if [ -f .env ]; then
    export $(grep -E '^DB_(USERNAME|DATABASE)=' .env | xargs)
fi
DB_USER="${DB_USERNAME:-avinash}"
DB_NAME="${DB_DATABASE:-avinash_eye}"
until docker compose exec -T db pg_isready -U "$DB_USER" -d "$DB_NAME" >/dev/null 2>&1; do
    sleep 2
    COUNTER=$((COUNTER + 2))
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo -e "${RED}❌ Timeout waiting for database${NC}"
        break
    fi
    if [ $((COUNTER % 10)) -eq 0 ]; then
        echo "   Still waiting... (${COUNTER}s)"
    fi
done

# Wait for Laravel container to be ready
echo "   Waiting for Laravel container..."
sleep 5

echo -e "${GREEN}✅ Services ready${NC}"

echo ""
echo -e "${BLUE}📊 Step 8/8: Running fresh migrations and seeding...${NC}"

# Run fresh migrations
echo "   Running migrations..."
docker compose exec -T laravel-app php artisan migrate:fresh --force

# Seed database
echo "   Seeding database..."
docker compose exec -T laravel-app php artisan db:seed --force

# Create storage link
echo "   Creating storage link..."
docker compose exec -T laravel-app php artisan storage:link || true

# Clear and cache config
echo "   Optimizing application..."
docker compose exec -T laravel-app php artisan config:clear || true
docker compose exec -T laravel-app php artisan cache:clear || true
docker compose exec -T laravel-app php artisan route:clear || true
docker compose exec -T laravel-app php artisan view:clear || true

echo -e "${GREEN}✅ Database initialized${NC}"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          ✅ Fresh Start Complete!                     ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo ""
echo -e "1. ${YELLOW}Access the application:${NC}"
echo -e "   ${GREEN}http://localhost:8080${NC}"
echo ""
echo -e "2. ${YELLOW}Default login credentials:${NC}"
echo -e "   Email: ${GREEN}admin@avinash-eye.local${NC} (or from .env)"
echo -e "   Password: ${GREEN}Admin@123${NC} (or from .env)"
echo ""
echo -e "3. ${YELLOW}Start queue worker (for image processing):${NC}"
echo -e "   ${GREEN}./start-queue-worker.sh${NC}"
echo ""
echo -e "4. ${YELLOW}Check service status:${NC}"
echo -e "   ${GREEN}docker compose ps${NC}"
echo ""
echo -e "${YELLOW}⚠️  Remember to change the default password!${NC}"
echo ""

