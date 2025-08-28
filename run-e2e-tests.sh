#!/bin/bash

# Script pour exécuter les tests E2E de manière organisée
# Usage: ./run-e2e-tests.sh [category]
# Catégories: auth, billing, integration, whatsapp, all

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function pour afficher les titres
print_title() {
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║  $1${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
}

# Function pour afficher les sections
print_section() {
    echo -e "${YELLOW}📂 $1${NC}"
}

# Fonction pour exécuter les tests
run_tests() {
    local category=$1
    local path=$2
    
    print_section "Tests $category"
    echo -e "${GREEN}Exécution: php artisan test $path${NC}"
    php artisan test "$path" || echo -e "${RED}❌ Tests $category échoués${NC}"
    echo ""
}

# Vérifier les paramètres
CATEGORY=${1:-all}

print_title "🧪 EXÉCUTION TESTS E2E - CATÉGORIE: $CATEGORY"

case $CATEGORY in
    "auth"|"authentication")
        run_tests "Authentication" "tests/E2E/Auth/"
        ;;
    "billing"|"facturation")
        run_tests "Billing" "tests/E2E/Billing/"
        run_tests "Billing System" "tests/E2E/BillingSystemE2ETest.php"
        ;;
    "integration")
        run_tests "Integration" "tests/E2E/Integration/"
        ;;
    "whatsapp")
        print_section "Tests WhatsApp (Legacy Scripts)"
        echo -e "${YELLOW}⚠️  Les tests WhatsApp utilisent des scripts PHP legacy${NC}"
        echo -e "${YELLOW}💡 À migrer vers PHPUnit : voir tests/E2E/WhatsApp/README.md${NC}"
        ;;
    "all"|"tout")
        run_tests "Authentication" "tests/E2E/Auth/"
        run_tests "Billing" "tests/E2E/Billing/"
        run_tests "Billing System" "tests/E2E/BillingSystemE2ETest.php"
        run_tests "Integration" "tests/E2E/Integration/"
        echo ""
        print_section "Résumé des scripts WhatsApp Legacy"
        echo -e "${YELLOW}📁 tests/E2E/WhatsApp/ contient des scripts PHP legacy${NC}"
        echo -e "${YELLOW}📁 tests/E2E/Legacy/ contient d'anciens tests à migrer${NC}"
        ;;
    *)
        echo -e "${RED}❌ Catégorie inconnue: $CATEGORY${NC}"
        echo -e "${GREEN}Catégories disponibles:${NC}"
        echo "  auth, authentication - Tests d'authentification"
        echo "  billing, facturation - Tests de facturation"
        echo "  integration - Tests d'intégration"  
        echo "  whatsapp - Information sur les scripts WhatsApp legacy"
        echo "  all, tout - Tous les tests"
        exit 1
        ;;
esac

print_title "✅ EXÉCUTION TERMINÉE"