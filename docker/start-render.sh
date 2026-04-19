#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ "${APP_ENV:-production}" = "production" ] && [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  echo "[startup] Refusing to start with SQLite in production."
  echo "[startup] Set DB_CONNECTION=mysql and provide persistent database credentials."
  exit 1
fi

if [ -z "${APP_KEY:-}" ]; then
  echo "[startup] APP_KEY is missing."
  echo "[startup] Generate one with 'php artisan key:generate --show' and add it in Render."
  exit 1
fi

mkdir -p \
  bootstrap/cache \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

if [ -L public/storage ]; then
  :
elif [ -e public/storage ]; then
  echo "[startup] public/storage already exists and is not a symlink; skipping storage:link."
else
  php artisan storage:link
fi

echo "[startup] Clearing stale Laravel caches..."
php artisan config:clear
php artisan cache:clear || true
php artisan view:clear || true

echo "[startup] Running Laravel migrations..."
attempt=1
max_attempts=10

until php artisan migrate --force; do
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "[startup] Database migration failed after ${max_attempts} attempts."
    exit 1
  fi

  echo "[startup] Database not ready yet. Retrying in 5 seconds..."
  attempt=$((attempt + 1))
  sleep 5
done

echo "[startup] Refreshing Laravel caches..."
php artisan view:cache

exec /start.sh
