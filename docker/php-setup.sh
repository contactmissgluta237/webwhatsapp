#!/bin/bash

# Install required packages
apt-get update
apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    netcat-traditional \
    unzip

# Install PHP extensions
docker-php-ext-install pdo_mysql mbstring zip gd

# Install Redis extension
pecl install redis && docker-php-ext-enable redis

# Install Composer (only if not exists)
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install dependencies if vendor doesn't exist
if [ ! -d "/var/www/html/vendor" ]; then
    cd /var/www/html
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Fix Laravel permissions for new developers
echo "Setting up Laravel permissions..."
cd /var/www/html

# Set ownership to www-data for critical Laravel directories
chown -R www-data:www-data storage bootstrap/cache

# Fix language files permissions (common issue for new devs)
if [ -d "resources/lang" ]; then
    chown -R www-data:www-data resources/lang
    chmod -R 664 resources/lang/*.json 2>/dev/null || true
fi

# Set proper permissions for writable directories
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs/* 2>/dev/null || true

echo "Setup completed with Laravel permissions fixed!"
