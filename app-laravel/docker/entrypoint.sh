#!/bin/sh
set -e

cd /var/www/html

# Clear stale cache from build (may reference dev-only packages like Sail)
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Run package discover (skipped at build time with --no-scripts)
php artisan package:discover --ansi 2>/dev/null || true

# Run migrations (show output for debugging)
php artisan migrate --force || echo "WARNING: Migrations failed"

# Cache config/routes/views
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

# Fix permissions after artisan commands (run as root, php-fpm runs as www-data)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

exec "$@"
