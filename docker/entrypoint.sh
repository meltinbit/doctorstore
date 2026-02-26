#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until nc -z "${DB_HOST:-mysql}" "${DB_PORT:-3306}" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done
echo "MySQL is ready."

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY not set — generating one for this session."
    echo "For production, set APP_KEY in your .env file."
    # artisan key:generate needs a .env file to write the key into
    if [ ! -f /var/www/html/.env ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    fi
    php artisan key:generate --no-interaction --force
    # Export the generated key so all subsequent artisan calls and php-fpm inherit it
    export APP_KEY
    APP_KEY=$(grep '^APP_KEY=' /var/www/html/.env | cut -d'=' -f2-)
fi

# Sync built assets into the shared volume so nginx can serve them.
# This runs on every start to ensure assets stay fresh after rebuilds.
echo "Syncing public assets..."
cp -a /var/www/html/public-snapshot/. /var/www/html/public/

# Only the app (php-fpm) service runs migrations and warms the cache.
# queue and scheduler depend on app being healthy (port 9000 open),
# which is only true after php-fpm starts — i.e., after migrations.
if [ "$1" = "php-fpm" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction

    echo "Seeding admin user..."
    php artisan db:seed --class=AdminSeeder --force --no-interaction

    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Execute the passed command
echo "Starting: $*"
exec "$@"
