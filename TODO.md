# Feuille de Route - Plateforme SaaS Générique

Ce document détaille les étapes de développement pour la création de la plateforme SaaS générique. Chaque fonctionnalité majeure doit être accompagnée d'une suite de tests complète (Unit, Feature).

## Phase 1 : Fondations et Gestion des Utilisateurs

- [ ] **1.2. Gestion des Rôles et Permissions (Spatie)**
    - [*] Définir les rôlestion Robuste**
    - [*] Inscription, connexion, déconnexion.
    - [*] Réinitialisation de mot de passe par email.
    - [*] Réinitialisation de mot de passe par phone.
    - [*] Vérification d'email à l'inscription.
    - [*] **Tests** : Unit, Feature, pour chaque parcours. de base : `Super-Admin`, `Admin`, `Client`.
    - [ ] Créer une interface pour assigner les rôles.
    - [ ] **Tests** : Vérifier les restrictions d'accès en fonction des rôles.

## Phase 2 : Portefeuille Virtuel et Transactions

- [ ] **2.1. Module `Wallet`**
    - [*] Créer le modèle `Wallet` lié à chaque utilisateur.
    - [*] Implémenter les méthodes de base : `credit()`, `debit()`.
    - [*] Assurer que les transactions sont atomiques pour éviter les incohérences.
    - [ ] **Tests** : Tests unitaires sur les opérations de crédit/débit.

- [ ] **2.2. Intégration des Paiements Mobiles**
    - [ ] Créer une interface `PaymentGateway` pour abstraire les fournisseurs.
    - [ ] Implémenter les adaptateurs pour Orange Money et MTN Money.
    - [ ] Gérer les callbacks de paiement pour créditer le `Wallet`.
    - [ ] **Tests** : Simuler des callbacks de paiement (mock) et vérifier le crédit du portefeuille.

## Phase 3 : Monétisation et Produits

- [ ] **3.1. Gestion des Produits/Services**
    - [ ] Créer une structure de données pour les produits (nom, prix, description).
    - [ ] Permettre aux administrateurs de gérer le catalogue.
    - [ ] **Tests** : CRUD des produits.

- [ ] **3.2. Processus d'Achat**
    - [ ] Logique d'achat d'un produit en utilisant le solde du `Wallet`.
    - [ ] Débiter le portefeuille et donner accès au service/produit.
    - [ ] **Tests** : test simulant un parcours d'achat complet.

## Phase 4 : Affiliation et Marketing

- [ ] **4.1. Système d'Affiliation**
    - [*] Générer un code de parrainage unique pour chaque utilisateur.
    - [*] Suivre les inscriptions via les liens de parrainage.
    - [*] Mettre en place un système de commissions (ex: % sur les recharges des filleuls).
    - [ ] **Tests** : Scénarios de parrainage et de calcul de commission.

## Phase 5 : Administration et Reporting

- [ ] **5.1. Tableau de Bord Administrateur**
    - [*] Vue d'ensemble des métriques clés (inscriptions, revenus, etc.).
    - [*] Gestion manuelle des `Wallets` (crédit/débit exceptionnel).
    - [ ] **Tests** : test pour les fonctionnalités critiques de l'admin.

## Phase 6 : Finalisation et Déploiement

- [ ] **6.1. Documentation**
    - [ ] Documenter l'API (si exposée).
    - [ ] Rédiger un guide pour dupliquer et configurer un nouveau projet à partir de cette base.

- [ ] **6.2. Optimisation**
    - [ ] Mettre en cache les configurations et les routes.
    - [ ] Optimiser les requêtes de base de données.

- setup global handler for notfound model and 500 with response and view

- déplacez les logiques vers les services et les repositories
- ajouter une vue pour consulter la liste des filleuls (revenus, date d'inscription)
- restaurer le remember_token
- retirer les commentaires en français inutiles et garder les commentaires en anglais utiles
- garder le mm standard partout