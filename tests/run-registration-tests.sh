#!/bin/bash

echo "🚀 Exécution des tests d'inscription..."
echo "=================================="

echo ""
echo "📋 Tests Unitaires - RegisterRequest"
echo "-----------------------------------"
php artisan test tests/Unit/Http/Requests/Auth/RegisterRequestTest.php -v

echo ""
echo "📋 Tests Unitaires - OtpService"
echo "-------------------------------"
php artisan test tests/Unit/Services/Auth/OtpServiceTest.php -v

echo ""
echo "📋 Tests Unitaires - RegisterForm (Livewire)"
echo "--------------------------------------------"
php artisan test tests/Unit/Livewire/Auth/RegisterFormTest.php -v

echo ""
echo "📋 Tests Fonctionnels - Registration"
echo "------------------------------------"
php artisan test tests/Feature/Auth/RegistrationTest.php -v

echo ""
echo "📋 Tests Fonctionnels - Activation"
echo "----------------------------------"
php artisan test tests/Feature/Auth/ActivationTest.php -v

echo ""
echo "🎯 Résumé de tous les tests d'inscription"
echo "========================================="
php artisan test tests/Unit/Http/Requests/Auth/RegisterRequestTest.php tests/Unit/Services/Auth/OtpServiceTest.php tests/Unit/Livewire/Auth/RegisterFormTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/ActivationTest.php

echo ""
echo "✅ Tests d'inscription terminés !"