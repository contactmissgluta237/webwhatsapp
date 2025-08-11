#!/bin/bash

# Script pour exécuter tous les tests du profil
# Usage: ./run-profile-tests.sh [options]

echo "🧪 Exécution des tests du profil..."
echo "=================================="

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m' 
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les résultats
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

# Tests unitaires du composant ProfileForm
echo -e "${BLUE}📋 Tests unitaires - Composant ProfileForm${NC}"
php artisan test tests/Unit/Livewire/Shared/ProfileFormTest.php --stop-on-failure
PROFILE_FORM_RESULT=$?
print_result $PROFILE_FORM_RESULT "Tests du composant ProfileForm"

echo ""

# Tests des Request classes
echo -e "${BLUE}📋 Tests unitaires - Request Classes${NC}"

echo -e "${YELLOW}  → UpdateProfileRequest${NC}"
php artisan test tests/Unit/Http/Requests/Profile/UpdateProfileRequestTest.php --stop-on-failure
UPDATE_PROFILE_RESULT=$?
print_result $UPDATE_PROFILE_RESULT "Tests UpdateProfileRequest"

echo -e "${YELLOW}  → UpdatePasswordRequest${NC}"
php artisan test tests/Unit/Http/Requests/Profile/UpdatePasswordRequestTest.php --stop-on-failure
UPDATE_PASSWORD_RESULT=$?
print_result $UPDATE_PASSWORD_RESULT "Tests UpdatePasswordRequest"

echo -e "${YELLOW}  → UpdateAvatarRequest${NC}"
php artisan test tests/Unit/Http/Requests/Profile/UpdateAvatarRequestTest.php --stop-on-failure
UPDATE_AVATAR_RESULT=$?
print_result $UPDATE_AVATAR_RESULT "Tests UpdateAvatarRequest"

echo ""

# Tests fonctionnels (Feature)
echo -e "${BLUE}📋 Tests fonctionnels - Intégration${NC}"
php artisan test tests/Feature/Profile/ProfileManagementTest.php --stop-on-failure
PROFILE_MANAGEMENT_RESULT=$?
print_result $PROFILE_MANAGEMENT_RESULT "Tests d'intégration du profil"

echo ""
echo "=================================="

# Calcul du résultat global
TOTAL_RESULT=$((PROFILE_FORM_RESULT + UPDATE_PROFILE_RESULT + UPDATE_PASSWORD_RESULT + UPDATE_AVATAR_RESULT + PROFILE_MANAGEMENT_RESULT))

if [ $TOTAL_RESULT -eq 0 ]; then
    echo -e "${GREEN}🎉 Tous les tests du profil sont passés avec succès !${NC}"
    exit 0
else
    echo -e "${RED}💥 Certains tests ont échoué. Voir les détails ci-dessus.${NC}"
    exit 1
fi