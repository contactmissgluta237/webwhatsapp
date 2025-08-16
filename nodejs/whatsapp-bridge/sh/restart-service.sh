#!/bin/bash

echo "🔄 Redémarrage du service WhatsApp Bridge..."

# Arrêter le processus Node.js existant
echo "🛑 Arrêt du service actuel..."
pkill -f "node src/server.js"

# Attendre un peu
sleep 2

# Vérifier si le processus est bien arrêté
if pgrep -f "node src/server.js" > /dev/null; then
    echo "⚠️  Forçage de l'arrêt..."
    pkill -9 -f "node src/server.js"
    sleep 2
fi

# Démarrer le nouveau service
echo "🚀 Démarrage du nouveau service..."
cd /home/douglas/Documents/AfrikSolutions/Projects/web-whatsapp/nodejs/whatsapp-bridge
nohup node src/server.js > service.log 2>&1 &

# Attendre que le service démarre
sleep 3

# Vérifier que le service fonctionne
if curl -s http://localhost:3000/api/sessions > /dev/null; then
    echo "✅ Service redémarré avec succès!"
    echo "📊 Vérification des sessions connectées..."
    curl -s http://localhost:3000/api/sessions | jq '.sessions[] | select(.status=="connected") | {sessionId, phoneNumber, status}'
else
    echo "❌ Erreur lors du redémarrage du service"
    echo "📋 Logs d'erreur:"
    tail -10 service.log
fi
