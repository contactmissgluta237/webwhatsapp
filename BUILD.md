# Generic SaaS - Instructions de Build Automatique

## 🎯 Résumé

Votre projet Generic SaaS est maintenant configuré avec une **initialisation automatique complète** qui gère :
- ✅ Attente des services (MySQL, Redis)
- ✅ Installation des dépendances Composer
- ✅ Génération de clé d'application
- ✅ Migrations de base de données
- ✅ Seeders automatiques
- ✅ Optimisation du cache Laravel
- ✅ Configuration des permissions

## 🚀 Commandes de Build

### 1. Build Standard
```bash
docker compose up -d
```

### 2. Rebuild Complet (Depuis Zéro)
```bash
# Utiliser le script automatique
./docker/scripts/rebuild.sh

# Ou manuellement
docker compose down --volumes --remove-orphans
docker image rm generic-saas-app 2>/dev/null || true
docker compose up --build -d
```

## 🔍 Vérifications Post-Build

### État des Services
```bash
docker compose ps
```

### Logs d'Initialisation
```bash
docker compose logs app
```

### Test de l'Application
```bash
curl -I http://localhost:8082/
```

## 📊 Ports et Services

| Service | Port Local | Port Interne | Description |
|---------|------------|--------------|-------------|
| Nginx | 8082 | 80 | Serveur web |
| MySQL | 3310 | 3306 | Base de données |
| Redis | 6382 | 6379 | Cache/Sessions |
| Mailpit | 8027 | 8025 | Mail testing |
| Selenium | 4446 | 4444 | Tests E2E |

## 🛠️ Gestion des Problèmes

### Conflit de Ports
Si vous avez des conflits de ports, modifiez le fichier `.env` :
```env
NGINX_PORT=8082
MYSQL_PORT=3310
REDIS_PORT=6382
```

### Rebuild Propre
Le script `./docker/scripts/rebuild.sh` garantit :
- Arrêt complet des conteneurs
- Suppression des images
- Nettoyage des volumes (optionnel)
- Reconstruction complète
- Test automatique

## 📝 Logs Importants

### Initialisation Réussie
```
🚀 Starting Laravel initialization...
✅ MySQL is ready!
✅ Redis is ready!
✅ Dependencies already installed
✅ Application key already exists
🗄️ Running database migrations...
✅ Migrations completed!
🧹 Clearing and caching configurations...
✅ Cache optimized!
🔒 Setting permissions...
✅ Permissions set!
🌱 Running database seeders...
✅ Seeders completed!
🎉 Laravel initialization completed successfully!
```

## 🔐 Accès à l'Application

- **URL principale** : http://localhost:8082/
- **Interface mail** : http://localhost:8027/
- **Redirection** : L'app redirige vers `/login` par défaut

## ⚡ Performance

L'initialisation complète prend environ **60-90 secondes** pour :
- Démarrage des conteneurs (10s)
- Attente des services (15s)
- Migrations et seeders (30-45s)
- Optimisation du cache (15s)

## 🎉 Garanties

✅ **Rebuild depuis zéro** : Fonctionne à 100%  
✅ **Base de données** : Migrée et peuplée automatiquement  
✅ **Redis** : Connexion garantie sur port 6382  
✅ **Permissions** : Configurées automatiquement  
✅ **Cache Laravel** : Optimisé au démarrage  

**Votre application est maintenant prête pour le développement et la production !**