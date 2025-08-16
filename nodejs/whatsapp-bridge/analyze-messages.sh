#!/bin/bash

# Script pour analyser les logs de messages WhatsApp séparés
# Usage: ./analyze-messages.sh [date] (format: 2025-08-15)

DATE=${1:-$(date +%Y-%m-%d)}
LOGS_DIR="logs"

echo "📊 ANALYSE DES MESSAGES WHATSAPP - $DATE"
echo "========================================"
echo ""

# Vérifier l'existence des fichiers de logs
INCOMING_LOG="$LOGS_DIR/incoming-messages-$DATE.log"
OUTGOING_LOG="$LOGS_DIR/outgoing-messages-$DATE.log"

if [ ! -f "$INCOMING_LOG" ] && [ ! -f "$OUTGOING_LOG" ]; then
    echo "❌ Aucun fichier de log trouvé pour la date: $DATE"
    echo "📁 Fichiers disponibles:"
    ls -la $LOGS_DIR/*.log | grep -E "(incoming|outgoing)" | tail -5
    exit 1
fi

echo "📈 STATISTIQUES DES MESSAGES:"
echo ""

# Messages entrants
if [ -f "$INCOMING_LOG" ]; then
    INCOMING_TOTAL=$(grep -c "MESSAGE RECEIVED" "$INCOMING_LOG" 2>/dev/null || echo 0)
    PRIVATE_MESSAGES=$(grep -c "PRIVATE MESSAGE" "$INCOMING_LOG" 2>/dev/null || echo 0)
    GROUP_MESSAGES=$(grep -c "GROUP MESSAGE" "$INCOMING_LOG" 2>/dev/null || echo 0)
    MEDIA_MESSAGES=$(grep -c "MEDIA MESSAGE" "$INCOMING_LOG" 2>/dev/null || echo 0)
    PROCESSING_ERRORS=$(grep -c "MESSAGE PROCESSING FAILED" "$LOGS_DIR/error-$DATE.log" 2>/dev/null || echo 0)
else
    INCOMING_TOTAL=0
    PRIVATE_MESSAGES=0
    GROUP_MESSAGES=0
    MEDIA_MESSAGES=0
    PROCESSING_ERRORS=0
fi

# Messages sortants
if [ -f "$OUTGOING_LOG" ]; then
    OUTGOING_TOTAL=$(grep -c "MESSAGE SENT SUCCESSFULLY" "$OUTGOING_LOG" 2>/dev/null || echo 0)
    AI_RESPONSES=$(grep -c "AI RESPONSE SENT" "$OUTGOING_LOG" 2>/dev/null || echo 0)
    MANUAL_SENDS=$(grep -c "MESSAGE SENDING" "$OUTGOING_LOG" 2>/dev/null || echo 0)
else
    OUTGOING_TOTAL=0
    AI_RESPONSES=0
    MANUAL_SENDS=0
fi

echo "📨 Messages entrants: $INCOMING_TOTAL"
echo "   👤 Messages privés: $PRIVATE_MESSAGES"
echo "   👥 Messages de groupe: $GROUP_MESSAGES"
echo "   📎 Messages avec média: $MEDIA_MESSAGES"
echo ""
echo "📤 Messages sortants: $OUTGOING_TOTAL"
echo "   🤖 Réponses IA: $AI_RESPONSES"
echo "   📝 Envois manuels: $MANUAL_SENDS"
echo ""
echo "❌ Erreurs de traitement: $PROCESSING_ERRORS"

echo ""
echo "🕐 ACTIVITÉ PAR HEURE (Messages entrants):"
echo ""

# Analyse par heure des messages entrants
if [ -f "$INCOMING_LOG" ]; then
    for hour in {00..23}; do
        count=$(grep "MESSAGE RECEIVED" "$INCOMING_LOG" 2>/dev/null | grep "\"timestamp\":\"[^\"]*$DATE $hour:" | wc -l)
        if [ $count -gt 0 ]; then
            printf "%s:00 | %3d messages | " "$hour" "$count"
            # Barre visuelle
            for ((i=1; i<=count && i<=20; i++)); do printf "█"; done
            printf "\n"
        fi
    done
fi

echo ""
echo "📞 TOP CONTACTS (messages entrants):"
echo ""

# Top contacts pour les messages entrants
if [ -f "$INCOMING_LOG" ]; then
    grep "MESSAGE RECEIVED" "$INCOMING_LOG" 2>/dev/null | \
    grep -o '"from":"[^"]*"' | \
    sort | uniq -c | sort -nr | head -10 | \
    while read count from; do
        contact=$(echo $from | cut -d'"' -f4 | cut -d'@' -f1)
        printf "%3d messages de %s\n" "$count" "$contact"
    done
fi

echo ""
echo "🔍 DERNIERS MESSAGES ENTRANTS:"
echo ""

# Derniers messages entrants
if [ -f "$INCOMING_LOG" ]; then
    grep "MESSAGE RECEIVED" "$INCOMING_LOG" 2>/dev/null | tail -5 | \
    while IFS= read -r line; do
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        from=$(echo "$line" | grep -o '"from":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
        body=$(echo "$line" | grep -o '"body":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
        echo "[$timestamp] De $from: $body..."
    done
fi

echo ""
echo "🤖 DERNIÈRES RÉPONSES IA ENVOYÉES:"
echo ""

# Dernières réponses IA
if [ -f "$OUTGOING_LOG" ]; then
    grep "AI RESPONSE SENT" "$OUTGOING_LOG" 2>/dev/null | tail -5 | \
    while IFS= read -r line; do
        timestamp=$(echo "$line" | grep -o '"timestamp":"[^"]*"' | cut -d'"' -f4)
        to=$(echo "$line" | grep -o '"to":"[^"]*"' | cut -d'"' -f4 | cut -d'@' -f1)
        response=$(echo "$line" | grep -o '"responseText":"[^"]*"' | cut -d'"' -f4 | cut -c1-50)
        echo "[$timestamp] Vers $to: $response..."
    done
fi

echo ""
echo "✅ Analyse terminée!"
echo ""
echo "💡 Pour surveiller les messages en temps réel:"
echo "   ./monitor-incoming.sh    # Messages entrants seulement"
echo "   ./monitor-outgoing.sh    # Messages sortants seulement"
echo "   ./monitor-all-messages.sh # Tous les messages"
echo ""
echo "💡 Pour voir les logs bruts:"
echo "   tail -f logs/incoming-messages-$DATE.log"
echo "   tail -f logs/outgoing-messages-$DATE.log"
