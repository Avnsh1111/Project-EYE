#!/bin/bash
#
# Laravel Scheduler Daemon
# Runs the Laravel task scheduler continuously
#

set -e

echo "🕐 Starting Laravel Scheduler..."
echo ""

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please copy .env.example to .env and configure it."
    exit 1
fi

# Check if artisan exists
if [ ! -f artisan ]; then
    echo "❌ Error: artisan file not found!"
    echo "Are you in the Laravel project directory?"
    exit 1
fi

echo "📋 Scheduled Tasks:"
php artisan schedule:list
echo ""

echo "✅ Scheduler is now running!"
echo "   - Intelligent image reprocessing: Every 30 minutes"
echo "   - Batch size: 20 images per run"
echo ""
echo "💡 Tips:"
echo "   - Press Ctrl+C to stop"
echo "   - View logs: tail -f storage/logs/laravel.log"
echo "   - This will run scheduled tasks every minute"
echo ""
echo "🔄 Running scheduler daemon..."
echo ""

# Run the scheduler every minute
while true; do
    php artisan schedule:run >> /dev/null 2>&1
    sleep 60
done

