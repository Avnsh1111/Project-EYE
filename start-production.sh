#!/bin/bash
# Production Startup Script for Avinash-EYE
# Handles complete system initialization

set -e

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                                                              ║"
echo "║     🚀 Avinash-EYE Production Startup                       ║"
echo "║                                                              ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  .env file not found!${NC}"
    echo ""
    echo "Creating .env from .env.production..."
    cp .env.production .env
    echo -e "${GREEN}✅ .env created!${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  IMPORTANT: Please update the following in .env:${NC}"
    echo "   1. DB_PASSWORD (change from 'secret')"
    echo "   2. Run: docker compose exec laravel-app php artisan key:generate"
    echo ""
    read -p "Press Enter to continue or Ctrl+C to exit and configure .env..."
fi

# Check if APP_KEY is set
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
    echo -e "${YELLOW}⚠️  APP_KEY not set in .env${NC}"
    echo "   This will be generated automatically after containers start"
    echo ""
fi

echo "📋 Pre-flight checks..."
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker found${NC}"

# Check Docker Compose
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker Compose found${NC}"

# Check available memory
AVAILABLE_MEMORY=$(docker info --format '{{.MemTotal}}' 2>/dev/null | awk '{print int($1/1024/1024/1024)}')
if [ -n "$AVAILABLE_MEMORY" ] && [ "$AVAILABLE_MEMORY" -lt 8 ]; then
    echo -e "${YELLOW}⚠️  Warning: Only ${AVAILABLE_MEMORY}GB RAM available${NC}"
    echo "   Recommended: 8GB minimum, 16GB for optimal performance"
    echo ""
fi

# Check available disk space
AVAILABLE_SPACE=$(df -BG . | awk 'NR==2 {print $4}' | sed 's/G//')
if [ "$AVAILABLE_SPACE" -lt 10 ]; then
    echo -e "${YELLOW}⚠️  Warning: Only ${AVAILABLE_SPACE}GB disk space available${NC}"
    echo "   Recommended: 10GB minimum (5GB for models + images)"
    echo ""
fi

echo ""
echo "🏗️  Building and starting services..."
echo "   This may take 10-15 minutes on first run (downloading AI models)"
echo ""

# Install Composer dependencies locally first
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies locally..."
    if command -v composer &> /dev/null; then
        composer install --no-interaction --optimize-autoloader
        echo -e "${GREEN}✅ Dependencies installed${NC}"
    else
        echo -e "${YELLOW}⚠️  Composer not found locally, will install in container${NC}"
        echo "   Note: This may cause issues with volume mounts"
    fi
    echo ""
fi

# Stop any existing containers
echo "🛑 Stopping any existing containers..."
docker compose down 2>/dev/null || true

# Build and start services
echo ""
echo "🔨 Building containers..."
docker compose build --no-cache

echo ""
echo "🚀 Starting all services..."
docker compose up -d

echo ""
echo "⏳ Waiting for services to be healthy..."
echo ""

# Function to check service health
check_service() {
    local service=$1
    local max_wait=$2
    local waited=0
    
    echo -n "   Checking $service... "
    
    while [ $waited -lt $max_wait ]; do
        if docker compose ps $service | grep -q "healthy"; then
            echo -e "${GREEN}✅ Ready${NC}"
            return 0
        fi
        sleep 5
        waited=$((waited + 5))
        echo -n "."
    done
    
    echo -e "${YELLOW}⏰ Still starting (may take longer)${NC}"
    return 1
}

# Check each service
echo "📊 Service Status:"
echo ""
check_service "db" 60
check_service "ollama" 180
check_service "python-ai" 300
check_service "laravel-app" 120
check_service "queue-worker" 60

echo ""
echo "🔐 Security Setup..."

# Generate APP_KEY if not set
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
    echo "   Generating application key..."
    docker compose exec -T laravel-app php artisan key:generate --force
    echo -e "   ${GREEN}✅ Application key generated${NC}"
fi

echo ""
echo "📦 System Information:"
echo "─────────────────────────────────────────────────────────────"

# Get service information
echo ""
echo "🐳 Container Status:"
docker compose ps

echo ""
echo "📊 Resource Usage:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" $(docker compose ps -q)

echo ""
echo "🤖 AI Models Status:"
echo ""
echo "   Ollama Models:"
docker compose exec -T ollama ollama list 2>/dev/null || echo "   (Still downloading...)"

echo ""
echo "   Python AI Status:"
curl -s http://localhost:8000/health | python3 -m json.tool 2>/dev/null || echo "   (Still initializing...)"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                                                              ║"
echo "║     ✅ Avinash-EYE is Starting Up!                          ║"
echo "║                                                              ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 Access Points:"
echo "   Web Interface: http://localhost:8080"
echo "   AI Service:    http://localhost:8000"
echo "   Database:      localhost:5432"
echo ""
echo "📝 Useful Commands:"
echo "   View logs:        docker compose logs -f"
echo "   Stop services:    docker compose down"
echo "   Restart:          docker compose restart"
echo "   Check status:     docker compose ps"
echo ""
echo "⏰ Note: First startup takes 10-15 minutes to download AI models (~5GB)"
echo "   Subsequent starts will be much faster (under 2 minutes)"
echo ""
echo "📚 Documentation: docs/README.md"
echo ""
echo "🎉 Happy image managing!"
echo ""

# Follow logs
echo "📋 Following container logs (Ctrl+C to exit)..."
echo "─────────────────────────────────────────────────────────────"
echo ""
docker compose logs -f --tail=50

