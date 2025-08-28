# Standards de Structure et d'Organisation du Code

## 🎯 Objectif
Ce document définit les standards de structure et d'organisation du code pour assurer une architecture cohérente et prévisible.

## 📁 Structure des Contrôleurs

### Convention de Nommage des URLs et Contrôleurs

Pour chaque URL, la structure du contrôleur doit être prévisible :

```
URL: http://localhost:8000/admin/whatsapp/accounts
Contrôleur: app/Http/Controllers/Admin/WhatsApp/Account/IndexController.php
Namespace: App\Http\Controllers\Admin\WhatsApp\Account
```

### Règles pour les Contrôleurs

1. **Dossier par Ressource** : Chaque ressource (accounts, conversations, etc.) a son propre dossier
2. **Action par Contrôleur** : Un contrôleur par action (IndexController, ShowController, CreateController, etc.)
3. **Structure Hiérarchique** : Respecter la hiérarchie des URLs dans la structure des dossiers

#### Exemples Corrects ✅

```
admin/whatsapp/accounts → Admin/WhatsApp/Account/IndexController
admin/whatsapp/accounts/{id} → Admin/WhatsApp/Account/ShowController
admin/whatsapp/conversations → Admin/WhatsApp/Conversation/IndexController
customer/packages → Customer/Packages/IndexController
```

#### Exemples Incorrects ❌

```
admin/whatsapp/accounts → Admin/WhatsApp/WhatsAppAccountController (trop générique)
admin/whatsapp/accounts → Admin/WhatsAppController (pas assez spécifique)
```

## 🗂️ Structure des Services

### Organisation par Domaine Métier

Les services doivent être organisés par domaine métier avec leurs interfaces dans un sous-dossier `Contracts` :

```
app/Services/
├── AI/
│   ├── AiConfigurationService.php
│   ├── OllamaService.php
│   ├── Contracts/
│   │   └── PromptEnhancementInterface.php
│   └── Helpers/
│       └── AgentPromptHelper.php
├── WhatsApp/
│   ├── WhatsAppService.php
│   ├── MessageService.php
│   └── Contracts/
│       ├── AIProviderServiceInterface.php
│       └── WhatsAppMessageOrchestratorInterface.php
├── Payment/
│   ├── PaymentService.php
│   └── Contracts/
│       └── PaymentServiceInterface.php
```

### Règles pour les Services

1. **Dossier par Domaine** : Un dossier par domaine métier (AI, WhatsApp, Payment, etc.)
2. **Interfaces dans Contracts** : Chaque interface va dans `{Domain}/Contracts/`
3. **Helpers Spécifiques** : Les helpers spécifiques à un domaine vont dans un sous-dossier `Helpers/`
4. **Services Partagés** : Les services utilisés par plusieurs domaines restent à la racine

## 📦 Structure des Repositories

### Organisation par Domaine avec Interfaces

```
app/Repositories/
├── WhatsApp/
│   ├── EloquentWhatsAppAccountRepository.php
│   ├── EloquentWhatsAppMessageRepository.php
│   └── Contracts/
│       ├── WhatsAppAccountRepositoryInterface.php
│       └── WhatsAppMessageRepositoryInterface.php
├── Payment/
│   ├── PaymentRepository.php
│   └── Contracts/
│       └── PaymentRepositoryInterface.php
├── Contracts/
│   └── ... (interfaces génériques seulement)
└── Eloquent/
    └── ... (implémentations génériques)
```

### Règles pour les Repositories

1. **Interface + Implémentation** : Toujours créer une interface et son implémentation
2. **Interfaces dans Contracts** : Les interfaces vont dans `{Domain}/Contracts/`
3. **Namespace Cohérent** : `App\Repositories\{Domain}\Contracts\{Entity}RepositoryInterface`
4. **Binding dans ServiceProvider** : Enregistrer les bindings dans `RepositoryServiceProvider`

## 📋 Standards des Interfaces

### Localisation des Interfaces

**❌ Incorrect** : Dossier global `app/Contracts`
```
app/Contracts/
├── PaymentServiceInterface.php
├── WhatsAppServiceInterface.php
└── AIServiceInterface.php
```

