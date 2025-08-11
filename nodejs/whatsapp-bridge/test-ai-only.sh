# docker/whatsapp-bridge/test-ai-only.sh
#!/bin/bash
echo "🤖 Quick AI Test..."

echo "Testing Ollama..."
curl -X POST http://localhost:3000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello! What is your name?",
    "options": {"model": "llama3.2:1b"}
  }' | jq '.ai_response'

echo -e "\nAI Service Info:"
curl -s http://localhost:3000/api/ai/status | jq '.service'
