#!/bin/bash

# Script pour surveiller les logs de messages WhatsApp en temps réel
# Usage: ./monitor-messages.sh

echo "🚀 Démarrage du monitoring des messages WhatsApp..."
echo "📁 Surveillance du dossier: $(pwd)/logs/"
echo "💡 Appuyez sur Ctrl+C pour arrêter"
echo ""
echo "========================================"
echo "🔍 MESSAGES WHATSAPP EN TEMPS RÉEL"
echo "========================================"
echo ""

# Surveiller les logs WhatsApp avec des filtres pour les messages
tail -f logs/whatsapp-*.log logs/app-*.log 2>/dev/null | \
grep -E "(📨|👤|👥|📎|🔄|✅|🤖|❌|MESSAGE)" --color=always | \
while read line; do
    # Extraire le timestamp et le message pour un affichage plus lisible
    if echo "$line" | grep -q '"timestamp"'; then
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        message=$(echo "$line" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        echo "[$timestamp] $message"
        
        # Afficher les détails du message si disponible
        if echo "$line" | grep -q '"body"'; then
            body=$(echo "$line" | grep -o '"body":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
            from=$(echo "$line" | grep -o '"from":"[^"]*"' | cut -d'"' -f4)
            echo "   📧 De: $from"
            echo "   💬 Message: $body..."
        fi
        echo ""
    else
        echo "$line"
    fi
done
