#!/bin/bash

# Script pour lancer les tests du simulateur WhatsApp
# Usage: ./tests/run-simulator-tests.sh

echo "🎬 Lancement des tests du simulateur de conversation WhatsApp..."
echo "=================================================="

# Tests unitaires/fonctionnels Livewire
echo "📝 Tests fonctionnels du composant Livewire..."
php artisan test --testsuite=Feature --filter=ConversationSimulatorTest

if [ $? -eq 0 ]; then
    echo "✅ Tests fonctionnels réussis!"
    
    # Tests d'intégration E2E
    echo ""
    echo "🔄 Tests d'intégration E2E..."
    php artisan test tests/E2E/ConversationSimulatorIntegrationTest.php
    
    if [ $? -eq 0 ]; then
        echo "✅ Tests E2E réussis!"
        
        # Tests de comparaison simulateur vs chat réel
        echo ""
        echo "🤖 Tests de comparaison simulateur vs chat réel..."
        php artisan test --testsuite=Feature --filter=SimulatorVsRealChatTest
        
        if [ $? -eq 0 ]; then
            echo ""
            echo "🎉 Tous les tests du simulateur ont réussi!"
            echo "Le simulateur est prêt pour la production! 🚀"
        else
            echo "❌ Erreur dans les tests de comparaison"
            exit 1
        fi
    else
        echo "❌ Erreur dans les tests E2E"
        exit 1
    fi
else
    echo "❌ Erreur dans les tests fonctionnels"
    exit 1
fi