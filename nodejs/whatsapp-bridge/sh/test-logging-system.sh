#!/bin/bash

# Script de test pour le système de logs des messages WhatsApp

echo "🧪 Test du système de logs des messages"
echo "======================================"

# Nettoyer les anciens logs pour ce test
echo "🧹 Nettoyage des logs de test..."
> logs/outgoing-messages-2025-08-15.log
> logs/incoming-messages-2025-08-15.log

# Test direct du logger
echo "📝 Test 1: Logger direct"
node -e "
const logger = require('./src/config/logger');
logger.outgoingMessage('TEST DIRECT OUTGOING', {
    sessionId: 'test_session_direct',
    to: 'test_number_direct',
    messageLength: 20,
    userId: 999
});
console.log('✅ Test direct terminé');
"

# Vérifier le résultat
echo "📋 Résultat du test direct:"
tail -1 logs/outgoing-messages-2025-08-15.log | jq '.'

echo ""
echo "🔄 Test 2: Simulation d'appel API"
# Tester via l'API avec données fictives
curl -X POST http://localhost:3000/api/bridge/send-message \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": "session_test_fake", 
    "to": "237000000000", 
    "message": "Message de test système de logs"
  }' \
  --connect-timeout 5 \
  --max-time 10

echo ""
echo "📋 Derniers logs outgoing après test API:"
tail -3 logs/outgoing-messages-2025-08-15.log | jq '.'

echo ""
echo "🔍 Vérification de tous les fichiers de logs:"
echo "Fichier outgoing: $(wc -l < logs/outgoing-messages-2025-08-15.log) lignes"
echo "Fichier incoming: $(wc -l < logs/incoming-messages-2025-08-15.log) lignes"

echo ""
echo "🎯 Test terminé!"
