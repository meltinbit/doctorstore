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
    echo "Generating APP_KEY..."
    php artisan key:generate --no-interaction --force
fi

# Only the app (php-fpm) service runs migrations and warms the cache.
# queue and scheduler depend on app being healthy (port 9000 open),
# which is only true after php-fpm starts — i.e., after migrations.
if [ "$1" = "php-fpm" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction

    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Execute the passed command
echo "Starting: $*"
exec "$@"
