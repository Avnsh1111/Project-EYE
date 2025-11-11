#!/bin/bash
#
# Python AI Service Startup Script
# Handles model loading, training, and service startup
#

set -e

echo "🚀 Starting Avinash-EYE Python AI Service..."
echo ""

# Function to check if training data exists
check_training_data() {
    if [ -f "/app/training_data/images_metadata.json" ]; then
        return 0
    else
        return 1
    fi
}

# Function to check if models are already trained
check_trained_models() {
    if [ -f "/app/training_data/category_patterns.json" ] && \
       [ -f "/app/training_data/description_patterns.json" ]; then
        return 0
    else
        return 1
    fi
}

# Function to run training in background
run_training_background() {
    echo "📊 Training data found, starting background training..."
    (
        sleep 60  # Wait for main service to be ready
        echo "🎓 Starting AI model training..."
        python /app/train_model.py > /app/training_data/training.log 2>&1
        
        if [ $? -eq 0 ]; then
            echo "✅ Training completed successfully"
            echo "$(date): Training completed" >> /app/training_data/training_history.log
        else
            echo "❌ Training failed, check /app/training_data/training.log"
            echo "$(date): Training failed" >> /app/training_data/training_history.log
        fi
    ) &
}

# Main startup sequence
echo "1️⃣  Phase 1: Environment Check"
echo "   Working directory: $(pwd)"
echo "   Python version: $(python --version)"
echo ""

# Create training data directory if it doesn't exist
mkdir -p /app/training_data
echo "   ✓ Training data directory ready"

# Check for training data and trained models
echo ""
echo "2️⃣  Phase 2: Training Status Check"

if check_training_data; then
    echo "   ✓ Training data found"
    
    if check_trained_models; then
        echo "   ✓ Previously trained models found"
        echo "   ℹ️  Using existing trained patterns"
        
        # Check if training data is newer than trained models
        METADATA_TIME=$(stat -c %Y /app/training_data/images_metadata.json 2>/dev/null || stat -f %m /app/training_data/images_metadata.json)
        PATTERNS_TIME=$(stat -c %Y /app/training_data/category_patterns.json 2>/dev/null || stat -f %m /app/training_data/category_patterns.json)
        
        if [ "$METADATA_TIME" -gt "$PATTERNS_TIME" ]; then
            echo "   ⚠️  Training data is newer than models"
            echo "   🔄 Will retrain in background..."
            SHOULD_RETRAIN=1
        else
            SHOULD_RETRAIN=0
        fi
    else
        echo "   ⚠️  No trained models found"
        echo "   🔄 Will train in background..."
        SHOULD_RETRAIN=1
    fi
else
    echo "   ℹ️  No training data yet (will use base models)"
    echo "   💡 Export data to train: php artisan export:training-data"
    SHOULD_RETRAIN=0
fi

# Start training in background if needed
if [ "$SHOULD_RETRAIN" = "1" ]; then
    echo ""
    echo "3️⃣  Phase 3: Background Training"
    run_training_background
    echo "   ✓ Training scheduled in background"
fi

echo ""
echo "4️⃣  Phase 4: Starting FastAPI Service"
echo "   Listening on: 0.0.0.0:8000"
echo "   Enhanced analysis: $([ -f /app/enhanced_analysis.py ] && echo 'Enabled' || echo 'Disabled')"
echo ""
echo "✅ Service Ready!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Start the FastAPI service
exec uvicorn main:app --host 0.0.0.0 --port 8000 --workers 1

