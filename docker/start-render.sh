#!/usr/bin/env sh
set -eu

cd /var/www/html

echo "[startup] Running Laravel migrations..."
php artisan migrate --force

echo "[startup] Refreshing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan view:cache

exec /start.sh
