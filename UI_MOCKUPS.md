# Interface Produits - Aperçu des vues

## 1. Page de gestion des produits (/customer/products)

```
┌─────────────────────────────────────────────────────────────────┐
│ 📦 Mes Produits                              [+ Ajouter produit] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │
│ │    [📱]     │  │    [💻]     │  │    [🎧]     │               │
│ │ iPhone 15   │  │ MacBook Air │  │ AirPods Pro │               │
│ │ Pro Max     │  │ M3          │  │ 3           │               │
│ │             │  │             │  │             │               │
│ │ 850 000 XAF │  │1 200 000 XAF│  │ 180 000 XAF │               │
│ │ [✅ Actif]  │  │ [✅ Actif]  │  │ [✅ Actif]  │               │
│ │ [✏️] [👁️] [🗑️] │  │ [✏️] [👁️] [🗑️] │  │ [✏️] [👁️] [🗑️] │               │
│ └─────────────┘  └─────────────┘  └─────────────┘               │
│                                                                 │
│ ┌─────────────┐  ┌─────────────┐                               │
│ │    [📱]     │  │    [⌚]     │                               │
│ │ iPad Pro    │  │ Apple Watch │                               │
│ │ 12.9"       │  │ Series 9    │                               │
│ │             │  │             │                               │
│ │ 750 000 XAF │  │ 280 000 XAF │                               │
│ │ [✅ Actif]  │  │ [✅ Actif]  │                               │
│ │ [✏️] [👁️] [🗑️] │  │ [✏️] [👁️] [🗑️] │                               │
│ └─────────────┘  └─────────────┘                               │
└─────────────────────────────────────────────────────────────────┘
```

## 2. Formulaire de création/modification de produit

```
┌─────────────────────────────────────────────────────────────────┐
│ ✨ Nouveau produit                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Titre *: [iPhone 15 Pro Max________________________]            │
│                                                                 │
│ Prix (XAF) *: [850000_____________]                             │
│                                                                 │
│ Description *:                                                  │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Le dernier iPhone avec écran Super Retina XDR de 6,7       │ │
│ │ pouces, processeur A17 Pro et système photo avancé.        │ │
│ │                                                             │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ Images (max 5): [Choisir fichiers________________] 📁           │
│ Formats: JPG, PNG. Taille max: 2MB par image                   │
│                                                                 │
│ ☑️ Produit actif                                                │
│                                                                 │
│ [💾 Enregistrer] [❌ Annuler]                                   │
└─────────────────────────────────────────────────────────────────┘
```

## 3. Configuration IA - Onglet Produits

```
┌─────────────────────────────────────────────────────────────────┐
│ [ℹ️ Infos] [📄 Contextes] [⚙️ Avancé] [📦 Produits (5)]          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌─── Produits liés à l'agent ────┐ ┌─── Ajouter des produits ──┐ │
│ │ 🔗 Produits liés       [5/10]  │ │ 🔍 Ajouter produits       │ │
│ │ ┌─────────────────────────────┐ │ │ [Rechercher produit...___] │ │
│ │ │ [📱] iPhone 15 Pro Max      │ │ │                           │ │
│ │ │ 850 000 XAF            [❌] │ │ │ ┌───────────────────────┐ │ │
│ │ └─────────────────────────────┘ │ │ │ [📱] Samsung Galaxy   │ │ │
│ │ ┌─────────────────────────────┐ │ │ │ S24 Ultra             │ │ │
│ │ │ [💻] MacBook Air M3         │ │ │ │ 800 000 XAF      [+] │ │ │
│ │ │ 1 200 000 XAF          [❌] │ │ │ └───────────────────────┘ │ │
│ │ └─────────────────────────────┘ │ │ ┌───────────────────────┐ │ │
│ │ ┌─────────────────────────────┐ │ │ │ [💻] Dell XPS 13      │ │ │
│ │ │ [🎧] AirPods Pro 3          │ │ │ │ 950 000 XAF   [❌ Lié] │ │ │
│ │ │ 180 000 XAF            [❌] │ │ │ └───────────────────────┘ │ │
│ │ └─────────────────────────────┘ │ │                           │ │
│ │ ┌─────────────────────────────┐ │ │ [+ Créer un produit]      │ │
│ │ │ [📱] iPad Pro 12.9"         │ │ │                           │ │
│ │ │ 750 000 XAF            [❌] │ │ └───────────────────────────┘ │
│ │ └─────────────────────────────┘ │                               │
│ │ ┌─────────────────────────────┐ │                               │
│ │ │ [⌚] Apple Watch Series 9   │ │                               │
│ │ │ 280 000 XAF            [❌] │ │                               │
│ │ └─────────────────────────────┘ │                               │
│ │                                 │                               │
│ │ ℹ️ Vous pouvez encore ajouter   │                               │
│ │ 5 produit(s)                    │                               │
│ └─────────────────────────────────┘                               │
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ 💡 Comment ça fonctionne ?                                  │ │
│ │ • Liez jusqu'à 10 produits à votre agent IA                │ │
│ │ • L'IA pourra proposer ces produits aux clients            │ │
│ │ • Les produits incluront images, prix et descriptions      │ │
│ │ • Seuls les produits actifs peuvent être liés             │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Flux d'utilisation

1. **Créer des produits**: L'utilisateur va sur `/customer/products` et crée ses produits
2. **Ajouter images**: Upload d'images pour chaque produit (max 5 par produit)
3. **Configurer l'IA**: Dans la configuration WhatsApp, onglet "Produits"
4. **Lier les produits**: Recherche et sélection des produits à lier (max 10)
5. **Utilisation**: L'IA peut maintenant proposer ces produits aux clients

## Fonctionnalités clés

- ✅ Interface responsive et intuitive
- ✅ Upload d'images multiples
- ✅ Recherche en temps réel
- ✅ Validation des données
- ✅ Limite de 10 produits par agent
- ✅ Gestion des autorisations
- ✅ Feedback utilisateur avec toasts
- ✅ Design cohérent avec l'existant