**✅ Correct** : Interfaces dans le domaine métier
```
app/Services/Payment/Contracts/PaymentServiceInterface.php
app/Services/WhatsApp/Contracts/WhatsAppServiceInterface.php
app/Services/AI/Contracts/AIServiceInterface.php
```

### Règles pour les Interfaces

1. **Proximité** : Chaque interface doit être proche de son implémentation
2. **Dossier Contracts** : Toujours dans un sous-dossier `Contracts/`
3. **Namespace Cohérent** : `App\{Category}\{Domain}\Contracts\{Interface}`
4. **Binding Explicite** : Toujours lier l'interface à son implémentation dans un ServiceProvider

## 🛠️ Structure des Helpers

### Organisation par Domaine ou Usage

```
app/Helpers/
├── AI/
│   └── ResponseFormatter.php
├── WhatsApp/
│   └── QRCodeHelper.php
├── Payment/
│   └── PaymentHelper.php
└── CardHelper.php (helper générique)
```

### Règles pour les Helpers

1. **Helpers Spécifiques** : Dans le dossier du domaine approprié
2. **Helpers Génériques** : À la racine du dossier Helpers
3. **Classes Statiques** : Utiliser des méthodes statiques pour les helpers

## 🧪 Structure des Tests

### Correspondance avec la Structure du Code

```
tests/
├── Unit/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── WhatsApp/
│   │           └── Account/
│   │               └── IndexControllerTest.php
│   ├── Services/
│   │   └── AI/
│   │       └── OllamaServiceTest.php
│   └── Repositories/
│       └── WhatsApp/
│           └── RepositoryStructureTest.php
├── Feature/
│   └── Admin/
│       └── WhatsApp/
│           └── AccountManagementTest.php
```

### Règles pour les Tests

1. **Correspondance Structure** : La structure des tests suit celle du code source
2. **Test par Classe** : Un fichier de test par classe
3. **Tests d'Intégration** : Les tests de structure vérifient les bindings et configurations

## 🔄 Processus de Refactoring

### Étapes à Suivre

1. **Analyser** : Identifier les fichiers qui ne respectent pas les standards
2. **Déplacer** : Réorganiser les fichiers dans la structure correcte
3. **Mettre à Jour** : Modifier les namespaces et imports
4. **Tester** : Créer ou mettre à jour les tests
5. **Valider** : Vérifier que tout fonctionne correctement

### Checklist

- [ ] Structure des contrôleurs respecte les URLs
- [ ] Namespaces mis à jour
- [ ] Imports corrigés dans tous les fichiers
- [ ] ServiceProvider mis à jour
- [ ] Tests créés/mis à jour
- [ ] Documentation mise à jour

## 📋 Standards de Nommage

### Contrôleurs
- **Format** : `{Action}Controller` (ex: `IndexController`, `ShowController`)
- **Namespace** : Suit la structure des dossiers
- **Actions** : `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`

### Services
- **Format** : `{Domain}Service` (ex: `WhatsAppService`, `PaymentService`)
- **Namespace** : `App\Services\{Domain}`
- **Interface** : `App\Services\{Domain}\Contracts\{Service}Interface`

### Repositories
- **Interface** : `{Entity}RepositoryInterface`
- **Implémentation** : `Eloquent{Entity}Repository`
- **Namespace Interface** : `App\Repositories\{Domain}\Contracts`
- **Namespace Implémentation** : `App\Repositories\{Domain}`

### Helpers
- **Format** : `{Purpose}Helper` (ex: `QRCodeHelper`, `AgentPromptHelper`)
- **Namespace** : `App\Helpers\{Domain}` ou `App\Helpers`

## 🚀 Avantages de cette Structure

1. **Prévisibilité** : L'équipe peut rapidement localiser le code
2. **Maintenabilité** : Structure claire et cohérente
3. **Évolutivité** : Facile d'ajouter de nouvelles fonctionnalités
4. **Standards** : Respect des conventions Laravel
5. **Collaboration** : Facilite le travail en équipe

---

*Ce document doit être mis à jour à chaque évolution des standards du projet.*
