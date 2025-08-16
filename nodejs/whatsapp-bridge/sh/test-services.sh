#!/bin/bash
# docker/whatsapp-bridge/test-services.sh

BASE_URL="http://localhost:3000"
echo "🚀 Testing WhatsApp Bridge Services..."

# Test 1: Health Check
echo -e "\n1️⃣  Testing Health Check..."
curl -s "$BASE_URL/health" | jq '.'

# Test 2: AI Service Status
echo -e "\n2️⃣  Testing AI Service Status..."
curl -s "$BASE_URL/api/ai/status" | jq '.'

# Test 3: Available Models
echo -e "\n3️⃣  Testing Available Models..."
curl -s "$BASE_URL/api/ai/models" | jq '.'

# Test 4: Chat with Ollama
echo -e "\n4️⃣  Testing Chat with Ollama..."
curl -X POST "$BASE_URL/api/ai/chat" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello! What is 2+2?",
    "options": {
      "model": "llama3.2:1b"
    }
  }' | jq '.'

# Test 5: Switch to DeepSeek (if API key is available)
echo -e "\n5️⃣  Testing Switch to DeepSeek..."
curl -X POST "$BASE_URL/api/ai/switch" \
  -H "Content-Type: application/json" \
  -d '{
    "service": "deepseek",
    "config": {
      "defaultModel": "deepseek-chat"
    }
  }' | jq '.'

# Test 6: Chat with DeepSeek
echo -e "\n6️⃣  Testing Chat with DeepSeek..."
curl -X POST "$BASE_URL/api/ai/chat" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello! Can you solve 15 * 23?",
    "options": {}
  }' | jq '.'

# Test 7: Switch back to Ollama
echo -e "\n7️⃣  Switching back to Ollama..."
curl -X POST "$BASE_URL/api/ai/switch" \
  -H "Content-Type: application/json" \
  -d '{
    "service": "ollama",
    "config": {
      "baseUrl": "http://ollama:11434",
      "defaultModel": "llama3.2:1b"
    }
  }' | jq '.'

# Test 8: WhatsApp Session Management
echo -e "\n8️⃣  Testing WhatsApp Session Creation..."
curl -X POST "$BASE_URL/api/sessions/create" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "test-session-001",
    "userId": "test-user-123"
  }' | jq '.'

# Test 9: Get Session Status
echo -e "\n9️⃣  Testing Session Status..."
sleep 2
curl -s "$BASE_URL/api/sessions/test-session-001/status" | jq '.'

# Test 10: List all sessions
echo -e "\n🔟 Testing List Sessions..."
curl -s "$BASE_URL/api/sessions" | jq '.'

echo -e "\n✅ Tests completed!"
