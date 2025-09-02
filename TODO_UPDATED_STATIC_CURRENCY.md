# TODO: Élimination des références de devises statiques

Ce document recense tous les endroits dans le code où des références de devises sont hardcodées (USD, XAF, $, FCFA) et nécessitent une centralisation via la configuration.

## 🎯 Objectif
Remplacer toutes les références statiques de devises par une configuration centralisée pour permettre une gestion flexible des devises dans l'application.

## 📍 Endroits identifiés avec références statiques

### 1. Helpers et Services Core

#### `app/Helpers/CurrencyHelper.php`
- **Ligne 9-10** : `USD_TO_XAF_RATE = 600.0` et `USD_TO_EUR_RATE = 0.92` (constantes hardcodées)
- **Ligne 14, 19** : Calculs de conversion XAF/USD avec taux fixe
- **Ligne 22-24** : `formatUsd()` avec symbole `$` hardcodé
- **Ligne 29** : `config("currencies.gateway_mappings.{$gateway}.{$paymentMethod}", 'XAF')` (fallback XAF)
- **Ligne 32-33** : Switch case avec 'XAF' et 'EUR' hardcodés

#### `app/Services/CurrencyService.php`
- **Ligne 31** : Commentaire "Toujours retourner USD maintenant"
- **Ligne 38, 41-44** : `formatPrice()` avec 'USD', 'XAF', 'EUR' hardcodés et symboles `$`, `XAF`, `€`
- **Ligne 77** : `config('currencies.default_currency', 'USD')` (fallback USD)

#### `app/Services/AI/DeepSeekService.php`
- **Ligne 23** : `USD_TO_XAF_RATE = 650` (constante hardcodée)
- **Ligne 268, 274-275** : Variables `$totalCostUSD`, `total_cost_usd`, `total_cost_xaf`

### 2. Constants et Enums

#### `app/Constants/FinancialLimits.php`
- **Ligne 21** : `USD_TO_XAF_DEFAULT_RATE = 650` (constante hardcodée)

#### `app/Services/Payment/Gateways/MyCoolPay/Enums/MyCoolPayCurrency.php`
- **Ligne 8, 16** : Enum avec 'XAF' hardcodé

### 3. Controllers et Logique Métier

#### `app/Http/Controllers/Customer/Packages/SubscribeController.php`
- **Ligne 91** : Message d'erreur avec "XAF" hardcodé
- **Ligne 114, 168** : Messages avec "XAF" hardcodé dans descriptions

#### `app/Services/Customer/PackageSubscriptionService.php`
- **Ligne 133, 238** : `'currency' => 'XAF'` hardcodé

#### `app/Handlers/WhatsApp/AgentActivationHandler.php`
- **Ligne 63** : `config('app.currency', 'XAF')` (fallback XAF)

### 4. Models avec formatage de devises

#### `app/Models/Package.php`
- **Ligne 243, 256** : `CurrencyHelper::formatUsd()` (force USD)

#### `app/Models/Wallet.php`
- **Ligne 97, 101** : Commentaire et usage `formatUsd()` (force USD)

#### `app/Models/UserProduct.php`
- **Ligne 111** : `CurrencyHelper::formatUsd()` (force USD)

### 5. Services de Messaging WhatsApp

#### `app/Services/WhatsApp/MessageBuildService.php`
- **Ligne 19** : `CURRENCY_SUFFIX = ' XAF'` (constante hardcodée)
- **Ligne 136** : Usage du suffix XAF

#### `app/Services/WhatsApp/Senders/AbstractMessageSender.php`
- **Ligne 23** : `CURRENCY_SUFFIX = ' XAF'` (constante hardcodée)
- **Ligne 130** : Formatage avec suffix XAF

### 6. Composants Livewire avec formatage

#### `app/Livewire/Customer/InternalTransactionDataTable.php`
- **Ligne 54** : `CurrencyHelper::formatUsd()` (force USD)

