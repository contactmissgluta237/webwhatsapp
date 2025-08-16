#!/bin/bash

# ========================================
# Configuration des Sessions WhatsApp
# ========================================

# Session 1 - Premier numéro
export SESSION_1_ID="session_2_17551942013587_6f8c361f"
export SESSION_1_PHONE="237676636794"

# Session 2 - Deuxième numéro 
export SESSION_2_ID="session_2_17552599417542_942a2d5a"
export SESSION_2_PHONE="23755332183"

# URL de l'API Node.js
export NODE_API_URL="http://localhost:3000/api/bridge"

# ========================================
# Fonctions utilitaires
# ========================================

# Envoyer un message de la Session 1 vers Session 2
send_from_session1_to_session2() {
    local message="$1"
    echo "📤 Envoi depuis $SESSION_1_PHONE vers $SESSION_2_PHONE"
    echo "💬 Message: $message"
    
    curl -X POST "$NODE_API_URL/send-message" \
        -H "Content-Type: application/json" \
        -d "{\"session_id\": \"$SESSION_1_ID\", \"to\": \"$SESSION_2_PHONE\", \"message\": \"$message\"}"
    echo ""
}

# Envoyer un message de la Session 2 vers Session 1
send_from_session2_to_session1() {
    local message="$1"
    echo "📤 Envoi depuis $SESSION_2_PHONE vers $SESSION_1_PHONE"
    echo "💬 Message: $message"
    
    curl -X POST "$NODE_API_URL/send-message" \
        -H "Content-Type: application/json" \
        -d "{\"session_id\": \"$SESSION_2_ID\", \"to\": \"$SESSION_1_PHONE\", \"message\": \"$message\"}"
    echo ""
}

# Vérifier le statut des sessions
check_sessions() {
    echo "🔍 Statut des sessions connectées:"
    curl -s http://localhost:3000/api/sessions | jq '.sessions[] | select(.status=="connected") | {sessionId, phoneNumber, status, lastActivity}'
}

# Voir les derniers messages entrants
show_incoming_messages() {
    local count=${1:-5}
    echo "📨 Derniers $count messages entrants:"
    tail -$count logs/incoming-messages-2025-08-15.log | jq '.'
}

# Voir les derniers messages sortants
show_outgoing_messages() {
    local count=${1:-5}
    echo "📤 Derniers $count messages sortants:"
    tail -$count logs/outgoing-messages-2025-08-15.log | jq '.'
}

# Monitorer les logs en temps réel
monitor_all_logs() {
    echo "👀 Monitoring des logs en temps réel (Ctrl+C pour arrêter)..."
    tail -f logs/incoming-messages-2025-08-15.log logs/outgoing-messages-2025-08-15.log
}

# ========================================
# Tests rapides
# ========================================

# Test du système de logs seulement
test_logging_system() {
    echo "🧪 Test du système de logs..."
    
    # Test direct du logger
    node -e "
    const logger = require('./src/config/logger');
    logger.outgoingMessage('TEST LOG SYSTEM', {
        sessionId: 'test_session_$(date +%s)',
        to: 'test_number',
        messageLength: 15,
        userId: 999
    });
    console.log('✅ Test logger terminé');
    "
    
    echo "📋 Dernière entrée outgoing:"
    tail -1 logs/outgoing-messages-2025-08-15.log | jq '.'
}

# Test complet d'envoi bidirectionnel
test_bidirectional_messaging() {
    echo "🧪 Test bidirectionnel des messages..."
    
    # Vérifier d'abord les sessions
    echo "🔍 Vérification des sessions..."
    local sessions_count=$(curl -s http://localhost:3000/api/sessions | jq '.sessions[] | select(.status=="connected")' | wc -l)
    
    if [ "$sessions_count" -lt 2 ]; then
        echo "⚠️  Seulement $sessions_count session(s) connectée(s). Il faut 2 sessions pour le test bidirectionnel."
        echo "📱 Sessions disponibles:"
        curl -s http://localhost:3000/api/sessions | jq '.sessions[] | {sessionId, status, phoneNumber}'
        return 1
    fi
    
    # Message de Session1 vers Session2
    send_from_session1_to_session2 "Test envoi 1→2 - $(date '+%H:%M:%S')"
    sleep 2
    
    # Message de Session2 vers Session1
    send_from_session2_to_session1 "Test envoi 2→1 - $(date '+%H:%M:%S')"
    sleep 2
    
    echo "✅ Tests envoyés, vérifiez les logs!"
    echo "📊 Résumé des logs:"
    echo "   - Messages sortants: $(wc -l < logs/outgoing-messages-2025-08-15.log) entrées"
    echo "   - Messages entrants: $(wc -l < logs/incoming-messages-2025-08-15.log) entrées"
}

# Afficher l'aide
show_help() {
    echo "========================================="
    echo "Scripts de test WhatsApp Bridge"
    echo "========================================="
    echo ""
    echo "Chargement du fichier:"
    echo "  source sessions-config.sh"
    echo ""
    echo "Fonctions disponibles:"
    echo "  send_from_session1_to_session2 'message'  - Envoie de $SESSION_1_PHONE vers $SESSION_2_PHONE"
    echo "  send_from_session2_to_session1 'message'  - Envoie de $SESSION_2_PHONE vers $SESSION_1_PHONE"
    echo "  check_sessions                            - Vérifier le statut des sessions"
    echo "  show_incoming_messages [count]            - Afficher les derniers messages entrants"
    echo "  show_outgoing_messages [count]            - Afficher les derniers messages sortants"
    echo "  monitor_all_logs                          - Monitorer les logs en temps réel"
    echo "  test_logging_system                      - Tester seulement le système de logs
  test_bidirectional_messaging              - Test complet bidirectionnel"
    echo "  show_help                                 - Afficher cette aide"
    echo ""
    echo "Variables disponibles:"
    echo "  SESSION_1_ID:    $SESSION_1_ID"
    echo "  SESSION_1_PHONE: $SESSION_1_PHONE"
    echo "  SESSION_2_ID:    $SESSION_2_ID"
    echo "  SESSION_2_PHONE: $SESSION_2_PHONE"
    echo "========================================="
}

# Afficher l'aide au chargement si le script est sourcé
if [[ "${BASH_SOURCE[0]}" != "${0}" ]]; then
    echo "✅ Configuration des sessions chargée!"
    show_help
fi
