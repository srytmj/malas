#!/bin/bash
set -e

PROJECT_DIR="/var/www/malas"
BACKUP_BUCKET="${R2_BUCKET:-malas-backups}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "=== Deploy to Production ==="

cd "$PROJECT_DIR"

echo "[1/10] Enabling maintenance mode..."
php artisan down --retry=60 --message="MALAS sedang maintenance. Akan kembali dalam beberapa menit."

echo "[2/10] Backing up database..."
pg_dump -h "$DB_HOST" -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "/tmp/malas_backup_${TIMESTAMP}.sql.gz"
echo "Backup saved to /tmp/malas_backup_${TIMESTAMP}.sql.gz"

echo "[3/10] Pulling latest code..."
git fetch --tags
git checkout "${RELEASE_TAG:-main}"

echo "[4/10] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[5/10] Running migrations..."
php artisan migrate --force

echo "[6/10] Caching config..."
php artisan config:cache

echo "[7/10] Caching routes..."
php artisan route:cache

echo "[8/10] Caching views..."
php artisan view:cache

echo "[9/10] Restarting queue workers..."
php artisan queue:restart

echo "[10/10] Disabling maintenance mode..."
php artisan up

echo "=== Production deploy complete ==="