#### `app/Livewire/Admin/PackagesDataTable.php`
- **Ligne 100-101, 114** : `CurrencyHelper::formatUsd()` (force USD)

#### `app/Livewire/Admin/WhatsApp/WhatsAppAccountDataTable.php`
- **Ligne 168-175** : Variables `$totalCostXAF`, `$totalCostUSD`, texte "XAF" hardcodé

#### `app/Livewire/Admin/WhatsApp/ConversationDataTable.php`
- **Ligne 95** : `'0 XAF'` hardcodé
- **Ligne 262, 279** : Filtres "Min Cost (XAF)" et "Max Cost (XAF)" hardcodés

### 7. Templates et Vues

#### `resources/views/customer/packages/index.blade.php`
- **Ligne 69, 73, 75** : `CurrencyHelper::formatUsd()` (force USD)

#### `resources/views/emails/*.blade.php`
- Multiples fichiers avec "FCFA" hardcodé dans les templates d'emails
- **Exemple** : `recharge-notification.blade.php` ligne 7, 13, 19

#### `resources/views/livewire/customer/whats-app/tabs/basic-information.blade.php`
- **Ligne 89** : `USD / 1000 tokens` hardcodé

### 8. Configuration et Tests

#### `tests/` (multiples fichiers)
- **Ligne variées** : Tests avec `'currency' => 'USD'` et `'currency' => 'XAF'` hardcodés
- **Exemple** : `AbstractPackageFormTest.php` ligne 28 teste `'USD'`
- **Exemple** : `ToggleAiControllerTest.php` ligne 47, 67 avec XAF/USD hardcodés

#### `app/Http/Requests/Admin/Packages/*.php`
- **StorePackageRequest.php** ligne 24 : validation `currency` sans contrainte sur les valeurs
- **UpdatePackageRequest.php** ligne 29 : même problème

### 9. Notifications et Mails

#### `app/Notifications/WhatsApp/WalletDebitedNotification.php`
- **Ligne 61, 78** : Messages avec "XAF" hardcodé

#### `app/Mail/*.php`
- Multiples fichiers de mail avec "FCFA" hardcodé
- **Exemple** : `RechargeNotificationMail.php` avec formatage FCFA

### 10. Services de Transaction

#### `app/Services/Transaction/ExternalTransactionService.php`
- **Ligne 77, 116, 177** : Formatage avec devise utilisateur mais aussi "FCFA" hardcodé

## 🎯 Solution proposée - Configuration centralisée

### 1. Nouveau fichier de configuration `config/currencies.php`

```php
<?php

return [
    // Devise par défaut de l'application
    'default' => env('APP_DEFAULT_CURRENCY', 'USD'),
    
    // Symboles de devises
    'symbols' => [
        'USD' => '$',
        'XAF' => 'FCFA',
        'EUR' => '€',
    ],
    
    // Formatage par devise
    'formatting' => [
        'USD' => [
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => '',
            'position' => 'after', // $ après le montant
        ],
        'XAF' => [
            'decimals' => 0,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'position' => 'after', // FCFA après le montant
        ],
        'EUR' => [
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ' ',
            'position' => 'after', // € après le montant
        ],
    ],
    
    // Taux de conversion (à déplacer vers une source dynamique plus tard)
    'exchange_rates' => [
        'USD_TO_XAF' => env('EXCHANGE_RATE_USD_TO_XAF', 650),
        'USD_TO_EUR' => env('EXCHANGE_RATE_USD_TO_EUR', 0.92),
    ],
    
    // Mapping pour les gateways de paiement
    'gateway_mappings' => [
        'mycoolpay' => [
            'mobile_money' => 'XAF',
            'orange_money' => 'XAF',
        ],
    ],
    
    // Devises autorisées dans l'application
    'allowed' => ['USD', 'XAF', 'EUR'],
];
```

### 2. Service de formatage centralisé amélioré

