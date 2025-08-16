#!/bin/bash

# Script de redémarrage simple du service WhatsApp
# Utilise les sessions déjà sauvegardées dans le fichier JSON

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Répertoires et fichiers
SERVICE_DIR="/home/douglas/Documents/AfrikSolutions/Projects/web-whatsapp/nodejs/whatsapp-bridge"
PID_FILE="$SERVICE_DIR/service.pid"
SESSION_FILE="$SERVICE_DIR/src/data/active_sessions.json"

echo -e "${BLUE}=== Redémarrage Simple du Service WhatsApp ===${NC}"
echo ""

# Vérifier les sessions existantes
echo -e "${YELLOW}📱 Vérification des sessions existantes...${NC}"
if [ -f "$SESSION_FILE" ]; then
    session_count=$(cat "$SESSION_FILE" | jq '. | length' 2>/dev/null || echo "0")
    echo -e "${GREEN}✅ $session_count sessions trouvées dans le fichier${NC}"
    
    # Afficher un aperçu des sessions
    echo -e "${BLUE}Sessions disponibles:${NC}"
    cat "$SESSION_FILE" | jq -r 'to_entries[] | "  - \(.key): \(.value.phoneNumber // "N/A") (\(.value.status))"' 2>/dev/null || echo "  Erreur lors de la lecture du fichier"
else
    echo -e "${RED}⚠️ Aucun fichier de sessions trouvé${NC}"
    echo -e "${YELLOW}Les sessions devront être recréées après le redémarrage${NC}"
fi

echo ""
echo -e "${YELLOW}⚠️ Ce script va redémarrer le service WhatsApp.${NC}"
echo -e "${YELLOW}Les sessions seront restaurées automatiquement depuis le fichier JSON.${NC}"
echo ""
echo -e "${YELLOW}Continuer ? (y/N)${NC}"
read -r confirmation

if [[ ! "$confirmation" =~ ^[Yy]$ ]]; then
    echo -e "${RED}Arrêt du redémarrage${NC}"
    exit 1
fi

echo -e "${BLUE}Début du processus de redémarrage${NC}"

# Fonction pour arrêter le service
stop_service() {
    echo -e "${YELLOW}🛑 Arrêt du service...${NC}"
    
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if kill -0 "$PID" 2>/dev/null; then
            echo -e "${BLUE}Envoi du signal SIGTERM au processus $PID...${NC}"
            kill -TERM "$PID"
            
            # Attendre que le processus se termine (max 10 secondes)
            for i in {1..10}; do
                if ! kill -0 "$PID" 2>/dev/null; then
                    echo -e "${GREEN}✅ Service arrêté proprement${NC}"
                    rm -f "$PID_FILE"
                    return 0
                fi
                echo -e "${BLUE}Attente de l'arrêt... ($i/10)${NC}"
                sleep 1
            done
            
            # Si le processus ne s'arrête pas, forcer l'arrêt
            echo -e "${YELLOW}Force l'arrêt du processus...${NC}"
            kill -KILL "$PID" 2>/dev/null
            rm -f "$PID_FILE"
            echo -e "${GREEN}✅ Service arrêté (forcé)${NC}"
        else
            echo -e "${YELLOW}⚠️ Processus déjà arrêté${NC}"
            rm -f "$PID_FILE"
        fi
    else
        echo -e "${YELLOW}⚠️ Aucun fichier PID trouvé${NC}"
        # Chercher et tuer les processus Node.js restants
        pkill -f "node.*src/server.js" && echo -e "${GREEN}✅ Processus Node.js arrêtés${NC}"
    fi
}

# Fonction pour démarrer le service
start_service() {
    echo -e "${YELLOW}🚀 Démarrage du service...${NC}"
    
    cd "$SERVICE_DIR" || {
        echo -e "${RED}❌ Impossible d'accéder au répertoire $SERVICE_DIR${NC}"
        exit 1
    }
    
    # Démarrer le service en arrière-plan
    nohup node src/server.js > logs/service.log 2>&1 &
    SERVICE_PID=$!
    
    # Sauvegarder le PID
    echo "$SERVICE_PID" > "$PID_FILE"
    
    echo -e "${GREEN}✅ Service démarré avec PID $SERVICE_PID${NC}"
    
    # Attendre un peu pour que le service s'initialise
    echo -e "${BLUE}Attente de l'initialisation...${NC}"
    sleep 3
}

# Fonction pour vérifier que le service fonctionne
verify_service() {
    echo -e "${YELLOW}🔍 Vérification du service...${NC}"
    
    # Vérifier que le processus tourne
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if kill -0 "$PID" 2>/dev/null; then
            echo -e "${GREEN}✅ Processus en cours d'exécution (PID: $PID)${NC}"
        else
            echo -e "${RED}❌ Processus non trouvé${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Fichier PID non trouvé${NC}"
        return 1
    fi
    
    # Vérifier que l'API répond
    echo -e "${BLUE}Test de l'API...${NC}"
    for i in {1..5}; do
        if curl -s http://localhost:3000/api/status > /dev/null 2>&1; then
            echo -e "${GREEN}✅ API accessible${NC}"
            return 0
        fi
        echo -e "${BLUE}Tentative $i/5 - Attente de l'API...${NC}"
        sleep 2
    done
    
    echo -e "${YELLOW}⚠️ API non accessible, mais le service semble démarré${NC}"
    return 0
}

# Exécution du redémarrage
stop_service
echo ""
start_service
echo ""
verify_service

echo ""
echo -e "${GREEN}🎉 Redémarrage terminé !${NC}"
echo ""
echo -e "${BLUE}Prochaines étapes:${NC}"
echo -e "  1. Les sessions vont se reconnecter automatiquement"
echo -e "  2. Vérifiez les logs: tail -f logs/service.log"
echo -e "  3. Vérifiez les logs de messages pour tester la séparation:"
echo -e "     - Messages entrants: tail -f logs/incoming-messages.log"
echo -e "     - Messages sortants: tail -f logs/outgoing-messages.log"
echo ""
