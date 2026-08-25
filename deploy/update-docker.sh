#!/usr/bin/env bash
# =============================================================================
# MALAS — Update Script (Docker)
# Jalankan dari root direktori project: bash deploy/update-docker.sh
# Pull kode terbaru, rebuild image, jalankan migration baru, tanpa menimpa data.
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'
BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }
step()    { echo -e "\n${BOLD}▶ $*${NC}"; }

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_DIR="${PROJECT_ROOT}/deploy"
cd "$PROJECT_ROOT"

[[ -f "artisan" ]] || error "Script harus dijalankan dari project MALAS."
[[ -f "${DEPLOY_DIR}/.env" ]] || error "deploy/.env belum ada — jalankan deploy/deploy-docker.sh dulu."

# =============================================================================
step "Pull kode terbaru"
# =============================================================================
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
info "Branch: ${CURRENT_BRANCH}"
git fetch origin
git pull origin "$CURRENT_BRANCH"
success "Kode terbaru berhasil di-pull."

# =============================================================================
step "Rebuild image & restart container"
# =============================================================================
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" --env-file "${DEPLOY_DIR}/.env" build
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" --env-file "${DEPLOY_DIR}/.env" up -d
success "Container ter-restart dengan image terbaru."

# =============================================================================
step "Jalankan migration baru (jika ada)"
# =============================================================================
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" exec -T app php artisan migrate --force
success "Migration selesai (Laravel skip otomatis kalau tidak ada yang pending)."

# =============================================================================
step "Clear & rebuild cache"
# =============================================================================
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" exec -T app php artisan config:cache
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" exec -T app php artisan route:cache
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" exec -T app php artisan view:cache
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" exec -T app php artisan optimize
success "Cache diperbarui."

# =============================================================================
step "Restart queue worker"
# =============================================================================
docker compose -f "${DEPLOY_DIR}/docker-compose.yml" restart queue
success "Queue worker jalan dengan kode terbaru."

# =============================================================================
echo ""
echo -e "${GREEN}${BOLD}Update selesai!${NC}"
COMMIT=$(git log -1 --pretty=format:"%h %s")
echo -e "  Commit terbaru: ${COMMIT}"
echo ""
