# TODO REPOSITORY CLEAN - Plan de Refactorisation

## 🎯 OBJECTIF
Migrer toutes les requêtes complexes non-CRUD vers des repositories dédiés pour améliorer la maintenabilité, la testabilité et la réutilisabilité du code.

## 📊 ANALYSE COMPLÈTE DES REQUÊTES NON-CRUD IDENTIFIÉES

### 1. 🔥 **PRIORITÉ CRITIQUE** - Métriques & Analytics

#### **AdminMetricsRepository**
**Fichiers concernés :**
- `app/Services/AdminDashboardMetricsService.php:42-82` - **UTILISÉ ACTIVEMENT**
- `app/Http/Controllers/Admin/WhatsApp/Dashboard/IndexController.php:37-61` - **UTILISÉ ACTIVEMENT**

**Requêtes complexes identifiées :**
```php
// AdminDashboardMetricsService.php - MÉTHODES RÉELLEMENT UTILISÉES
User::role(UserRole::CUSTOMER()->value)->whereBetween('created_at', [$startDate, $endDate])->count();
ExternalTransaction::where('transaction_type', ExternalTransactionType::WITHDRAWAL())->sum('amount');
ExternalTransaction::where('transaction_type', ExternalTransactionType::RECHARGE())->sum('amount');  
SystemAccountTransaction::where('type', ExternalTransactionType::RECHARGE())->sum('amount');
SystemAccount::where('is_active', true)->get()->map(...);

// IndexController.php - MÉTHODES RÉELLEMENT UTILISÉES
WhatsAppAccount::count();
WhatsAppAccount::where('status', WhatsAppStatus::CONNECTED()->value)->count();
WhatsAppConversation::count();
WhatsAppMessage::count();
WhatsAppAccount::where('agent_enabled', true)->count();
AiUsageLog::selectRaw('DATE(created_at) as date, COUNT(*) as requests, SUM(total_cost_xaf) as cost')->groupBy('date');
```

**Repository proposé - SEULEMENT LES MÉTHODES UTILISÉES :**
```php
interface AdminMetricsRepositoryInterface
{
    // Méthodes du AdminDashboardMetricsService
    public function getRegisteredUsersCount(Carbon $startDate, Carbon $endDate): int;
    public function getTotalWithdrawals(Carbon $startDate, Carbon $endDate): float;
    public function getTotalRecharges(Carbon $startDate, Carbon $endDate): float;
    public function getCompanyProfit(Carbon $startDate, Carbon $endDate): float;
    public function getSystemAccountsBalance(): Collection;
    
    // Méthodes du WhatsApp Dashboard Controller
    public function getWhatsAppSystemStats(): array;
    public function getAiUsageTrend(int $days = 30): Collection;
}
```

---

#### **AiUsageRepository**
**Fichiers concernés :**
- `app/Models/AiUsageLog.php:178-181` - **UTILISÉ ACTIVEMENT** ✅
- `app/Services/AI/AiUsageTracker.php:68-112` - **UTILISÉ ACTIVEMENT** ✅

**Requêtes complexes RÉELLEMENT UTILISÉES :**
```php
// AiUsageLog.php - MÉTHODE STATIQUE UTILISÉE (après suppression des inutiles)
self::selectRaw('user_id, SUM(total_cost_usd) as total_cost, COUNT(*) as request_count')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy('user_id')->get();                                       // Ligne 178-181

// AiUsageTracker.php - MÉTHODES UTILISÉES
AiUsageLog::byUser($user->id)->byDateRange(now()->startOfDay(), now())->sum('total_cost_usd');  // Ligne 68-70
AiUsageLog::with('user')->byDateRange($startDate, now())
    ->selectRaw('user_id, SUM(total_cost_usd) as total_cost, SUM(total_cost_xaf) as total_cost_xaf, 
                 COUNT(*) as request_count, SUM(total_tokens) as total_tokens')
    ->groupBy('user_id')->orderBy('total_cost', 'desc')->limit($limit)->get();  // Ligne 100-112
```

**Repository proposé - APRÈS NETTOYAGE :**
```php
interface AiUsageRepositoryInterface  
{
    // Méthodes du modèle AiUsageLog encore utilisées
    public function getUsersUsageStats(?Carbon $startDate = null, ?Carbon $endDate = null): Collection;
    
    // Méthodes du AiUsageTracker utilisées
    public function getUserDailySpend(int $userId): float;
    public function getTopSpendingUsers(int $limit = 10, string $period = '30_days'): Collection;
}
```

