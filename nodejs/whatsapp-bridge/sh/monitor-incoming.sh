#!/bin/bash

# Script pour surveiller les messages entrants en temps réel
# Usage: ./monitor-incoming.sh

echo "🚀 Surveillance des MESSAGES ENTRANTS en temps réel..."
echo "📁 Fichier: $(pwd)/logs/incoming-messages-*.log"
echo "💡 Appuyez sur Ctrl+C pour arrêter"
echo ""
echo "========================================"
echo "📨 MESSAGES ENTRANTS - TEMPS RÉEL"
echo "========================================"
echo ""

# Surveiller les logs de messages entrants
tail -f logs/incoming-messages-*.log 2>/dev/null | \
while IFS= read -r line; do
    # Extraire les informations importantes
    if echo "$line" | grep -q '"timestamp"'; then
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        message=$(echo "$line" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        sessionId=$(echo "$line" | grep -o '"sessionId":"[^"]*"' | cut -d'"' -f4 | cut -d'_' -f3)
        
        echo "[$timestamp] 📨 $message (Session: ...$sessionId)"
        
        # Afficher les détails selon le type de message
        if echo "$line" | grep -q '"from"'; then
            from=$(echo "$line" | grep -o '"from":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
            echo "   👤 De: $from"
        fi
        
        if echo "$line" | grep -q '"body"'; then
            body=$(echo "$line" | grep -o '"body":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
            echo "   💬 Message: $body..."
        fi
        
        if echo "$line" | grep -q '"messageBody"'; then
            body=$(echo "$line" | grep -o '"messageBody":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
            echo "   💬 Contenu: $body..."
        fi
        
        if echo "$line" | grep -q '"isGroup":true'; then
            echo "   👥 Message de groupe"
        fi
        
        if echo "$line" | grep -q '"hasMedia":true'; then
            mediaType=$(echo "$line" | grep -o '"mediaType":"[^"]*"' | cut -d'"' -f4)
            echo "   📎 Média: $mediaType"
        fi
        
        echo ""
    fi
done
