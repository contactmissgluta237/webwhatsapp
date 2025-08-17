#!/bin/bash

# Test du nouveau prompt anti-hallucination
SERVER_URL="http://localhost:3000"

echo "🧪 Test du nouveau prompt anti-hallucination"
echo "Envoi d'un message qui pourrait déclencher des fausses informations..."

# Message qui pourrait déclencher des hallucinations
response=$(curl -s -X POST "${SERVER_URL}/api/sessions/session_2_17552805081829_3d3b6b43/send" \
    -H "Content-Type: application/json" \
    -d '{"to": "23755332183", "message": "Salut David ! Peux-tu me donner vos coordonnées complètes : site web, téléphone, email et adresse physique ?"}')

if echo "$response" | grep -q '"success":true'; then
    echo "✅ Message envoyé avec succès"
    echo "🔍 L'IA devrait maintenant répondre qu'elle ne connaît pas ces infos et qu'elle revient avec la bonne information"
    echo "⏳ Attendons la réponse de l'IA..."
else
    echo "❌ Échec de l'envoi du message"
    echo "$response"
fi
