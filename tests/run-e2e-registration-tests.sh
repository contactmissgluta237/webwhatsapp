#!/bin/bash

# Script pour exécuter tous les tests E2E d'inscription
# Usage: ./tests/run-e2e-registration-tests.sh

echo "🚀 Exécution des tests E2E d'inscription..."
echo "=============================================="
echo ""

# Vérifier que Dusk est configuré
if [ ! -f ".env.dusk.local" ] && [ ! -f ".env.dusk" ]; then
    echo "⚠️  Attention: Fichier .env.dusk.local ou .env.dusk non trouvé"
    echo "   Assurez-vous d'avoir configuré l'environnement Dusk"
    echo ""
fi

# Vérifier que Chrome est installé
if ! command -v google-chrome &> /dev/null && ! command -v chromium-browser &> /dev/null; then
    echo "⚠️  Attention: Chrome/Chromium non détecté"
    echo "   Les tests Dusk nécessitent Chrome pour fonctionner"
    echo ""
fi

echo "📋 Tests E2E - Flux d'inscription complet"
echo "----------------------------------------"
php artisan dusk tests/Browser/RegistrationFlowTest.php --group=registration

echo ""
echo "📋 Tests E2E - Activation de compte"
echo "-----------------------------------"
php artisan dusk tests/Browser/AccountActivationTest.php --group=activation

echo ""
echo "🎯 Résumé des tests E2E d'inscription"
echo "====================================="
php artisan dusk tests/Browser/RegistrationFlowTest.php tests/Browser/AccountActivationTest.php

echo ""
echo "✅ Tests E2E d'inscription terminés !"