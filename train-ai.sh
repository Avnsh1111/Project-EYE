#!/bin/bash
#
# Quick AI Training Script
# Trains the AI models using your existing images
#

set -e

echo "🧠 Avinash-EYE AI Training"
echo "==========================="
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if Docker is running
if ! docker compose ps > /dev/null 2>&1; then
    echo "❌ Error: Docker Compose is not running"
    echo "Please start services first: docker compose up -d"
    exit 1
fi

echo "✅ Docker services are running"
echo ""

# Step 1: Export training data
echo "📊 Step 1/3: Exporting training data..."
php artisan export:training-data --limit=1000

if [ $? -ne 0 ]; then
    echo "❌ Failed to export training data"
    exit 1
fi

echo ""

# Step 2: Run training
echo "🎓 Step 2/3: Training AI models..."
echo "   This may take 5-15 minutes depending on your library size..."
echo ""

docker compose exec -T python-ai python train_model.py

if [ $? -ne 0 ]; then
    echo "❌ Training failed"
    echo "Check logs: docker compose logs python-ai"
    exit 1
fi

echo ""

# Step 3: Restart services
echo "🔄 Step 3/3: Restarting Python AI service..."
docker compose restart python-ai

echo ""
echo "⏳ Waiting for models to load (30 seconds)..."
sleep 30

echo ""
echo "✅ Training Complete!"
echo ""
echo "📊 Training Results:"
echo "   Check: python-ai/training_data/training_report.json"
echo ""
echo "🎯 What's Improved:"
echo "   ✓ Better image descriptions"
echo "   ✓ Smarter categorization"
echo "   ✓ Enhanced face recognition"
echo "   ✓ Improved search results"
echo ""
echo "🚀 Next Steps:"
echo "   1. Upload a new image to test"
echo "   2. Try searching with different terms"
echo "   3. Check the Collections page"
echo "   4. Retrain monthly for best results"
echo ""
echo "💡 Tip: Run './train-ai.sh' again after adding more images!"
echo ""

