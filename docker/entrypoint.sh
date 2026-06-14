#!/bin/sh
set -e

# Cache Laravel configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations automatically on deployment
php artisan migrate --force

# Start FrankenPHP in worker mode to keep Laravel in memory
exec docker-php-entrypoint frankenphp run --config /etc/caddy/Caddyfile --worker public/index.php