---

### 2. 🟠 **PRIORITÉ ÉLEVÉE** - Gestion Financière

#### **ReferralRepository** - ⚠️ **SUPPRIMÉ** 
**TOUTES LES MÉTHODES COMPLEXES ÉTAIENT INUTILISÉES !**

**Seules les requêtes simples restent :**
- `distributeReferralEarnings()` - utilise `DB::transaction()` (OK, pas besoin de repository)
- `updateCommissionRate()` - simple update (OK, pas de repository nécessaire)

**📝 CONCLUSION : PAS DE REPOSITORY NÉCESSAIRE POUR REFERRAL**

---

#### **TransactionRepository** 
**Fichiers concernés :**
- `app/Livewire/Admin/Transactions/DataTables/ExternalTransactionDataTable.php:56-137` - **UTILISÉ ACTIVEMENT**
- `app/Livewire/Admin/Transactions/DataTables/InternalTransactionDataTable.php` - **UTILISÉ ACTIVEMENT**

**Requêtes complexes RÉELLEMENT UTILISÉES :**
```php
// ExternalTransactionDataTable.php - JOINS UTILISÉS DANS LES DATATABLES
ExternalTransaction::query()                                                           // Ligne 36
->join('wallets', 'external_transactions.wallet_id', '=', 'wallets.id')              // Ligne 56
->join('users', 'wallets.user_id', '=', 'users.id')                                  // Ligne 57
->leftJoin('users as approvers', 'external_transactions.approved_by', '=', 'approvers.id')  // Ligne 137

// InternalTransactionDataTable.php  
InternalTransaction::query()                                                           // Ligne 27
// Requêtes similaires avec filtres utilisateur
```

**Repository proposé - SEULEMENT LES MÉTHODES UTILISÉES :**
```php
interface TransactionRepositoryInterface
{
    // Méthodes pour DataTables réellement utilisées
    public function getExternalTransactionsWithUser(): Builder;
    public function getInternalTransactionsQuery(): Builder;
}
```

---

### 3. 🟡 **PRIORITÉ MOYENNE** - WhatsApp & Conversations

#### **WhatsAppAccountRepository**
**Fichiers concernés :**
- `app/Models/WhatsAppAccount.php:212-220`
- Livewire DataTables WhatsApp
- `app/Models/WhatsAppAccountUsage.php:76-163`

**Requêtes complexes identifiées :**
```php
// WhatsAppAccount.php:212-220
return $this->conversations()->count();
->where('ai_enabled', true)->count();

// WhatsAppAccountUsage.php:86-94
->sum('total_cost');
->sum('media_count');
```

**Repository proposé :**
```php
interface WhatsAppAccountRepositoryInterface
{
    public function getAccountStats(int $accountId): array;
    public function getAccountsWithConversationCount(): Collection;
    public function getActiveAccountsCount(): int;
    public function getAiEnabledAccountsCount(): int;
    public function getAccountUsageStats(int $accountId): array;
    public function getTopAccountsByMessages(int $limit = 10): Collection;
}
```

---

#### **ConversationRepository**
**Fichiers concernés :**
- `app/Models/WhatsAppConversation.php:115-195`
- `app/Services/WhatsApp/ConversationHistoryService.php:61-94`

**Requêtes complexes identifiées :**
```php
// WhatsAppConversation.php:115-123
->sum('total_cost');
$this->messageUsageLogs()->sum('total_cost');

// ConversationHistoryService.php:91-94  
'total_messages' => $conversation->messages()->count(),
'recent_messages' => $recentMessages->count(),
'ai_ratio' => $recentMessages->count() > 0 ? $aiMessages / $recentMessages->count() : 0.0,
```

**Repository proposé :**
```php
interface ConversationRepositoryInterface
{
    public function getConversationCosts(int $conversationId): float;
    public function getConversationStats(int $conversationId): array;  
    public function getConversationsWithMessageCount(int $accountId): Collection;
    public function getAverageResponseTime(int $conversationId): ?float;
    public function getConversationAnalytics(int $conversationId): array;
}
```

---

