# Whatsapp Agent - Guide de Développement

## 🚀 Démarrage Rapide pour Développeurs

### Prérequis
- Docker & Docker Compose
- Git

### Installation Initiale

```bash
# 1. Cloner le projet
git clone <repository-url>
cd generic-saas

# 2. Démarrer l'environnement Docker
docker compose up -d

# 3. L'application sera disponible sur http://localhost:8082
```

**C'est tout !** Le système d'initialisation automatique se charge de :
- ✅ Attendre que MySQL et Redis soient prêts
- ✅ Installer les dépendances Composer
- ✅ Générer la clé d'application Laravel
- ✅ Exécuter les migrations de base de données
- ✅ Optimiser le cache Laravel

## 🌱 Seeders (Données de Test)

**Important** : Les seeders ne s'exécutent **PAS** automatiquement pour éviter de polluer votre environnement.

### Lancer les Seeders Manuellement

```bash
# Lancer tous les seeders
docker exec genericsaas_app php artisan db:seed

# Lancer un seeder spécifique
docker exec genericsaas_app php artisan db:seed --class=UserSeeder
docker exec genericsaas_app php artisan db:seed --class=CountrySeeder
docker exec genericsaas_app php artisan db:seed --class=CustomerSeeder
```

### Recréer la Base de Données + Seeders

```bash
# Supprimer et recréer complètement la DB avec seeders
docker exec genericsaas_app php artisan migrate:fresh --seed
```

## 🔄 Rebuild Complet

Pour un nouveau développeur ou après des changements majeurs :

```bash
# Script automatique (recommandé)
./docker/scripts/rebuild.sh

# Ou commandes manuelles
docker compose down --volumes --remove-orphans
docker compose up --build -d
```

## 📊 Services et Ports

| Service | URL/Port | Description |
|---------|----------|-------------|
| Application | http://localhost:8082 | App Laravel principale |
| Interface Mail | http://localhost:8027 | Mailpit (emails de test) |
| Base de données | localhost:3310 | MySQL (externe) |
| Redis | localhost:6382 | Cache/Sessions (externe) |
| Selenium | localhost:4446 | Tests E2E |

## 🛠️ Commandes Utiles

### Artisan
```bash
# Accéder au conteneur
docker exec -it genericsaas_app bash

# Commandes Artisan directes
docker exec genericsaas_app php artisan migrate
docker exec genericsaas_app php artisan make:controller UserController
docker exec genericsaas_app php artisan queue:work
```

### Base de Données
```bash
# Status des migrations
docker exec genericsaas_app php artisan migrate:status

# Rollback
docker exec genericsaas_app php artisan migrate:rollback

# Tinker (REPL Laravel)
docker exec -it genericsaas_app php artisan tinker
```

### Cache & Configuration
```bash
# Vider les caches
docker exec genericsaas_app php artisan optimize:clear

# Optimiser pour production
docker exec genericsaas_app php artisan config:cache
docker exec genericsaas_app php artisan route:cache
```

## 📝 Logs & Debugging

```bash
# Logs de l'application Laravel
docker compose logs app

# Logs en temps réel
docker compose logs -f app

# Logs de tous les services
docker compose logs
```

---

# Docker Utilities pour Projets Laravel

Cette section contient un ensemble de fichiers de configuration Docker et de scripts shell conçus pour vous aider à configurer rapidement un environnement de développement et de production dockerisé pour vos projets Laravel.

