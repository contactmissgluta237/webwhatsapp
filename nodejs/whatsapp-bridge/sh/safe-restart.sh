#!/bin/bash

# Script de redémarrage sécurisé avec sauvegarde et restauration des sessions
# Ce script permet de redémarrer le service Node.js sans perdre les sessions

# Configuration des couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SERVICE_DIR="/home/douglas/Documents/AfrikSolutions/Projects/web-whatsapp/nodejs/whatsapp-bridge"
PID_FILE="$SERVICE_DIR/service.pid"
SESSION_FILE="$SERVICE_DIR/src/data/active_sessions.json"

echo -e "${BLUE}=== Redémarrage Sécurisé du Service WhatsApp ===${NC}"
echo ""

# Fonction pour sauvegarder les sessions avant arrêt
save_sessions() {
    echo -e "${YELLOW}📱 Déclenchement de la sauvegarde des sessions...${NC}"
    
    # Appel API pour forcer la sauvegarde
    response=$(curl -s -X POST http://localhost:3000/api/sessions/save)
    if echo "$response" | grep -q '"success":true'; then
        echo -e "${GREEN}✅ Sessions sauvegardées avec succès${NC}"
        
        # Vérifier que le fichier de session existe et est récent
        if [ -f "$SESSION_FILE" ]; then
            session_count=$(cat "$SESSION_FILE" | jq '. | length' 2>/dev/null || echo "0")
            echo -e "${GREEN}📊 $session_count sessions trouvées dans le fichier${NC}"
            
            # Afficher un aperçu des sessions sauvegardées
            echo -e "${BLUE}Sessions sauvegardées:${NC}"
            cat "$SESSION_FILE" | jq -r 'to_entries[] | "  - \(.key): \(.value.phoneNumber // "N/A") (\(.value.status))"' 2>/dev/null || echo "  Erreur lors de la lecture du fichier"
        else
            echo -e "${RED}⚠️ Fichier de sessions non trouvé${NC}"
        fi
    else
        echo -e "${RED}❌ Échec de la sauvegarde des sessions${NC}"
        echo "Réponse: $response"
        echo -e "${YELLOW}Continuer quand même ? (y/N)${NC}"
        read -r confirmation
        if [[ ! "$confirmation" =~ ^[Yy]$ ]]; then
            echo -e "${RED}Arrêt du redémarrage${NC}"
            exit 1
        fi
    fi
    echo ""
}

# Fonction pour arrêter le service
stop_service() {
    echo -e "${YELLOW}🛑 Arrêt du service Node.js...${NC}"
    
    # Trouver le PID du processus Node.js
    node_pid=$(ps aux | grep "node src/server.js" | grep -v grep | awk '{print $2}')
    
    if [ -n "$node_pid" ]; then
        echo -e "${YELLOW}Processus Node.js trouvé (PID: $node_pid)${NC}"
        
        # Arrêt gracieux (SIGTERM)
        echo -e "${YELLOW}Envoi de SIGTERM...${NC}"
        kill -TERM "$node_pid"
        
        # Attendre 10 secondes pour l'arrêt gracieux
        echo -e "${YELLOW}Attente de l'arrêt gracieux (10s)...${NC}"
        sleep 10
        
        # Vérifier si le processus est toujours là
        if ps -p "$node_pid" > /dev/null 2>&1; then
            echo -e "${YELLOW}Processus toujours actif, arrêt forcé (SIGKILL)...${NC}"
            kill -KILL "$node_pid"
            sleep 2
        fi
        
        echo -e "${GREEN}✅ Service arrêté${NC}"
    else
        echo -e "${YELLOW}⚠️ Aucun processus Node.js trouvé${NC}"
    fi
    echo ""
}

# Fonction pour démarrer le service
start_service() {
    echo -e "${YELLOW}🚀 Démarrage du service Node.js...${NC}"
    
    cd "$SERVICE_DIR" || {
        echo -e "${RED}❌ Impossible d'accéder au répertoire du service${NC}"
        exit 1
    }
    
    # Démarrer le service en arrière-plan
    nohup node src/server.js > service.log 2>&1 &
    service_pid=$!
    
    echo "$service_pid" > "$PID_FILE"
    echo -e "${GREEN}✅ Service démarré (PID: $service_pid)${NC}"
    
    # Attendre que le service soit prêt
    echo -e "${YELLOW}⏳ Attente du démarrage du service...${NC}"
    sleep 5
    
    # Vérifier que le service répond
    max_attempts=12  # 60 secondes maximum
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if curl -s http://localhost:3000/api/sessions > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Service prêt et répond aux requêtes${NC}"
            break
        else
            echo -e "${YELLOW}⏳ Tentative $((attempt + 1))/$max_attempts...${NC}"
            sleep 5
            attempt=$((attempt + 1))
        fi
    done
    
    if [ $attempt -eq $max_attempts ]; then
        echo -e "${RED}❌ Le service ne répond pas après 60 secondes${NC}"
        exit 1
    fi
    echo ""
}

# Fonction pour vérifier la restauration
check_restoration() {
    echo -e "${YELLOW}🔍 Vérification de la restauration des sessions...${NC}"
    
    # Attendre un peu plus pour la restauration
    echo -e "${YELLOW}⏳ Attente de la restauration (15s)...${NC}"
    sleep 15
    
    # Vérifier les sessions restaurées
    response=$(curl -s http://localhost:3000/api/sessions)
    if echo "$response" | grep -q '"success":true'; then
        session_count=$(echo "$response" | jq '.count' 2>/dev/null || echo "0")
        echo -e "${GREEN}✅ $session_count sessions trouvées après restauration${NC}"
        
        if [ "$session_count" -gt 0 ]; then
            echo -e "${BLUE}Sessions restaurées:${NC}"
            echo "$response" | jq -r '.sessions[] | "  - \(.sessionId): \(.phoneNumber // "N/A") (\(.status))"' 2>/dev/null
        fi
    else
        echo -e "${RED}❌ Erreur lors de la vérification des sessions${NC}"
    fi
    echo ""
}

# Fonction principale
main() {
    echo -e "${BLUE}Début du processus de redémarrage sécurisé${NC}"
    echo ""
    
    # Étape 1: Sauvegarder les sessions
    save_sessions
    
    # Étape 2: Arrêter le service
    stop_service
    
    # Étape 3: Démarrer le service
    start_service
    
    # Étape 4: Vérifier la restauration
    check_restoration
    
    echo -e "${GREEN}🎉 Redémarrage terminé avec succès !${NC}"
    echo ""
    echo -e "${BLUE}Pour vérifier l'état des sessions:${NC}"
    echo -e "${BLUE}  curl -s http://localhost:3000/api/sessions | jq${NC}"
    echo ""
    echo -e "${BLUE}Pour voir les logs en temps réel:${NC}"
    echo -e "${BLUE}  tail -f $SERVICE_DIR/service.log${NC}"
    echo -e "${BLUE}  tail -f $SERVICE_DIR/logs/app-$(date +%Y-%m-%d).log${NC}"
}

# Confirmation avant exécution
echo -e "${YELLOW}⚠️ Ce script va redémarrer le service WhatsApp.${NC}"
echo -e "${YELLOW}Les sessions seront sauvegardées et restaurées automatiquement.${NC}"
echo ""
echo -e "${YELLOW}Continuer ? (y/N)${NC}"
read -r confirmation

if [[ "$confirmation" =~ ^[Yy]$ ]]; then
    main
else
    echo -e "${BLUE}Redémarrage annulé${NC}"
    exit 0
fi
