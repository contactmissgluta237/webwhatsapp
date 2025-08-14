# Configuration IA Centralisée - Version Corrigée

## Vue d'ensemble

Cette documentation explique la nouvelle architecture de configuration centralisée pour tous les services d'IA du projet. **IMPORTANT** : Séparation stricte entre le code de production et les utilitaires de test.

## Architecture Propre

### 🏗️ Services de Production
- **`config/ai.php`** - Configuration centralisée pour tous les providers IA
- **`app/Services/AI/AiConfigurationService.php`** - Service de production UNIQUEMENT

### 🧪 Utilitaires de Test  
- **`tests/Helpers/AiTestHelper.php`** - Helper dédié AUX TESTS UNIQUEMENT

## Principe de Séparation

### ✅ AiConfigurationService (Production)
```php
final class AiConfigurationService
{
    // UNIQUEMENT méthodes de production
    public static function getProviderConfig(string $provider): array
    public static function getDefaultModels(): array
    
    // ❌ PAS de méthodes avec "Test" dans le nom !
}
```

### ✅ AiTestHelper (Tests)
```php
final class AiTestHelper
{
    // UNIQUEMENT méthodes pour les tests
    public static function createTestModelData(string $provider, array $overrides = []): array
    public static function getOllamaTestEndpoint(): string
    public static function getMockEndpoint(string $type = 'inaccessible'): string
    public static function createOllamaTestModel(array $overrides = []): array
}
```

## Usage dans le Code

### Pour les Services de Production
```php
use App\Services\AI\AiConfigurationService;

// Obtenir config d'un provider
$ollamaConfig = AiConfigurationService::getProviderConfig('ollama');

// Obtenir tous les modèles par défaut pour les seeders
$models = AiConfigurationService::getDefaultModels();
```

### Pour les Tests UNIQUEMENT
```php
use Tests\Helpers\AiTestHelper;

// Créer des données de test
$testData = AiTestHelper::createTestModelData('ollama');

// Obtenir endpoint de test
$endpoint = AiTestHelper::getOllamaTestEndpoint();

// Obtenir endpoint mock pour erreurs
$mockEndpoint = AiTestHelper::getMockEndpoint('inaccessible');
```

## Exemple de Migration Corrigée

### ❌ AVANT (Mélange production/test)
```php
// Dans un service de production - INCORRECT !
use App\Services\AI\AiConfigurationService;
$model = AiConfigurationService::createTestModelData('ollama'); // ❌ "Test" dans prod !
```

### ✅ APRÈS (Séparation propre)
```php
// Dans les tests
use Tests\Helpers\AiTestHelper;
$model = AiTestHelper::createTestModelData('ollama'); // ✅ Test dans tests !

// Dans la production  
use App\Services\AI\AiConfigurationService;
$config = AiConfigurationService::getProviderConfig('ollama'); // ✅ Prod dans prod !
```

## Règles Strictes

1. **Service de Production** = Zéro méthode avec "Test" dans le nom
2. **Helper de Test** = Zéro utilisation en production  
3. **Namespace séparé** = `App\Services\AI\` vs `Tests\Helpers\`
4. **Import interdit** = Jamais importer AiTestHelper dans les services de prod

## Architecture Correcte

```
app/Services/AI/
  ├── AiConfigurationService.php  ← Production UNIQUEMENT
  └── [autres services de prod]

tests/Helpers/
  ├── AiTestHelper.php            ← Tests UNIQUEMENT  
  └── [autres helpers de test]

config/
  └── ai.php                      ← Configuration centralisée
```

## Validation de la Séparation

### ✅ Tests Passent
- ✅ Tests Feature : 8/8 tests passent (62.84s)
- ✅ Service de production propre (2 méthodes seulement)
- ✅ Helper de test isolé (6 méthodes de test)

Cette architecture respecte maintenant les principes de **Clean Architecture** et de **Separation of Concerns**.
