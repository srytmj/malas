#!/usr/bin/env bash
# =============================================================================
# MALAS — Update Script
# Jalankan dari root direktori project: bash update.sh
# Pull kode terbaru dari GitHub tanpa menimpa database.
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'
BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }
step()    { echo -e "\n${BOLD}▶ $*${NC}"; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

[[ -f "artisan" ]] || error "Script harus dijalankan dari root direktori project MALAS."

echo -e "${BOLD}MALAS — Update${NC}"
echo "Mengambil kode terbaru dari GitHub..."
echo ""

# =============================================================================
step "Aktifkan maintenance mode"
# =============================================================================
php artisan down --render="errors.503" || true
info "Maintenance mode aktif."

# =============================================================================
step "Pull kode terbaru"
# =============================================================================
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
info "Branch: ${CURRENT_BRANCH}"

git fetch origin
git pull origin "$CURRENT_BRANCH"
success "Kode terbaru berhasil di-pull."

# =============================================================================
step "Update PHP dependencies"
# =============================================================================
if git diff HEAD@{1} HEAD --name-only 2>/dev/null | grep -q "composer.lock"; then
    info "composer.lock berubah — update dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    success "Composer update selesai."
else
    info "Tidak ada perubahan composer.lock — skip."
fi

# =============================================================================
step "Build ulang frontend"
# =============================================================================
if git diff HEAD@{1} HEAD --name-only 2>/dev/null | grep -qE "^resources/|package-lock.json|package.json|vite.config"; then
    info "Ada perubahan frontend — rebuild..."
    npm ci
    npm run build
    success "Frontend build selesai."
else
    info "Tidak ada perubahan frontend — skip."
fi

# =============================================================================
step "Jalankan migration baru (jika ada)"
# =============================================================================
PENDING=$(php artisan migrate:status --no-ansi 2>/dev/null | grep "Pending" | wc -l || echo "0")
if [[ "$PENDING" -gt 0 ]]; then
    info "${PENDING} migration baru ditemukan — menjalankan..."
    php artisan migrate --force
    success "Migration selesai."
else
    info "Tidak ada migration baru."
fi

# =============================================================================
step "Clear & rebuild cache"
# =============================================================================
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
success "Cache diperbarui."

# =============================================================================
step "Nonaktifkan maintenance mode"
# =============================================================================
php artisan up
success "Aplikasi kembali online."

# =============================================================================
echo ""
echo -e "${GREEN}${BOLD}Update selesai!${NC}"
COMMIT=$(git log -1 --pretty=format:"%h %s")
echo -e "  Commit terbaru: ${COMMIT}"
echo ""
