# Tests WhatsApp E2E

Ce dossier contient les tests End-to-End spécifiques aux fonctionnalités WhatsApp.

## Fichiers

### Scripts de test (Legacy)
Ces fichiers sont des scripts PHP directs qui doivent être migrés vers PHPUnit :

- **BaseTestIncomingMessage.php** - Classe de base pour les tests de messages entrants
- **test-incoming-flow-basic.php** - Test du flux de base des messages entrants
- **test-incoming-flow-complete.php** - Test du flux complet des messages entrants  
- **test-incoming-flow-conversation.php** - Test des conversations
- **test-incoming-flow-with-products.php** - Test avec gestion des produits
- **test-whatsapp-currency-formatting.php** - Test du formatage des devises

## Migration vers PHPUnit

Ces scripts devraient être convertis en tests PHPUnit modernes :

```php
<?php

namespace Tests\E2E\WhatsApp;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IncomingMessageFlowE2ETest extends TestCase
{
    #[Test]
    public function basic_incoming_flow_works(): void
    {
        // Migrer le contenu de test-incoming-flow-basic.php
    }
}
```

## Utilisation actuelle

Ces scripts peuvent être exécutés directement avec PHP :

```bash
php tests/E2E/WhatsApp/test-incoming-flow-basic.php
```

## Objectif de migration

- [ ] Migrer `test-incoming-flow-basic.php` → `IncomingMessageFlowE2ETest.php`
- [ ] Migrer `test-incoming-flow-complete.php` → `CompleteIncomingFlowE2ETest.php`  
- [ ] Migrer `test-incoming-flow-conversation.php` → `ConversationFlowE2ETest.php`
- [ ] Migrer `test-incoming-flow-with-products.php` → `ProductIntegrationFlowE2ETest.php`
- [ ] Migrer `test-whatsapp-currency-formatting.php` → `CurrencyFormattingE2ETest.php`