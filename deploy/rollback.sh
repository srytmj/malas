#!/bin/bash
set -e

PROJECT_DIR="/var/www/malas"
PREVIOUS_HASH="${1:-ORIG_HEAD}"

echo "=== Rollback Production ==="

cd "$PROJECT_DIR"

echo "[1/7] Enabling maintenance mode..."
php artisan down

echo "[2/7] Rolling back to previous release: ${PREVIOUS_HASH}..."
git reset --hard "$PREVIOUS_HASH"

echo "[3/7] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[4/7] Rolling back last migration (if needed)..."
php artisan migrate:rollback --step=1 --force || echo "Migration rollback skipped or not needed."

echo "[5/7] Clearing and rebuilding cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[6/7] Restarting queue workers..."
php artisan queue:restart

echo "[7/7] Disabling maintenance mode..."
php artisan up

echo "=== Rollback complete ==="
echo "IMPORTANT: Notify the team about this rollback!"
