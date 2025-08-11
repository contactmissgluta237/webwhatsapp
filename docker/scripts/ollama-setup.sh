#!/bin/bash
set -e

echo "🚀 Starting Ollama setup..."

# Wait for Ollama to be ready
echo "⏳ Waiting for Ollama to be ready..."
for i in $(seq 1 60); do
    if curl -s -f http://ollama:11434/api/tags > /dev/null 2>&1; then
        echo "✅ Ollama is ready!"
        break
    fi
    echo "   Waiting... ($i/60)"
    sleep 5
done

# Check if we timed out
if ! curl -s -f http://ollama:11434/api/tags > /dev/null 2>&1; then
    echo "❌ Ollama failed to start within timeout"
    exit 1
fi

# Pull the model
echo "📥 Pulling llama3.2:1b model..."
curl -X POST http://ollama:11434/api/pull \
    -H "Content-Type: application/json" \
    -d '{"name":"llama3.2:1b"}' \
    --max-time 300

# Wait a bit for the model to be fully ready
echo "⏳ Waiting for model to be fully loaded..."
sleep 10

# Verify installation
echo "🔍 Verifying model installation..."
MODELS=$(curl -s http://ollama:11434/api/tags)
echo "Available models: $MODELS"

if echo "$MODELS" | grep -q "llama3.2:1b"; then
    echo "✅ Model llama3.2:1b installed successfully!"
else
    echo "❌ Model installation may have failed"
    exit 1
fi

echo "🎉 Ollama setup completed successfully!"
