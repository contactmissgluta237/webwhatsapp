#!/bin/bash

# Script pour surveiller les messages sortants en temps réel
# Usage: ./monitor-outgoing.sh

echo "🚀 Surveillance des MESSAGES SORTANTS en temps réel..."
echo "📁 Fichier: $(pwd)/logs/outgoing-messages-*.log"
echo "💡 Appuyez sur Ctrl+C pour arrêter"
echo ""
echo "========================================"
echo "📤 MESSAGES SORTANTS - TEMPS RÉEL"
echo "========================================"
echo ""

# Surveiller les logs de messages sortants
tail -f logs/outgoing-messages-*.log 2>/dev/null | \
while IFS= read -r line; do
    # Extraire les informations importantes
    if echo "$line" | grep -q '"timestamp"'; then
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        message=$(echo "$line" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        sessionId=$(echo "$line" | grep -o '"sessionId":"[^"]*"' | cut -d'"' -f4 | cut -d'_' -f3)
        
        echo "[$timestamp] 📤 $message (Session: ...$sessionId)"
        
        # Afficher les détails selon le type de message
        if echo "$line" | grep -q '"to"'; then
            to=$(echo "$line" | grep -o '"to":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
            echo "   📞 Vers: $to"
        fi
        
        if echo "$line" | grep -q '"messageLength"'; then
            length=$(echo "$line" | grep -o '"messageLength":[0-9]*' | cut -d':' -f2)
            echo "   📏 Longueur: $length caractères"
        fi
        
        if echo "$line" | grep -q '"messagePreview"'; then
            preview=$(echo "$line" | grep -o '"messagePreview":"[^"]*"' | cut -d'"' -f4)
            echo "   💬 Aperçu: $preview"
        fi
        
        if echo "$line" | grep -q '"responseText"'; then
            response=$(echo "$line" | grep -o '"responseText":"[^"]*"' | cut -d'"' -f4)
            echo "   🤖 Réponse IA: $response"
        fi
        
        if echo "$line" | grep -q '"originalMessageId"'; then
            originalId=$(echo "$line" | grep -o '"originalMessageId":"[^"]*"' | cut -d'"' -f4)
            echo "   🔗 En réponse à: $originalId"
        fi
        
        echo ""
    fi
done