#### Améliorer `CurrencyService` pour remplacer `CurrencyHelper`

```php
class CurrencyService 
{
    public function getDefaultCurrency(): string 
    {
        return config('currencies.default');
    }
    
    public function getSymbol(string $currency): string 
    {
        return config("currencies.symbols.{$currency}", $currency);
    }
    
    public function format(float $amount, ?string $currency = null): string 
    {
        $currency = $currency ?? $this->getDefaultCurrency();
        $config = config("currencies.formatting.{$currency}");
        $symbol = $this->getSymbol($currency);
        
        $formatted = number_format(
            $amount,
            $config['decimals'],
            $config['decimal_separator'],
            $config['thousands_separator']
        );
        
        return $config['position'] === 'before' 
            ? "{$symbol}{$formatted}" 
            : "{$formatted} {$symbol}";
    }
    
    public function getExchangeRate(string $from, string $to): float 
    {
        $key = strtoupper("{$from}_TO_{$to}");
        return config("currencies.exchange_rates.{$key}", 1.0);
    }
}
```

### 3. Variables d'environnement à ajouter

```env
# Devise par défaut de l'application
APP_DEFAULT_CURRENCY=USD

# Taux de change (à déplacer vers API externe plus tard)
EXCHANGE_RATE_USD_TO_XAF=650
EXCHANGE_RATE_USD_TO_EUR=0.92
```

## ✅ Plan de migration

### Phase 1 : Infrastructure
1. ✅ Créer le fichier `config/currencies.php`
2. ✅ Améliorer `CurrencyService` avec les nouvelles méthodes
3. ✅ Ajouter les variables d'environnement

### Phase 2 : Remplacement des Helpers
1. Remplacer tous les usages de `CurrencyHelper::formatUsd()` par `CurrencyService::format()`
2. Supprimer les constantes hardcodées dans `CurrencyHelper`
3. Mettre à jour `DeepSeekService` pour utiliser la config

### Phase 3 : Models et Services
1. Mettre à jour tous les models (`Package`, `Wallet`, `UserProduct`)
2. Corriger les services (`MessageBuildService`, `AbstractMessageSender`)
3. Mettre à jour les controllers

### Phase 4 : Livewire et Vues
1. Corriger tous les composants Livewire
2. Mettre à jour les templates Blade
3. Corriger les notifications et mails

### Phase 5 : Tests et Validation
1. Mettre à jour tous les tests pour utiliser la config
2. Valider le bon fonctionnement
3. Tests de régression

## 🔧 Commandes pour identifier d'autres occurrences

```bash
# Rechercher d'autres occurrences de devises hardcodées
rg -n "USD|XAF|FCFA|\\\$[0-9]" app/ resources/ tests/ --type php

# Rechercher les formatages de devises
rg -n "formatUsd|format.*\\\$" app/ resources/ --type php

# Rechercher les symboles de devises dans les templates
rg -n "FCFA|USD|XAF" resources/views/ --type php
```

## 📋 Checklist finale

- [ ] Configuration `config/currencies.php` créée
- [ ] `CurrencyService` amélioré avec formatage centralisé
- [ ] Toutes les constantes hardcodées supprimées
- [ ] Tous les `CurrencyHelper::formatUsd()` remplacés
- [ ] Services de messaging corrigés
- [ ] Components Livewire mis à jour
- [ ] Templates d'emails corrigés
- [ ] Tests mis à jour
- [ ] Variables d'environnement documentées
- [ ] Tests de régression passés

## 💡 Améliorations futures

1. **API de taux de change** : Remplacer les taux statiques par une API externe (ex: ExchangeRate-API)
2. **Cache des taux** : Implémenter un cache Redis pour les taux de change
3. **Multi-devises utilisateur** : Permettre à chaque utilisateur de choisir sa devise de préférence
4. **Historique des taux** : Sauvegarder l'historique des taux pour les analyses