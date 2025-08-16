#!/bin/bash

# Script pour surveiller TOUS les messages (entrants + sortants) en temps réel
# Usage: ./monitor-all-messages.sh

echo "🚀 Surveillance de TOUS LES MESSAGES en temps réel..."
echo "📁 Fichiers: incoming-messages-*.log + outgoing-messages-*.log"
echo "💡 Appuyez sur Ctrl+C pour arrêter"
echo ""
echo "============================================"
echo "📨📤 TOUS LES MESSAGES - TEMPS RÉEL"
echo "============================================"
echo ""

# Surveiller les deux types de logs en parallèle
tail -f logs/incoming-messages-*.log logs/outgoing-messages-*.log 2>/dev/null | \
while IFS= read -r line; do
    # Déterminer si c'est un message entrant ou sortant
    if echo "$line" | grep -q '"messageDirection":"incoming"'; then
        icon="📨"
        direction="ENTRANT"
    elif echo "$line" | grep -q '"messageDirection":"outgoing"'; then
        icon="📤"
        direction="SORTANT"
    else
        continue
    fi
    
    # Extraire les informations importantes
    if echo "$line" | grep -q '"timestamp"'; then
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        message=$(echo "$line" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        sessionId=$(echo "$line" | grep -o '"sessionId":"[^"]*"' | cut -d'"' -f4 | cut -d'_' -f3)
        
        echo "[$timestamp] $icon $direction: $message (Session: ...$sessionId)"
        
        # Détails spécifiques aux messages entrants
        if [ "$direction" = "ENTRANT" ]; then
            if echo "$line" | grep -q '"from"'; then
                from=$(echo "$line" | grep -o '"from":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
                echo "     👤 De: $from"
            fi
            
            if echo "$line" | grep -q '"body"' || echo "$line" | grep -q '"messageBody"'; then
                body=$(echo "$line" | grep -o -E '"(body|messageBody)":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
                echo "     💬 Message: $body..."
            fi
        fi
        
        # Détails spécifiques aux messages sortants
        if [ "$direction" = "SORTANT" ]; then
            if echo "$line" | grep -q '"to"'; then
                to=$(echo "$line" | grep -o '"to":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
                echo "     📞 Vers: $to"
            fi
            
            if echo "$line" | grep -q '"responseText"'; then
                response=$(echo "$line" | grep -o '"responseText":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
                echo "     🤖 Réponse: $response..."
            fi
        fi
        
        echo ""
    fi
done