### 4. 🔵 **PRIORITÉ BASSE** - Autres Repositories

#### **DataTableRepository** 
**Fichiers concernés - AVEC REQUÊTES RÉELLEMENT UTILISÉES :**
- `app/Livewire/Admin/WhatsApp/ConversationDataTable.php:273-292` - **AGRÉGATIONS AI USAGE**
- `app/Livewire/Admin/Referrals/ReferralDataTable.php:69-84` - **JOINS REFERRERS**  
- `app/Livewire/Customer/Ticket/TicketDataTable.php:79` - **JOINS ASSIGNED USERS**

**Requêtes complexes RÉELLEMENT UTILISÉES :**
```php
// ConversationDataTable.php - AGRÉGATIONS COÛTS AI
$q->selectRaw('conversation_id, SUM(total_cost_xaf) as total_cost')
  ->groupBy('conversation_id')
  ->havingRaw('SUM(total_cost_xaf) >= ?', [(float) $value]);                          // Ligne 273-275

// ReferralDataTable.php - JOINS REFERRERS  
$query->join('users as referrers', 'users.referrer_id', '=', 'referrers.id');       // Ligne 69-84

// TicketDataTable.php - JOINS ASSIGNED USERS
$query->leftJoin('users as assigned_users', 'tickets.assigned_to', '=', 'assigned_users.id'); // Ligne 79
```

**Repository proposé - SEULEMENT LES MÉTHODES UTILISÉES :**
```php
interface DataTableRepositoryInterface
{
    // Méthodes pour les filtres avancés des DataTables
    public function getConversationsWithAiCostFilter(float $minCost = null, float $maxCost = null): Builder;
    public function getReferralsWithReferrerInfo(): Builder;
    public function getTicketsWithAssignedUsers(): Builder;
}
```

---

## 🏗️ ARCHITECTURE PROPOSÉE

### Structure des Repositories
```
app/Repositories/
├── Contracts/
│   ├── AdminMetricsRepositoryInterface.php
│   ├── AiUsageRepositoryInterface.php
│   ├── ReferralRepositoryInterface.php
│   ├── TransactionRepositoryInterface.php
│   ├── WhatsAppAccountRepositoryInterface.php
│   ├── ConversationRepositoryInterface.php
│   └── ...
├── Eloquent/
│   ├── AdminMetricsRepository.php
│   ├── AiUsageRepository.php
│   ├── ReferralRepository.php
│   ├── TransactionRepository.php
│   ├── WhatsAppAccountRepository.php
│   ├── ConversationRepository.php
│   └── ...
└── RepositoryServiceProvider.php
```

### Injection de Dépendances
```php
// Dans RepositoryServiceProvider.php
public function register(): void
{
    $this->app->bind(AdminMetricsRepositoryInterface::class, AdminMetricsRepository::class);
    $this->app->bind(AiUsageRepositoryInterface::class, AiUsageRepository::class);
    // ... autres bindings
}
```

---

## 📋 PLAN D'IMPLÉMENTATION

### Phase 1: Repositories Critiques (Semaine 1-2) ⚡
1. ✅ **AdminMetricsRepository** - 7 méthodes utilisées réellement
2. ✅ **AiUsageRepository** - 3 méthodes utilisées réellement (après nettoyage)

### Phase 2: Repositories DataTables (Semaine 3) 📊  
3. ✅ **TransactionRepository** - 2 méthodes pour DataTables
4. ✅ **DataTableRepository** - 3 méthodes pour filtres avancés

### Phase 3: Tests & Migration (Semaine 4) 🧪
5. ✅ Tests unitaires pour chaque repository
6. ✅ Migration du code existant  
7. ✅ Documentation

**TOTAL APRÈS NETTOYAGE: 4 REPOSITORIES avec 15 méthodes réellement utilisées**

### 🗑️ **SUPPRIMÉ DÉFINITIVEMENT :**
- ❌ **ReferralRepository** - toutes les méthodes complexes étaient inutiles
- ❌ 2 méthodes AiUsageLog inutilisées  
- ❌ 3 méthodes ReferralService inutilisées

---

## 🎯 BÉNÉFICES ATTENDUS

### ✅ **Séparation des Responsabilités**
- Logique métier centralisée dans les repositories
- Services allégés et plus focalisés
- Models dédies aux relations et accesseurs

