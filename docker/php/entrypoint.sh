#!/bin/bash
set -e

cd /var/www/html

# Installer les dependances si vendor/ n'existe pas
if [ ! -d "vendor" ]; then
    echo "[SIP] Installing Composer dependencies..."
    composer install --optimize-autoloader --no-interaction
fi

# Generer la cle si absente
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "[SIP] Generating application key..."
    php artisan key:generate --force
fi

# Attendre que MariaDB soit pret (via mysqladmin)
echo "[SIP] Waiting for database at ${DB_HOST:-mariadb}:${DB_PORT:-3306}..."
MAX_RETRIES=30
RETRY=0
until mysqladmin ping -h "${DB_HOST:-mariadb}" -P "${DB_PORT:-3306}" -u "${DB_USERNAME:-root}" -p"${DB_PASSWORD}" --silent 2>/dev/null; do
    RETRY=$((RETRY+1))
    if [ $RETRY -ge $MAX_RETRIES ]; then
        echo "[SIP] ERROR: Database not reachable after ${MAX_RETRIES} attempts"
        break
    fi
    echo "[SIP] Waiting for database... (attempt ${RETRY}/${MAX_RETRIES})"
    sleep 3
done
echo "[SIP] Database is ready"

# Migrations
echo "[SIP] Running migrations..."
php artisan migrate --force

# Cache (production)
if [ "$APP_ENV" = "production" ]; then
    echo "[SIP] Caching config for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Permissions storage
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Storage link
php artisan storage:link 2>/dev/null || true

echo "[SIP] Ready — starting services..."
exec "$@"
