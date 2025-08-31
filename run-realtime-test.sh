#!/bin/bash

echo "========================================="
echo "    TEST TEMPS RÉEL - STORE MESSAGES    "
echo "========================================="
echo ""

echo "🔧 Préparation de l'environnement..."
export APP_ENV=local

echo "🚀 Lancement du test temps réel..."
echo ""

php tests/E2E/RealTimeListenerTest.php

echo ""
echo "========================================="
echo "Pour voir les logs détaillés, consultez:"
echo "- storage/logs/laravel.log"
echo "========================================="