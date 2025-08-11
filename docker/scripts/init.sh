#!/bin/bash
set -e

# Attendre que MySQL soit prêt
until nc -z mysql 3306; do
  echo "Waiting for MySQL..."
  sleep 2
done

# Attendre que Redis soit prêt
until nc -z redis 6379; do
  echo "Waiting for Redis..."
  sleep 2
done

echo "Services are ready!"

# Exécuter les migrations si nécessaire
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

# Créer le lien de storage si nécessaire
if [ ! -L "/var/www/html/public/storage" ]; then
    php artisan storage:link
fi

# Optimiser les performances
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer supervisor pour les queues en arrière-plan
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &

# Exécuter la commande passée en paramètre
exec "$@"
