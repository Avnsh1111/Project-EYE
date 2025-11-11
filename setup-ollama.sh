#!/bin/bash
#
# Ollama Setup Script for Avinash-EYE
# This script pulls the required Ollama models for image analysis
#

set -e

echo "🚀 Setting up Ollama models for Avinash-EYE..."
echo ""

# Check if Docker Compose is running
if ! docker compose ps ollama &> /dev/null; then
    echo "❌ Ollama container is not running."
    echo "Please start it first: docker compose up -d ollama"
    exit 1
fi

echo "✅ Ollama container is running"
echo ""

# Function to pull a model
pull_model() {
    local model=$1
    echo "📦 Pulling Ollama model: $model"
    echo "   This may take several minutes..."
    
    if docker compose exec -T ollama ollama pull "$model"; then
        echo "✅ Successfully pulled $model"
        echo ""
    else
        echo "❌ Failed to pull $model"
        echo ""
        return 1
    fi
}

# Pull the primary vision model (llava)
echo "=== Primary Vision Model ==="
pull_model "llava"

# Optional: Pull alternative models
read -p "Do you want to pull additional models? (llava:13b, bakllava) [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "=== Additional Models ==="
    
    read -p "Pull llava:13b (larger, more accurate, slower)? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        pull_model "llava:13b"
    fi
    
    read -p "Pull bakllava (alternative vision model)? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        pull_model "bakllava"
    fi
fi

echo ""
echo "=== Model List ==="
echo "Available Ollama models:"
docker compose exec -T ollama ollama list

echo ""
echo "✅ Ollama setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Go to Settings page: http://localhost:8080/settings"
echo "   2. Enable 'Ollama (Detailed Descriptions)'"
echo "   3. Select your preferred model (llava is recommended)"
echo "   4. Save settings"
echo "   5. Upload images to get detailed AI descriptions!"
echo ""

