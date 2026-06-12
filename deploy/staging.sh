#!/bin/bash
set -e

PROJECT_DIR="/var/www/malas-staging"

echo "=== Deploy to Staging ==="

cd "$PROJECT_DIR"

echo "[1/6] Pulling latest code from develop..."
git pull origin develop

echo "[2/6] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[3/6] Running migrations..."
php artisan migrate --force

echo "[4/6] Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/6] Restarting queue workers..."
php artisan queue:restart

echo "[6/6] Clearing stale cache..."
php artisan optimize:clear

echo "=== Staging deploy complete ==="
