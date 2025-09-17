#!/bin/bash

echo "🧪 Validation des Tests Fonctionnels"
echo "=================================="

# Compter le nombre de tests
echo "📊 Statistiques:"
echo "- Fichiers de tests créés: $(find tests/Feature -name "*Test.php" -newer tests/Feature/Auth/LoginTest.php | wc -l)"
echo "- Total méthodes de test: $(grep -r "#\[Test\]" tests/Feature | wc -l)"

# Vérifier la syntaxe PHP
echo ""
echo "🔍 Vérification syntaxe PHP:"
find tests/Feature -name "*.php" -newer tests/Feature/Auth/LoginTest.php -exec php -l {} \; | grep -c "No syntax errors detected"

# Vérifier les imports et namespaces
echo ""
echo "📝 Vérification des patterns:"
echo "- Fichiers avec 'declare(strict_types=1)': $(grep -r "declare(strict_types=1)" tests/Feature --include="*.php" | wc -l)"
echo "- Fichiers avec 'final class': $(grep -r "final class" tests/Feature --include="*.php" | wc -l)"
echo "- Fichiers avec 'use RefreshDatabase': $(grep -r "use RefreshDatabase" tests/Feature --include="*.php" | wc -l)"

# Vérifier les dépendances
echo ""
echo "🔗 Vérification des dépendances:"
echo "- Tests utilisant User::factory(): $(grep -r "User::factory()" tests/Feature --include="*.php" | wc -l)"
echo "- Tests utilisant Ticket::factory(): $(grep -r "Ticket::factory()" tests/Feature --include="*.php" | wc -l)"
echo "- Tests utilisant WhatsAppAccount::factory(): $(grep -r "WhatsAppAccount::factory()" tests/Feature --include="*.php" | wc -l)"

# Vérifier les types de tests
echo ""
echo "🎯 Types de tests:"
echo "- Tests d'autorisation (assertForbidden): $(grep -r "assertForbidden" tests/Feature --include="*.php" | wc -l)"
echo "- Tests de redirection (assertRedirect): $(grep -r "assertRedirect" tests/Feature --include="*.php" | wc -l)"
echo "- Tests de vues (assertViewIs): $(grep -r "assertViewIs" tests/Feature --include="*.php" | wc -l)"
echo "- Tests JSON (assertJson): $(grep -r "assertJson" tests/Feature --include="*.php" | wc -l)"

echo ""
echo "✅ Validation terminée!"