## Table des Matières
1.  [Prérequis](#1-prérequis)
2.  [Utilisation des Utilitaires](#2-utilisation-des-utilitaires)
3.  [Ajout de Mailpit pour les Tests d'Email](#3-ajout-de-mailpit-pour-les-tests-demail)
4.  [Comprendre les Fichiers](#4-comprendre-les-fichiers)
5.  [Aperçu des Scripts](#5-aperçu-des-scripts)
6.  [Dépannage](#6-dépannage)
7.  [Exposer Votre Application Locale en Ligne avec Serveo](#7-exposer-votre-application-locale-en-ligne-avec-serveo)

---

## 1. Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre système :

*   **Docker** (20.10+ recommandé)
*   **Docker Compose** (2.0+ recommandé)
*   **Git**

---

## 2. Utilisation des Utilitaires

Suivez ces étapes pour intégrer ces utilitaires Docker dans votre projet Laravel :

1.  **Copier les Fichiers vers Votre Projet :**
    Copiez tout le contenu de `docker-utils` (en excluant ce `README.md` si vous le souhaitez, ou renommez-le) dans la racine de votre projet Laravel.

    ```bash
    # Exemple : Si vous avez copié ce dossier comme 'docker-utils'
    cp -r docker-utils/* /chemin/vers/votre/projet/laravel/
    cd /chemin/vers/votre/projet/laravel/
    ```

2.  **Ajuster `.env.docker` :**
    Renommez `.env.docker` en `.env` (si vous n'en avez pas déjà un) et mettez à jour les variables à l'intérieur pour correspondre aux besoins de votre projet.

    ```bash
    cp .env.docker .env
    # Ouvrir .env et ajuster les variables
    nano .env # ou votre éditeur préféré
    ```

3.  **Réviser `docker-compose.yml` :**
    Ce fichier définit vos services Docker principaux (app, base de données, Nginx, Redis, Selenium).

4.  **Rendre les Scripts Exécutables :**
    Assurez-vous que les scripts shell sont exécutables :

    ```bash
    chmod +x install.sh launch.sh stop.sh
    ```

5.  **Exécuter la Configuration Initiale :**
    Exécutez le script `install.sh`. Cela construira vos images Docker, démarrera les conteneurs, installera les dépendances Composer, exécutera les migrations et peuplera votre base de données.

    ```bash
    ./install.sh
    ```

---

## 3. Ajout de Mailpit pour les Tests d'Email

Mailpit est un excellent outil pour les tests d'email locaux. Voici comment l'intégrer :

1.  **Ajouter le Service Mailpit à `docker-compose.yml` :**
    Ouvrez votre fichier `docker-compose.yml` et ajoutez le bloc de service suivant, de préférence près de votre service `redis` :

    ```yaml
      mailpit:
        image: axllent/mailpit
        container_name: mailpit
        restart: unless-stopped
        ports:
          - "8025:8025" # Interface web pour Mailpit
          - "1025:1025" # Port SMTP pour envoyer des emails
        networks:
          - laravel # Assurez-vous qu'il est sur le même réseau que votre app
    ```

2.  **Configurer Laravel pour Utiliser Mailpit dans `.env` :**
    Ouvrez votre fichier `.env` et définissez la configuration de votre mailer pour pointer vers Mailpit :

    ```dotenv
    MAIL_MAILER=smtp
    MAIL_HOST=mailpit # C'est le nom du service défini dans docker-compose.yml
    MAIL_PORT=1025
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-hello@example.com}"
    MAIL_FROM_NAME="${APP_NAME}"
    ```

3.  **Redémarrer les Services Docker :**
    Après avoir modifié `docker-compose.yml`, vous devez redémarrer vos services Docker pour que les changements prennent effet :

    ```bash
    ./stop.sh
    ./launch.sh
    ```

    Vous devriez maintenant pouvoir accéder à l'interface web de Mailpit à `http://localhost:8025` et tous les emails envoyés par votre application Laravel y seront interceptés.

---

## 4. Comprendre les Fichiers

*   **`docker-compose.yml`** : Définit les services Docker pour votre environnement de développement (PHP-FPM, Nginx, MySQL, Redis, Selenium).
*   **`docker-compose.prod.yml`** : (Optionnel, mais recommandé) Surcharges et étend `docker-compose.yml` avec des configurations spécifiques à la production (par exemple, Dockerfiles optimisés, différentes configs Nginx, limites de ressources).
*   **`docker/` directory** : Contient les contextes de construction et configurations Docker :
    *   `docker/php/` : `Dockerfile` pour le conteneur PHP-FPM, `supervisord.conf` pour la gestion des processus, `start.sh` pour la logique de démarrage du conteneur, `crontab` pour les tâches planifiées, et `local.ini` pour les paramètres PHP.
    *   `docker/nginx/` : `default.conf` pour la configuration du bloc serveur Nginx.
    *   `docker/mysql/` : Espace réservé pour les fichiers de configuration MySQL (par exemple, `my.cnf`).
*   **`.env.docker`** : Un modèle pour votre fichier `.env`, pré-configuré avec des variables d'environnement spécifiques à Docker.

---

## 5. Aperçu des Scripts

*   **`install.sh`** : Automatise la configuration initiale : copie de `.env.docker`, construction et démarrage des conteneurs, installation des dépendances Composer, génération de la clé de l'application, exécution des migrations et peuplement, et démarrage des workers de la file d'attente.
*   **`launch.sh`** : Démarre tous les services Docker définis dans `docker-compose.yml` (et `docker-compose.prod.yml` si utilisé) en mode détaché.
*   **`stop.sh`** : Arrête et supprime tous les conteneurs, réseaux et volumes Docker définis dans votre configuration Docker Compose.

---

## 6. Dépannage

*   **Conteneur qui ne démarre pas :** Vérifiez `docker logs <nom_conteneur>` pour des messages d'erreur spécifiques.
*   **`502 Bad Gateway` :** Indique souvent que Nginx ne peut pas se connecter à PHP-FPM. Vérifiez les configurations réseau dans `docker-compose.yml` et `fastcgi_pass` dans la config Nginx.
*   **Problèmes de connexion à la base de données :** Vérifiez les logs du conteneur MySQL et assurez-vous que `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sont corrects dans votre `.env`.
*   **Erreurs de permissions :** Assurez-vous que les répertoires `storage` et `bootstrap/cache` ont les bonnes permissions d'écriture pour l'utilisateur exécutant PHP-FPM à l'intérieur du conteneur.

---

## 7. Exposer Votre Application Locale en Ligne avec Serveo

Serveo est un outil fantastique pour exposer votre serveur de développement local sur internet, le rendant accessible pour les webhooks, démos, ou tests sur des appareils externes.

1.  **Assurez-vous que Votre Application Fonctionne Localement :**
    Avant d'utiliser Serveo, assurez-vous que votre application Laravel fonctionne et est accessible sur votre machine locale (ex : `http://localhost:8082`). Si vous utilisez la configuration Docker fournie, assurez-vous que vos conteneurs sont en marche :

    ```bash
    docker compose up -d
    ```

2.  **Exposer Votre Application avec Serveo :**
    Ouvrez votre terminal et exécutez la commande suivante. Serveo vous fournira automatiquement une URL publique.

    ```bash
    ssh -R 80:localhost:8082 serveo.net
    ```

    *   **`80` (premier chiffre) :** Port public sur Serveo
    *   **`localhost:8082` :** Adresse et port de votre application Laravel locale. Ajustez le port si votre application est exécutée sur un port différent (par exemple, `localhost:8000`).

3.  **Accéder à Votre Application :**
    Après avoir exécuté la commande, Serveo vous fournira une URL publique (ex : `https://random-string.serveo.net`). Vous pouvez maintenant accéder à votre application Laravel locale via cette URL depuis n'importe où avec un accès internet.

4.  **Considérations pour Laravel :**
    *   **`APP_URL` :** Pour que certaines fonctionnalités Laravel (génération d'assets, URLs signées) fonctionnent correctement avec l'URL publique Serveo, vous pourriez avoir besoin de mettre à jour temporairement votre `APP_URL` dans votre fichier `.env` vers l'URL Serveo (ex : `APP_URL=https://random-string.serveo.net`). N'oubliez pas de revenir à la configuration initiale quand vous avez terminé.
    *   **HTTPS :** Serveo fournit automatiquement HTTPS, donc votre URL publique sera `https://`.
    *   **Sécurité :** Soyez conscient qu'exposer votre machine locale sur internet comporte des risques de sécurité. N'exposez que ce qui est nécessaire et pour la durée requise.

---

## 🔧 Développement Avancé

### Structure du Projet
- `app/` - Code application Laravel
- `docker/` - Configuration Docker
- `resources/views/` - Templates Blade
- `routes/web.php` - Routes web
- `database/migrations/` - Migrations DB
- `database/seeders/` - Seeders

### Variables d'Environnement
Le fichier `.env` est automatiquement configuré pour Docker. Variables importantes :

```env
APP_URL=http://localhost:8082
DB_HOST=mysql
DB_PORT=3306
REDIS_HOST=redis
REDIS_PORT=6379
```

## 🚨 Problèmes Courants

### Port déjà utilisé
Si un port est occupé, modifiez `.env` :
```env
NGINX_PORT=8083  # Au lieu de 8082
MYSQL_PORT=3311  # Au lieu de 3310
```

### Conteneurs qui ne démarrent pas
```bash
# Vérifier les logs
docker compose logs

# Redémarrer complètement
docker compose down
docker compose up -d
```

### Problèmes de permissions
```bash
# Fixer les permissions (si nécessaire)
docker exec genericsaas_app chown -R www-data:www-data storage bootstrap/cache
```

## 🧪 Tests

```bash
# Tests unitaires
docker exec genericsaas_app php artisan test

# Tests Browser (Dusk)
docker exec genericsaas_app php artisan dusk
```

## 📚 Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- Documentation spécifique du projet : `BUILD.md`

---

**Bienvenue dans l'équipe ! 🎉**