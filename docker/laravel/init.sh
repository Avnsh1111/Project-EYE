#!/bin/bash
# Laravel Initialization Script for Production
# Handles first-time setup and updates

set -e

echo "🚀 Laravel Production Initialization"
echo "===================================="

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
TIMEOUT=60
COUNTER=0
until php artisan db:show >/dev/null 2>&1; do
    sleep 2
    COUNTER=$((COUNTER + 2))
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ Timeout waiting for database"
        exit 1
    fi
    echo "   Still waiting..."
done

echo "✅ Database connected!"

# Run migrations
echo ""
echo "📊 Running database migrations..."
php artisan migrate --force
echo "✅ Migrations complete!"

# Seed settings
echo ""
echo "⚙️  Seeding default settings..."
php artisan db:seed --class=SettingsSeeder --force 2>/dev/null || echo "   Settings already seeded"
echo "✅ Settings ready!"

# Create storage link
echo ""
echo "🔗 Creating storage symlink..."
php artisan storage:link 2>/dev/null || echo "   Link already exists"
echo "✅ Storage linked!"

# Set permissions
echo ""
echo "🔒 Setting proper permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
echo "✅ Permissions set!"

# Optimize for production
if [ "$APP_ENV" = "production" ]; then
    echo ""
    echo "⚡ Optimizing for production..."
    
    # Clear old cache
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Cache configuration
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "✅ Optimization complete!"
fi

echo ""
echo "🎉 Laravel initialization complete!"
echo "✨ Application is ready to serve requests"