### ✅ **Testabilité Améliorée**  
- Mocking facile des repositories
- Tests unitaires isolés
- Couverture de code augmentée

### ✅ **Réutilisabilité**
- Requêtes complexes réutilisables
- API cohérente entre les repositories
- Moins de duplication de code

### ✅ **Performance**
- Optimisation centralisée des requêtes
- Mise en cache au niveau repository
- Eager loading optimisé

### ✅ **Maintenabilité**
- Code plus lisible et organisé
- Évolution plus simple des requêtes
- Debugging facilité

---

## 🔧 EXEMPLES D'IMPLÉMENTATION

### AdminMetricsRepository - Implémentation EXACTE des méthodes utilisées
```php
<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AdminMetricsRepositoryInterface;
use App\Models\{User, ExternalTransaction, SystemAccount, SystemAccountTransaction, WhatsAppAccount, WhatsAppConversation, WhatsAppMessage, AiUsageLog};
use App\Enums\{UserRole, ExternalTransactionType, TransactionStatus, WhatsAppStatus};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminMetricsRepository implements AdminMetricsRepositoryInterface
{
    // AdminDashboardMetricsService.php:42
    public function getRegisteredUsersCount(Carbon $startDate, Carbon $endDate): int
    {
        return User::role(UserRole::CUSTOMER()->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    // AdminDashboardMetricsService.php:50
    public function getTotalWithdrawals(Carbon $startDate, Carbon $endDate): float
    {
        return (float) ExternalTransaction::where('transaction_type', ExternalTransactionType::WITHDRAWAL())
            ->where('status', TransactionStatus::COMPLETED())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;
    }

    // AdminDashboardMetricsService.php:58
    public function getTotalRecharges(Carbon $startDate, Carbon $endDate): float
    {
        return (float) ExternalTransaction::where('transaction_type', ExternalTransactionType::RECHARGE())
            ->where('status', TransactionStatus::COMPLETED())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;
    }

    // AdminDashboardMetricsService.php:64-72
    public function getCompanyProfit(Carbon $startDate, Carbon $endDate): float
    {
        $recharges = (float) SystemAccountTransaction::where('type', ExternalTransactionType::RECHARGE())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;

        $withdrawals = (float) SystemAccountTransaction::where('type', ExternalTransactionType::WITHDRAWAL())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;

        return $recharges - $withdrawals;
    }

    // AdminDashboardMetricsService.php:80-82
    public function getSystemAccountsBalance(): Collection
    {
        return SystemAccount::where('is_active', true)
            ->get()
            ->map(fn ($account) => SystemAccountBalanceDTO::fromSystemAccount($account));
    }

    // IndexController.php:37-41
    public function getWhatsAppSystemStats(): array
    {
        return [
            'total_accounts' => WhatsAppAccount::count(),
            'active_accounts' => WhatsAppAccount::where('status', WhatsAppStatus::CONNECTED()->value)->count(),
            'total_conversations' => WhatsAppConversation::count(),
            'total_messages' => WhatsAppMessage::count(),
            'ai_enabled_accounts' => WhatsAppAccount::where('agent_enabled', true)->count(),
        ];
    }

    // IndexController.php:58-62
    public function getAiUsageTrend(int $days = 30): Collection
    {
        return AiUsageLog::selectRaw('DATE(created_at) as date, COUNT(*) as requests, SUM(total_cost_xaf) as cost')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }
}
```

---

## ⚠️ POINTS D'ATTENTION

### 🔴 **Migration Prudente**
- Migrer repository par repository
- Tests complets avant déploiement
- Rollback plan en cas de problème

### 🔴 **Performance**
- Surveiller les performances après migration
- Optimiser les requêtes si nécessaire
- Mettre en place du cache si besoin

### 🔴 **Interfaces**
- Définir des interfaces claires
- Respecter les contrats
- Documentation complète

---

## ✅ VALIDATION REQUISE

Avant de procéder à l'implémentation :

1. **Validation de l'architecture** ✋
2. **Priorisation des repositories** ✋  
3. **Définition des interfaces** ✋
4. **Plan de migration détaillé** ✋
5. **Stratégie de tests** ✋

---

**📝 Ce document servira de référence pour la refactorisation complète du code vers une architecture repository propre et maintenable.**