# Whatsapp Agent - Guide de Développement Sans Docker

## 🚀 Démarrage Rapide Sans Docker

Parfois, Docker peut être lourd ou poser des problèmes. Ce guide vous permet de faire tourner l'application directement sur votre machine locale.

### 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé sur votre système :

- **PHP 8.2+** avec extensions requises
- **Composer** (gestionnaire de dépendances PHP)
- **Node.js 18+** et **npm**
- **MySQL 8.0+** ou **MariaDB**
- **Redis Server**
- **Git**

### 🔧 Installation des Prérequis Ubuntu/Debian

```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Installation de PHP 8.2 et extensions nécessaires
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd \
    php8.2-intl php8.2-bcmath php8.2-soap php8.2-xdebug

# Installation de Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installation de Node.js et npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Installation de MySQL
sudo apt install -y mysql-server mysql-client

# Installation de Redis
sudo apt install -y redis-server

# Démarrage des services
sudo systemctl start mysql
sudo systemctl start redis-server
sudo systemctl enable mysql
sudo systemctl enable redis-server
```

### 🗄️ Configuration de la Base de Données

```bash
# Connexion à MySQL
sudo mysql -u root

# Création de la base de données et utilisateur
CREATE DATABASE genericsaas;
CREATE USER 'root'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON genericsaas.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 🚀 Installation de l'Application

```bash
# 1. Cloner le projet
git clone <repository-url>
cd web-whatsapp

# 2. Copier le fichier d'environnement sans Docker
cp .env.no-docker .env

# 3. Installer les dépendances PHP
composer install

# 4. Installer les dépendances Node.js
npm install

# 5. Générer la clé d'application (si nécessaire)
php artisan key:generate

# 6. Exécuter les migrations
php artisan migrate

# 7. Compiler les assets
npm run build
```

### 🌱 Seeders (Données de Test)

```bash
# Lancer tous les seeders
php artisan db:seed

# Lancer un seeder spécifique
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CountrySeeder
php artisan db:seed --class=CustomerSeeder

# Recréer la base complètement avec seeders
php artisan migrate:fresh --seed
```

### 🖥️ Démarrage du Serveur de Développement

```bash
# Terminal 1 - Serveur Laravel
php artisan serve --port=8000

# Terminal 2 - Queue Worker (optionnel)
php artisan queue:work

# Terminal 3 - Vite Dev Server (pour le hot-reload des assets)
npm run dev

# Terminal 4 - Scheduler (pour les tâches cron)
php artisan schedule:work
```

L'application sera disponible sur : **http://localhost:8000**

### 🔧 Services Externes Requis

#### WhatsApp Bridge
```bash
# Si vous utilisez le WhatsApp Bridge, démarrez-le séparément
cd whatsapp-bridge
npm install
npm start
# Sera disponible sur localhost:3000
```

#### Ollama (pour l'IA)
```bash
# Installation d'Ollama (Linux)
curl -fsSL https://ollama.ai/install.sh | sh

# Démarrage d'Ollama
ollama serve &

# Téléchargement du modèle
ollama pull llama3.2:1b
```

#### Mailpit (pour les emails en développement)
```bash
# Installation de Mailpit
wget https://github.com/axllent/mailpit/releases/latest/download/mailpit-linux-amd64.tar.gz
tar -xzf mailpit-linux-amd64.tar.gz
sudo mv mailpit /usr/local/bin/

# Démarrage de Mailpit
mailpit &
# Interface web : http://localhost:8025
# SMTP sur : localhost:1025
```

### ⚡ Scripts de Développement Rapide

Créez un script `start-dev.sh` pour démarrer tous les services :

```bash
#!/bin/bash
echo "🚀 Démarrage de l'environnement de développement..."

# Vérification des services
echo "📡 Vérification des services..."
sudo systemctl status mysql | grep -q "active (running)" || sudo systemctl start mysql
sudo systemctl status redis | grep -q "active (running)" || sudo systemctl start redis

# Démarrage de Mailpit en arrière-plan
echo "📧 Démarrage de Mailpit..."
mailpit > /dev/null 2>&1 &

# Démarrage d'Ollama en arrière-plan
echo "🤖 Démarrage d'Ollama..."
ollama serve > /dev/null 2>&1 &

# Démarrage du queue worker
echo "⚙️ Démarrage du queue worker..."
php artisan queue:work > /dev/null 2>&1 &

# Démarrage du scheduler
echo "⏰ Démarrage du scheduler..."
php artisan schedule:work > /dev/null 2>&1 &

echo "✅ Tous les services sont démarrés !"
echo "🌐 Application : http://localhost:8000"
echo "📧 Mailpit : http://localhost:8025"
echo ""
echo "Pour démarrer le serveur Laravel :"
echo "php artisan serve --port=8000"
echo ""
echo "Pour le hot-reload des assets :"
echo "npm run dev"
```

### 🐛 Résolution de Problèmes

#### Problèmes de Permissions
```bash
# Fixer les permissions des dossiers Laravel
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

#### Redis ne démarre pas
```bash
# Vérifier le statut
sudo systemctl status redis

# Redémarrer Redis
sudo systemctl restart redis

# Vérifier la connexion
redis-cli ping
```

#### MySQL ne se connecte pas
```bash
# Vérifier le statut
sudo systemctl status mysql

# Se connecter et vérifier
mysql -u root -p -e "SHOW DATABASES;"
```

#### Port déjà utilisé
```bash
# Vérifier qui utilise le port 8000
sudo lsof -i :8000

# Utiliser un autre port
php artisan serve --port=8001
```

### 🧪 Tests

```bash
# Tests unitaires
php artisan test

# Tests avec couverture
php artisan test --coverage

# Tests Browser (Dusk) - nécessite Chrome/Chromium
php artisan dusk
```

### 📦 Optimisation Production

```bash
# Cache des configurations
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Optimisation Composer
composer install --optimize-autoloader --no-dev

# Build des assets pour production
npm run build
```

### 🔄 Mise à Jour

```bash
# Mise à jour du code
git pull origin main

# Mise à jour des dépendances
composer install
npm install

# Migrations
php artisan migrate

# Rebuild des caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
npm run build
```

### 📊 Monitoring des Services

```bash
# Vérifier tous les services d'un coup
echo "MySQL:" && sudo systemctl is-active mysql
echo "Redis:" && sudo systemctl is-active redis
echo "Processus PHP:" && ps aux | grep "php artisan" | grep -v grep
echo "Mailpit:" && curl -s http://localhost:8025 > /dev/null && echo "✅ Running" || echo "❌ Stopped"
echo "Ollama:" && curl -s http://localhost:11434 > /dev/null && echo "✅ Running" || echo "❌ Stopped"
```

## ✨ Avantages du Développement Sans Docker

- **Performance** : Accès direct aux ressources système
- **Simplicité** : Pas de complexité Docker
- **Debug** : Plus facile de déboguer directement
- **IDE** : Meilleure intégration avec l'IDE
- **Flexibilité** : Configuration fine de chaque service

## 🔚 Arrêt de l'Environnement

```bash
# Arrêter les processus Laravel
pkill -f "php artisan"

# Arrêter Mailpit
pkill mailpit

# Arrêter Ollama
pkill ollama

# Les services système (MySQL, Redis) continuent de tourner
```

---

**Happy Coding!** 🎉
