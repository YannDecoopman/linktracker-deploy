#!/bin/sh
set -e

cd /var/www/html

# Run package discover (skipped at build time with --no-scripts)
php artisan package:discover --ansi 2>/dev/null || true

# Run migrations
php artisan migrate --force 2>/dev/null || true

# Cache config/routes/views
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

exec "$@"
