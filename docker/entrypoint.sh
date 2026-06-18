#!/bin/sh
set -e

# Cache Laravel configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations automatically on deployment
php artisan migrate --force

# Ensure the public storage symlink exists so uploaded note images resolve
php artisan storage:link --force || true

# Start FrankenPHP using the base image's entrypoint
exec docker-php-entrypoint frankenphp run --config /etc/caddy/Caddyfile
