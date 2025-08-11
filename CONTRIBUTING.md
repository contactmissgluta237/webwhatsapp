# Guide de Contribution

Ce document fournit un ensemble de directives pour contribuer à ce projet. L'objectif est de maintenir une base de code propre, cohérente et de haute qualité.

## Philosophie

Nous adhérons aux principes suivants pour garantir un code digne d'un développeur senior :

-   **Code Propre :** Le code doit être lisible, compréhensible et maintenable.
-   **SOLID :** Respectez les cinq principes SOLID.
-   **DRY (Don't Repeat Yourself) :** Évitez la duplication en créant des abstractions réutilisables.
-   **KISS (Keep It Simple, Stupid) :** Préférez les solutions simples et directes.

## Architecture

Le projet suit une architecture applicative claire pour séparer les responsabilités :

-   **Repository Pattern :** Toute la logique d'accès aux données et les requêtes Eloquent doivent être encapsulées dans des classes `Repository`. Les contrôleurs et les services ne doivent pas contenir de requêtes de base de données directes.
-   **Service Pattern :** La logique métier complexe est orchestrée par des classes `Service`. Les services coordonnent les repositories et d'autres services pour accomplir une tâche métier.
-   **Event Pattern :** Pour les actions découplées (par exemple, l'envoi d'un email après l'inscription d'un utilisateur), nous utilisons le système d'événements et d'écouteurs de Laravel. Les événements sont déclenchés par les services ou les contrôleurs, et les `Listeners` gèrent les actions secondaires.
-   **Controllers :** Les contrôleurs doivent rester légers (`thin`). Leur rôle principal est de gérer les requêtes HTTP, de valider les entrées et de retourner des réponses, en déléguant la logique métier aux services.

## Style de Code et Bonnes Pratiques

-   **Formatage :** Le code doit être correctement formaté en suivant les standards du projet (Laravel Pint/Duster est configuré).
-   **Petites Fonctions :** Découpez la logique complexe en petites fonctions bien nommées qui ont une seule responsabilité.
-   **Optimisation :** Écrivez un code performant, en particulier pour les requêtes de base de données (évitez le problème N+1, utilisez les index, etc.).
-   **Commentaires :**
    -   N'ajoutez pas de commentaires évidents. Un code bien écrit doit être auto-documenté.
    -   Si un commentaire est absolument nécessaire pour expliquer une logique complexe, **il doit être rédigé en anglais**.

En suivant ces directives, nous nous assurons que le projet reste robuste, évolutif et agréable à maintenir.
