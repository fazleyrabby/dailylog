#!/usr/bin/env bash
#
# DailyLOG production deploy script.
#
# Run on the VPS, from the project root, after pulling new code:
#   ./bin/deploy.sh
#
# Why this exists: the app is fast (single-digit ms locally), but a VPS running
# in dev mode is not. Debug mode + an installed Debugbar instruments every
# query/view/model and writes a heavy payload per request, turning ~100ms pages
# into multi-second ones. This script forces the production-optimized path:
# no dev deps (drops Debugbar), optimized autoloader, cached config/routes/views,
# and a runtime restart so OPcache + cached config actually reload.
#
# Prereqs (one-time, in the VPS .env):
#   APP_ENV=production
#   APP_DEBUG=false
#   DEBUGBAR_ENABLED=false
#   LOG_LEVEL=error
#
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Installing dependencies (no dev, optimized autoloader)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Building front-end assets"
npm ci
npm run build

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Clearing then caching config / routes / views / events"
php artisan optimize:clear
php artisan optimize

# Restart the runtime so OPcache and the cached config reload. FrankenPHP /
# Octane workers hold the old bootstrapped state in memory until restarted.
# Adjust the service name to match your setup.
RUNTIME_SERVICE="${RUNTIME_SERVICE:-frankenphp}"
echo "==> Restarting runtime: ${RUNTIME_SERVICE}"
if command -v systemctl >/dev/null 2>&1 && systemctl list-units --type=service 2>/dev/null | grep -q "${RUNTIME_SERVICE}"; then
    sudo systemctl restart "${RUNTIME_SERVICE}"
elif php artisan list 2>/dev/null | grep -q "octane:reload"; then
    php artisan octane:reload
else
    echo "    !! Could not auto-detect the runtime service."
    echo "    !! Restart it manually (e.g. 'sudo systemctl restart ${RUNTIME_SERVICE}')."
fi

echo "==> Done. Verify OPcache is enabled:  php -i | grep opcache.enable"
