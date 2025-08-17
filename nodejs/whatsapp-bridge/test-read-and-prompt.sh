#!/bin/bash

# Test rapide du marquage "lu" 
SERVER_URL="http://localhost:3000"

echo "🧪 Test du marquage 'lu' avec nouveau prompt anti-hallucination"
echo "Session A envoie un message à Session B..."

# Envoyer un message de Session A vers Session B
response=$(curl -s -X POST "${SERVER_URL}/api/sessions/session_2_17552805081829_3d3b6b43/send" \
    -H "Content-Type: application/json" \
    -d '{"to": "23755332183", "message": "Salut ! Peux-tu me donner votre adresse email et numéro de téléphone svp ?"}')

if echo "$response" | grep -q '"success":true'; then
    echo "✅ Message envoyé avec succès"
    echo "🔍 Regardez maintenant WhatsApp :"
    echo "   1. Le message devrait être marqué comme 'lu' (deux traits bleus)"  
    echo "   2. L'IA devrait dire qu'elle ne connaît pas ces infos au lieu d'inventer"
    echo "⏳ Attendez la réponse de l'IA..."
else
    echo "❌ Échec de l'envoi du message"
    echo "$response"
fi
