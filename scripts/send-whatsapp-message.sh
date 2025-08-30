#!/bin/bash

# Script pour tester l'envoi de messages WhatsApp via Node.js
# Usage: ./send-whatsapp-message.sh [session_id] [phone_number] [message]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_URL="http://localhost:3000"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les logs
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Paramètres par défaut
DEFAULT_SESSION="session_2_17564998223800_71794c46"
DEFAULT_PHONE="23755332183"
DEFAULT_MESSAGE="Test message depuis script - $(date '+%Y-%m-%d %H:%M:%S')"

# Récupération des paramètres
SESSION_ID="${1:-$DEFAULT_SESSION}"
PHONE_NUMBER="${2:-$DEFAULT_PHONE}"
MESSAGE="${3:-$DEFAULT_MESSAGE}"

# Formatage du numéro de téléphone
if [[ ! "$PHONE_NUMBER" == *"@c.us" ]]; then
    PHONE_NUMBER="${PHONE_NUMBER}@c.us"
fi

log_info "🚀 Envoi de message WhatsApp"
log_info "📱 Session: $SESSION_ID"
log_info "📞 Destinataire: $PHONE_NUMBER"
log_info "💬 Message: $MESSAGE"

# Vérification de la connexion Node.js
log_info "🔍 Vérification de la connexion Node.js..."
if ! curl -s "$BASE_URL/health" > /dev/null; then
    log_error "❌ Impossible de se connecter à Node.js sur $BASE_URL"
    log_error "Assurez-vous que le serveur Node.js est démarré"
    exit 1
fi

log_info "✅ Connexion Node.js OK"

# Vérification du statut de la session
log_info "🔍 Vérification du statut de la session..."
SESSION_STATUS=$(curl -s "$BASE_URL/api/sessions/$SESSION_ID/status" | jq -r '.status // "unknown"')

if [ "$SESSION_STATUS" != "connected" ]; then
    log_warn "⚠️  Session status: $SESSION_STATUS"
    log_warn "La session pourrait ne pas être prête pour envoyer des messages"
fi

# Envoi du message
log_info "📤 Envoi du message..."

RESPONSE=$(curl -s -X POST "$BASE_URL/api/bridge/send-message" \
    -H "Content-Type: application/json" \
    -d "{
        \"session_id\": \"$SESSION_ID\",
        \"to\": \"$PHONE_NUMBER\",
        \"message\": \"$MESSAGE\"
    }")

# Vérification de la réponse
if echo "$RESPONSE" | jq -e '.success == true' > /dev/null; then
    log_info "✅ Message envoyé avec succès!"
    echo "$RESPONSE" | jq '.'
else
    log_error "❌ Échec de l'envoi du message"
    echo "$RESPONSE" | jq '.'
    exit 1
fi

log_info "🎉 Script terminé avec succès!"
