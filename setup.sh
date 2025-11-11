#!/bin/bash

echo "🚀 Avinash-EYE Setup Script"
echo "================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker and try again."
    exit 1
fi

echo "✅ Docker is running"
echo ""

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi
echo ""

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/public/images
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "✅ Storage directories created"
echo ""

# Build and start Docker containers
echo "🐳 Building and starting Docker containers..."
echo "⏳ This may take 10-15 minutes on first run (downloading AI models)"
echo ""
docker-compose up -d --build

# Wait for services to be ready
echo ""
echo "⏳ Waiting for services to start..."
sleep 10

# Check if laravel-app container is running
if ! docker-compose ps | grep -q "laravel-app.*Up"; then
    echo "❌ Error: Laravel container failed to start. Check logs with: docker-compose logs laravel-app"
    exit 1
fi

echo "✅ Containers are running"
echo ""

# Generate application key if not set
echo "🔑 Generating application key..."
docker-compose exec -T laravel-app php artisan key:generate --force
echo "✅ Application key generated"
echo ""

# Run database migrations
echo "🗄️  Running database migrations..."
sleep 5  # Give database time to be ready
docker-compose exec -T laravel-app php artisan migrate --force
echo "✅ Migrations completed"
echo ""

# Create storage symlink
echo "🔗 Creating storage symlink..."
docker-compose exec -T laravel-app php artisan storage:link
echo "✅ Storage symlink created"
echo ""

# Check AI service health
echo "🤖 Checking AI service..."
echo "⏳ Waiting for AI models to load (this can take a few minutes)..."
for i in {1..30}; do
    if curl -s http://localhost:8000/health | grep -q "models_loaded"; then
        echo "✅ AI service is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "⚠️  AI service is still loading. You can check progress with: docker-compose logs -f python-ai"
    fi
    sleep 10
done
echo ""

echo "================================"
echo "✨ Setup complete!"
echo ""
echo "📊 Service Status:"
docker-compose ps
echo ""
echo "🌐 Access the application at: http://localhost:8080"
echo ""
echo "📝 Useful Commands:"
echo "   View logs:           docker-compose logs -f"
echo "   Stop services:       docker-compose down"
echo "   Restart services:    docker-compose restart"
echo "   Run artisan:         docker-compose exec laravel-app php artisan [command]"
echo ""
echo "🎉 Happy image analyzing!"

