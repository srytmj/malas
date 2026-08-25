#!/usr/bin/env bash
# =============================================================================
# MALAS — Deploy Script (Docker, generik untuk LXC/VM Proxmox atau Linux manapun)
# Jalankan dari root direktori project: bash deploy/deploy-docker.sh
#
# Asumsi: Docker Engine + Docker Compose plugin SUDAH terpasang di host/LXC/VM.
# Script ini TIDAK menyentuh Proxmox API sama sekali — cuma perlu `docker` dan
# `docker compose` ada di PATH, jadi portable ke Linux manapun (bukan cuma Proxmox).
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'
BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }
step()    { echo -e "\n${BOLD}▶ $*${NC}"; }

# Root project = satu level di atas folder deploy/
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_DIR="${PROJECT_ROOT}/deploy"
cd "$DEPLOY_DIR"

[[ -f "${PROJECT_ROOT}/artisan" ]] || error "Script harus dijalankan dari project MALAS (deploy/deploy-docker.sh)."

# =============================================================================
step "Cek prasyarat"
# =============================================================================
command -v docker &>/dev/null || error "Docker belum terpasang. Install Docker Engine dulu di LXC/VM ini, lalu jalankan ulang script ini."
docker compose version &>/dev/null || error "Docker Compose plugin belum ada ('docker compose' gagal). Install docker-compose-plugin dulu."
success "Docker & Docker Compose terdeteksi."

# =============================================================================
step "Setup file .env"
# =============================================================================
if [[ ! -f ".env" ]]; then
    cp .env.docker.example .env
    info "File deploy/.env dibuat dari .env.docker.example."

    read -rp "Domain / IP:port yang dipakai akses app (contoh: malas.example.com atau 123.45.67.89:8080): " APP_DOMAIN
    read -rp "Port nginx di host [8080]: " APP_PORT
    APP_PORT="${APP_PORT:-8080}"

    while true; do
        read -rsp "Password database Postgres: " DB_PASSWORD
        echo ""
        read -rsp "Konfirmasi password: " DB_PASSWORD2
        echo ""
        [[ "$DB_PASSWORD" == "$DB_PASSWORD2" ]] && break
        warn "Password tidak cocok, coba lagi."
    done

    warn "SSO credentials wajib diisi agar login berfungsi (boleh dikosongkan dulu kalau mau pakai Login via Email saja)."
    read -rp "SSO_CLIENT_ID (opsional): " SSO_CLIENT_ID
    read -rsp "SSO_CLIENT_SECRET (opsional): " SSO_CLIENT_SECRET
    echo ""

    sed -i "s|APP_URL=.*|APP_URL=http://${APP_DOMAIN}|"                 .env
    sed -i "s|APP_PORT=.*|APP_PORT=${APP_PORT}|"                        .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|"                .env
    sed -i "s|SSO_CLIENT_ID=.*|SSO_CLIENT_ID=${SSO_CLIENT_ID}|"          .env
    sed -i "s|SSO_CLIENT_SECRET=.*|SSO_CLIENT_SECRET=${SSO_CLIENT_SECRET}|" .env
    sed -i "s|SSO_REDIRECT_URI=.*|SSO_REDIRECT_URI=http://${APP_DOMAIN}/auth/callback|" .env

    success "deploy/.env dikonfigurasi."
else
    info "deploy/.env sudah ada — dipakai apa adanya (hapus file ini kalau mau setup ulang dari awal)."
fi

# =============================================================================
step "Build image"
# =============================================================================
docker compose build
success "Image ter-build."

# Generate APP_KEY kalau belum ada (lewat container sesaat, sebelum stack full jalan)
if ! grep -q "^APP_KEY=base64:" .env; then
    info "Generate APP_KEY..."
    KEY=$(docker compose run --rm --no-deps app php artisan key:generate --show)
    sed -i "s|APP_KEY=.*|APP_KEY=${KEY}|" .env
    success "APP_KEY di-generate."
fi

# =============================================================================
step "Jalankan container"
# =============================================================================
docker compose up -d
success "Container app, queue, nginx, db jalan."

# =============================================================================
step "Tunggu Postgres siap"
# =============================================================================
info "Menunggu healthcheck database..."
for _ in $(seq 1 30); do
    STATUS=$(docker compose ps -q db | xargs docker inspect -f '{{.State.Health.Status}}' 2>/dev/null || echo "starting")
    [[ "$STATUS" == "healthy" ]] && break
    sleep 2
done
[[ "$STATUS" == "healthy" ]] || error "Database Postgres tidak sehat setelah 60 detik — cek 'docker compose logs db'."
success "Database siap."

# =============================================================================
step "Migration & seeding"
# =============================================================================
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --class=MenuSeeder --force
docker compose exec -T app php artisan storage:link || true
success "Migration dan seeding selesai."

# =============================================================================
step "Migrasi data lama (opsional)"
# =============================================================================
echo ""
read -rp "Ada data lama (SQLite/MySQL) yang mau disalin ke Postgres ini? (y/N): " DO_MIGRATE
if [[ "${DO_MIGRATE,,}" == "y" ]]; then
    warn "Pastikan koneksi sumber ('sqlite' atau 'mysql') masih valid di .env container app SEBELUM lanjut,"
    warn "atau jalankan manual: docker compose exec app php artisan malas:migrate-data --from=sqlite --to=pgsql"
    read -rp "Nama connection sumber [sqlite]: " FROM_CONN
    FROM_CONN="${FROM_CONN:-sqlite}"
    docker compose exec -T app php artisan malas:migrate-data --from="${FROM_CONN}" --to=pgsql --truncate --no-interaction
    success "Migrasi data selesai — cek jumlah baris tiap tabel penting sebelum menganggap cutover selesai."
else
    info "Skip migrasi data (fresh install)."
fi

# =============================================================================
step "Cache production"
# =============================================================================
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan optimize
success "Cache siap."

# =============================================================================
echo ""
echo -e "${GREEN}${BOLD}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║   MALAS (Docker) berhasil di-deploy!      ║${NC}"
echo -e "${GREEN}${BOLD}╚═══════════════════════════════════════════╝${NC}"
echo ""
PORT=$(grep '^APP_PORT=' .env | cut -d= -f2)
echo -e "  URL         : http://localhost:${PORT:-8080}  (lewat nginx container)"
echo -e "  Compose dir : ${DEPLOY_DIR}"
echo ""
echo -e "${BOLD}Perintah berguna:${NC}"
echo -e "  Logs semua service : docker compose -f ${DEPLOY_DIR}/docker-compose.yml logs -f"
echo -e "  Stop stack          : docker compose -f ${DEPLOY_DIR}/docker-compose.yml down"
echo -e "  Restart worker      : docker compose -f ${DEPLOY_DIR}/docker-compose.yml restart queue"
echo ""
echo -e "Untuk HTTPS/domain publik, lihat opsi Cloudflare Tunnel / reverse proxy di docs/DOCKER.md."
echo -e "Untuk update kode di masa mendatang, jalankan: ${BOLD}bash deploy/update-docker.sh${NC}"
echo ""
