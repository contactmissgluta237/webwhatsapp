# Tests E2E - Organisation

Ce dossier contient les tests End-to-End (E2E) organisés par domaine fonctionnel.

## Structure

### 📁 **Auth/** - Tests d'authentification
- `CompleteAuthenticationFlowE2ETest.php` - Test complet du parcours utilisateur (inscription → activation → connexion → déconnexion → mot de passe oublié → réinitialisation → reconnexion)

### 📁 **Billing/** - Tests de facturation
- `BaseE2EBillingTest.php` - Test de base pour la facturation
- `BillingSystemE2ETest.php` - Tests du système de facturation complet
- `MailHogE2ETest.php` - Tests des emails avec MailHog
- `MailSendingE2ETest.php` - Tests d'envoi d'emails
- `NotificationE2ETest.php` - Tests des notifications
- `QuotaDebitE2ETest.php` - Tests de débit de quota
- `SimpleMailE2ETest.php` - Tests d'emails simples
- `WalletDebitE2ETest.php` - Tests de débit de portefeuille

### 📁 **Integration/** - Tests d'intégration
- `ConversationSimulatorIntegrationTest.php` - Tests d'intégration du simulateur de conversation

### 📁 **WhatsApp/** - Tests WhatsApp
- `BaseTestIncomingMessage.php` - Test de base pour les messages entrants
- `test-incoming-flow-basic.php` - Flux de base des messages entrants
- `test-incoming-flow-complete.php` - Flux complet des messages entrants
- `test-incoming-flow-conversation.php` - Tests de conversation
- `test-incoming-flow-with-products.php` - Tests avec produits
- `test-whatsapp-currency-formatting.php` - Tests de formatage de devises

### 📁 **Legacy/** - Anciens tests
- `test-currency-registration.php` - Tests d'inscription avec devises
- `test-product-listing-currencies.php` - Tests de liste de produits avec devises

## Conventions

### Nomenclature des fichiers :
- **Tests PHPUnit modernes** : `*E2ETest.php` avec classes et namespaces appropriés
- **Scripts de test legacy** : `test-*.php` (à migrer vers PHPUnit quand possible)

### Namespaces :
- `Tests\E2E\Auth\*` - Tests d'authentification  
- `Tests\E2E\Billing\*` - Tests de facturation
- `Tests\E2E\Integration\*` - Tests d'intégration
- `Tests\E2E\WhatsApp\*` - Tests WhatsApp

## Exécution des tests

```bash
# Tous les tests E2E
php artisan test tests/E2E/

# Tests d'authentification uniquement
php artisan test tests/E2E/Auth/

# Tests de facturation uniquement
php artisan test tests/E2E/Billing/

# Test spécifique
php artisan test tests/E2E/Auth/CompleteAuthenticationFlowE2ETest.php
```

## Bonnes pratiques

1. **Tests isolés** : Chaque test doit pouvoir s'exécuter indépendamment
2. **Données réelles** : Utiliser des données réelles avec nettoyage automatique
3. **Noms explicites** : Les noms de tests doivent être clairs et descriptifs
4. **Cleanup** : Toujours nettoyer les données de test créées
5. **Documentation** : Commenter les étapes complexes ou les comportements spéciaux

## Migration des anciens tests

Les fichiers dans `Legacy/` sont des anciens scripts de test qui devraient être migrés vers PHPUnit :
- Convertir en classes de test avec attributs `#[Test]`
- Ajouter les namespaces appropriés
- Implémenter le nettoyage des données
- Suivre les conventions de nommage modernes