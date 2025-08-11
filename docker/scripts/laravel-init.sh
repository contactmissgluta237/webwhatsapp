#!/bin/bash

set -e

echo "🚀 Starting Laravel initialization..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL connection..."
while ! nc -z mysql 3306; do
    echo "Waiting for MySQL..."
    sleep 2
done
echo "✅ MySQL is ready!"

# Wait for Redis to be ready  
echo "⏳ Waiting for Redis connection..."
while ! nc -z redis 6379; do
    echo "Waiting for Redis..."
    sleep 2
done
echo "✅ Redis is ready!"

cd /var/www/html

# Set permissions ONLY for specific Laravel directories (not everything)
echo "🔧 Setting up Laravel-specific permissions..."
# Créer les dossiers s'ils n'existent pas
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache resources/lang

# Ne changer les permissions QUE pour les dossiers nécessaires
chown -R www-data:www-data storage bootstrap/cache resources/lang
chmod -R 775 storage bootstrap/cache resources/lang
echo "✅ Laravel permissions configured!"

# Install Composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "✅ Dependencies installed!"
else
    echo "✅ Dependencies already installed"
fi

# Generate app key if not exists
if ! grep -q "^APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --no-interaction
    echo "✅ Application key generated!"
else
    echo "✅ Application key already exists"
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force
echo "✅ Migrations completed!"

# Clear and cache configurations
echo "🧹 Clearing and caching configurations..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
echo "✅ Cache optimized!"

# Final check on Laravel-specific directories only
echo "🔒 Final permissions check..."
chown -R www-data:www-data storage bootstrap/cache resources/lang
chmod -R 775 storage bootstrap/cache resources/lang
echo "✅ Final permissions set!"

echo "ℹ️  Seeders are NOT executed automatically."
echo "ℹ️  To populate test data, run: docker exec genericsaas_app php artisan db:seed"

echo "🎉 Laravel initialization completed successfully!"
