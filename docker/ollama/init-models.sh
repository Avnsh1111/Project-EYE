#!/bin/bash
# Ollama Model Initialization Script
# Automatically pulls required models on first start

set -e

echo "🤖 Ollama Model Auto-Setup Script"
echo "=================================="

# Wait for Ollama to be ready
echo "⏳ Waiting for Ollama service to be ready..."
TIMEOUT=60
COUNTER=0
until curl -f http://localhost:11434/api/tags >/dev/null 2>&1; do
    sleep 2
    COUNTER=$((COUNTER + 2))
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ Timeout waiting for Ollama to start"
        exit 1
    fi
done

echo "✅ Ollama is ready!"

# Function to check if model exists
model_exists() {
    curl -s http://localhost:11434/api/tags | grep -q "\"name\":\"$1\""
}

# Pull LLaVA model (recommended for image analysis)
echo ""
echo "📥 Checking LLaVA model..."
if model_exists "llava:latest"; then
    echo "✅ LLaVA model already exists"
else
    echo "⬇️  Pulling LLaVA model (this may take a while, ~4.7GB)..."
    ollama pull llava
    echo "✅ LLaVA model pulled successfully!"
fi

# Optionally pull other models (uncomment if needed)
# echo ""
# echo "📥 Checking Llama2 model..."
# if model_exists "llama2:latest"; then
#     echo "✅ Llama2 model already exists"
# else
#     echo "⬇️  Pulling Llama2 model..."
#     ollama pull llama2
#     echo "✅ Llama2 model pulled successfully!"
# fi

echo ""
echo "🎉 All models are ready!"
echo "📋 Available models:"
curl -s http://localhost:11434/api/tags | grep '"name"' | cut -d'"' -f4

echo ""
echo "✨ Ollama setup complete! Service is ready for use."

