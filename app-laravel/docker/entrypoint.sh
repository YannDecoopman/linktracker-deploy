#!/bin/sh
set -e

# Copy public files to shared volume for nginx
if [ -d /var/www/html/public-shared ]; then
    cp -r /var/www/html/public/* /var/www/html/public-shared/
fi

# Run migrations and cache on first deploy
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan storage:link 2>/dev/null || true
fi

exec "$@"
