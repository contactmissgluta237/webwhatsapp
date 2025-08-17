#!/bin/bash

# Script de test pour vérifier la séparation des logs incoming/outgoing
# Test de communication croisée entre deux sessions connectées

# Configuration des couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration du serveur
SERVER_URL="http://localhost:3000"
LOG_DIR="./logs"

# Sessions connectées (basées sur l'API)
SESSION_A="session_2_17552805081829_3d3b6b43"
PHONE_A="237676636794"

SESSION_B="session_2_17552805689246_e3929ee8"
PHONE_B="23755332183"  # Corrigé avec le bon numéro de active_sessions.json

echo -e "${BLUE}=== Test de Communication Croisée WhatsApp ===${NC}"
echo -e "${BLUE}Session A: ${SESSION_A} (${PHONE_A})${NC}"
echo -e "${BLUE}Session B: ${SESSION_B} (${PHONE_B})${NC}"
echo ""

# Fonction pour nettoyer les logs
clean_logs() {
    echo -e "${YELLOW}🧹 Nettoyage des anciens logs...${NC}"
    rm -f ${LOG_DIR}/incoming-messages-$(date +%Y-%m-%d).log
    rm -f ${LOG_DIR}/outgoing-messages-$(date +%Y-%m-%d).log
    echo -e "${GREEN}✅ Logs nettoyés${NC}"
    echo ""
}

# Fonction pour envoyer un message
send_message() {
    local from_session=$1
    local to_phone=$2
    local message=$3
    local direction=$4
    
    echo -e "${BLUE}📤 Envoi de ${from_session} vers ${to_phone}${NC}"
    echo -e "${BLUE}Message: ${message}${NC}"
    
    response=$(curl -s -X POST "${SERVER_URL}/api/sessions/${from_session}/send" \
        -H "Content-Type: application/json" \
        -d "{\"to\": \"${to_phone}\", \"message\": \"${message}\"}")
    
    if echo "$response" | grep -q '"success":true'; then
        echo -e "${GREEN}✅ Message envoyé avec succès${NC}"
    else
        echo -e "${RED}❌ Échec de l'envoi du message${NC}"
        echo "$response"
    fi
    echo ""
}

# Fonction pour attendre et vérifier les logs
check_logs() {
    local log_type=$1
    local expected_count=$2
    local description=$3
    
    echo -e "${YELLOW}📋 Vérification des logs ${log_type}...${NC}"
    sleep 2
    
    log_file="${LOG_DIR}/${log_type}-messages-$(date +%Y-%m-%d).log"
    
    if [ -f "$log_file" ]; then
        count=$(wc -l < "$log_file")
        echo -e "${GREEN}📄 Fichier ${log_type}: ${count} entrées${NC}"
        
        if [ $count -gt 0 ]; then
            echo -e "${BLUE}Dernières entrées:${NC}"
            tail -3 "$log_file" | while read line; do
                echo -e "${BLUE}  ${line}${NC}"
            done
        fi
    else
        echo -e "${RED}❌ Fichier ${log_type} n'existe pas encore${NC}"
    fi
    echo ""
}

# Fonction pour afficher un résumé des logs
show_log_summary() {
    echo -e "${YELLOW}📊 RÉSUMÉ DES LOGS${NC}"
    echo "=================================="
    
    incoming_file="${LOG_DIR}/incoming-messages-$(date +%Y-%m-%d).log"
    outgoing_file="${LOG_DIR}/outgoing-messages-$(date +%Y-%m-%d).log"
    
    if [ -f "$incoming_file" ]; then
        incoming_count=$(wc -l < "$incoming_file")
        echo -e "${GREEN}📨 Messages entrants: ${incoming_count}${NC}"
    else
        echo -e "${RED}📨 Messages entrants: 0 (fichier inexistant)${NC}"
    fi
    
    if [ -f "$outgoing_file" ]; then
        outgoing_count=$(wc -l < "$outgoing_file")
        echo -e "${GREEN}📤 Messages sortants: ${outgoing_count}${NC}"
    else
        echo -e "${RED}📤 Messages sortants: 0 (fichier inexistant)${NC}"
    fi
    echo ""
}

# Fonction principale de test
run_test() {
    echo -e "${YELLOW}🚀 Début du test de communication croisée${NC}"
    echo ""
    
    # Nettoyage initial
    clean_logs
    
    # Test 1: Session A -> Session B
    echo -e "${YELLOW}=== TEST 1: ${SESSION_A} -> ${PHONE_B} ===${NC}"
    send_message "$SESSION_A" "$PHONE_B" "Salut depuis Session A ! Ceci est un test de message sortant." "A_to_B"
    
    # Attendre un peu pour le traitement
    echo -e "${YELLOW}⏳ Attente 5 secondes pour le traitement...${NC}"
    sleep 5
    
    # Vérifier les logs après le premier message
    check_logs "outgoing" 1 "Premier message sortant"
    check_logs "incoming" 0 "Pas encore de messages entrants"
    
    # Test 2: Session B -> Session A  
    echo -e "${YELLOW}=== TEST 2: ${SESSION_B} -> ${PHONE_A} ===${NC}"
    send_message "$SESSION_B" "$PHONE_A" "Bonjour depuis Session B ! Test de réponse automatique." "B_to_A"
    
    # Attendre pour le traitement et les réponses AI potentielles
    echo -e "${YELLOW}⏳ Attente 8 secondes pour le traitement et les réponses AI...${NC}"
    sleep 8
    
    # Vérifier les logs finaux
    check_logs "outgoing" 2 "Tous les messages sortants"
    check_logs "incoming" 2 "Messages entrants reçus"
    
    # Afficher le résumé
    show_log_summary
    
    echo -e "${GREEN}✅ Test terminé !${NC}"
    echo ""
    echo -e "${BLUE}💡 Pour voir les logs détaillés:${NC}"
    echo -e "${BLUE}   tail -f ${LOG_DIR}/incoming-messages-$(date +%Y-%m-%d).log${NC}"
    echo -e "${BLUE}   tail -f ${LOG_DIR}/outgoing-messages-$(date +%Y-%m-%d).log${NC}"
}

# Exécution du test
run_test
