FROM php:8.2-fpm

# Installation des dépendances et extensions Laravel
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip netcat-openbsd libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Note: Redis extension installation skipped due to network limitations in build environment
# In production, add: && pecl install redis && docker-php-ext-enable redis

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration PHP pour Laravel
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/laravel.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/laravel.ini

# Create QR codes directory with proper permissions
RUN mkdir -p /var/www/html/public/qrcodes && \
    chown -R www-data:www-data /var/www/html/public && \
    chmod -R 775 /var/www/html/public

WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
