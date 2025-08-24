# Fonctionnalité Produits Utilisateur - Documentation

## Vue d'ensemble

Cette fonctionnalité permet aux utilisateurs de créer et gérer leurs propres produits, puis de les lier à leurs agents IA WhatsApp. L'IA peut ensuite proposer ces produits aux prospects avec images, descriptions et prix.

## Fonctionnalités implémentées

### 1. Gestion des produits utilisateur

#### Modèle UserProduct
- **Table**: `user_products`
- **Champs**: 
  - `title` (string) - Nom du produit
  - `description` (text) - Description détaillée
  - `price` (decimal) - Prix en XAF
  - `is_active` (boolean) - Statut actif/inactif
  - `user_id` (foreign key) - Propriétaire du produit

#### Relations
- Un utilisateur peut avoir plusieurs produits
- Un produit peut être lié à plusieurs comptes WhatsApp
- Support des médias (images) via Spatie Media Library

### 2. Interface de gestion

#### Route
```
/customer/products - Page de gestion des produits
```

#### Composant Livewire: ProductManager
- **Création** de nouveaux produits avec upload d'images
- **Modification** des produits existants
- **Activation/Désactivation** des produits
- **Suppression** des produits
- **Gestion des images** (max 5 par produit)

#### Fonctionnalités:
- Upload multiple d'images (max 2MB par image)
- Validation des données
- Interface responsive avec cartes produits
- Gestion des autorisations (policies)

### 3. Intégration IA

#### Nouvel onglet "Produits" dans la configuration IA
- **Recherche** de produits par titre/description
- **Ajout/Retrait** de produits à l'agent IA
- **Limite** de 10 produits par agent
- **Compteur** de produits liés avec badge

#### Composant Livewire: AiProductsConfiguration
- Interface en deux colonnes:
  - Produits liés à l'agent (gauche)
  - Produits disponibles à ajouter (droite)
- Recherche en temps réel
- Affichage des images et prix
- Feedback utilisateur avec toasts

### 4. Base de données

#### Tables créées:
1. `user_products` - Stockage des produits
2. `whatsapp_account_products` - Table pivot pour les liaisons

#### Relations ajoutées:
- `User::userProducts()` - HasMany
- `WhatsAppAccount::userProducts()` - BelongsToMany
- `UserProduct::user()` - BelongsTo
- `UserProduct::whatsappAccounts()` - BelongsToMany

### 5. Autorisations

#### Policy UserProductPolicy
- Seuls les customers peuvent gérer des produits
- Un utilisateur ne peut gérer que ses propres produits
- Vérification des permissions sur toutes les actions CRUD

### 6. Tests

#### Tests unitaires
- `UserProductTest` - Tests du modèle et relations
- `ProductManagerTest` - Tests du composant Livewire
- `AiProductsConfigurationTest` - Tests de l'intégration IA

#### Couverture des tests:
- Création/modification/suppression de produits
- Relations entre modèles
- Contraintes de limite (10 produits max)
- Autorisations et sécurité
- Interface utilisateur Livewire

## Utilisation

### 1. Créer un produit
```php
$product = UserProduct::create([
    'user_id' => $userId,
    'title' => 'iPhone 15 Pro',
    'description' => 'Dernière génération...',
    'price' => 850000,
    'is_active' => true,
]);

// Ajouter des images
$product->addMediaFromRequest($imageFile)
    ->toMediaCollection('images');
```

### 2. Lier un produit à un agent IA
```php
$whatsappAccount->userProducts()->attach($productId);
```

### 3. Récupérer les produits d'un agent
```php
$userProducts = $whatsappAccount->userProducts()
    ->where('is_active', true)
    ->get();
```

## Interface utilisateur

### Navigation
1. **Menu Customer** → **Produits** → Gestion des produits
2. **Configuration IA** → **Onglet Produits** → Liaison avec l'agent

### Workflow
1. L'utilisateur crée ses produits avec images et descriptions
2. Dans la configuration IA, il recherche et sélectionne les produits à lier
3. L'IA peut maintenant proposer ces produits aux clients
4. Maximum 10 produits par agent pour éviter la surcharge

## Architecture

### Design Patterns utilisés
- **Repository Pattern** avec Eloquent ORM
- **Factory Pattern** pour les tests et seeders
- **Policy Pattern** pour les autorisations
- **Observer Pattern** avec Livewire pour la réactivité

### Traits utilisés
- `HasMediaCollections` pour la gestion des images
- `HasFactory` pour les factories de test
- `AuthorizesRequests` pour les autorisations

Cette implémentation respecte les standards Laravel et maintient la cohérence avec l'architecture existante du projet.