# Malas — Deployment via Docker (Postgres)

> Metode ketiga selain [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) (native/bare-metal, MySQL). Jalur ini pakai **PostgreSQL** sebagai database (biar konsisten sama microservice lain), dikemas lewat Docker Compose, dan **generik** — cuma butuh Docker Engine + Compose plugin, jadi jalan di LXC/VM Proxmox, VPS mana pun, atau laptop buat testing lokal. Script-nya **tidak** menyentuh Proxmox API sama sekali.

---

## Kapan pakai metode ini vs `deploy.sh` (native)?

| | Native (`deploy/deploy.sh`) | Docker (`deploy/deploy-docker.sh`) |
|---|---|---|
| Database | MySQL 8+ | **PostgreSQL 16** |
| Install ke | Host langsung (systemd, Nginx, PHP-FPM) | Container (app, nginx, db, queue) |
| Cocok untuk | VPS/EC2/Azure biasa | Homelab/Proxmox, atau mau konsisten sama microservice lain yang sudah pakai Postgres/Docker |
| Isolasi | Tidak — semua share OS host | Ya — tiap service kontainer sendiri |

Kalau kamu sudah pernah deploy native (MySQL/SQLite) dan mau pindah ke sini, lihat bagian [Migrasi Data dari Instalasi Lama](#migrasi-data-dari-instalasi-lama) — **jangan** langsung `deploy-docker.sh` di atas data lama, itu bikin instalasi Postgres baru yang kosong.

---

## Prasyarat

- Docker Engine + Docker Compose plugin (`docker compose version` harus jalan)
- LXC/VM Proxmox (atau host Linux apa pun) dengan akses internet buat pull base image
- Repo Malas sudah di-clone
- Credentials SSO whitearchive.id (boleh dikosongkan dulu kalau mau pakai Login via Email saja)

Kalau LXC Proxmox belum ada Docker:

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $(whoami)
# logout/login ulang biar grup docker kepakai
```

> **Catatan LXC Proxmox**: kalau pakai LXC (bukan VM), pastikan container **unprivileged** tetap dikasih izin nested container (`Options → Features → Nesting` = enabled) di config Proxmox, atau Docker daemon di dalamnya bisa gagal start. VM biasa tidak butuh langkah ini.

---

## Deploy Baru (Fresh Install)

```bash
git clone https://github.com/USERNAME/malas.git /opt/malas
cd /opt/malas
bash deploy/deploy-docker.sh
```

Script akan:
1. Cek `docker` dan `docker compose` tersedia
2. Setup `deploy/.env` dari `deploy/.env.docker.example` (nanya domain, port, password Postgres, SSO credentials)
3. Generate `APP_KEY`
4. Build image (`deploy/Dockerfile`, multi-stage: Node build → Composer → PHP-FPM)
5. Jalankan stack: `app` (PHP-FPM), `queue` (worker), `nginx`, `db` (Postgres 16)
6. Tunggu Postgres healthy, lalu migrate + seed menu
7. Tanya apakah mau migrasi data dari instalasi lama (lihat bagian di bawah)
8. Cache production (`config:cache`, `route:cache`, dst)

Setelah selesai, app bisa diakses di `http://<host>:<APP_PORT>` (default port `8080`).

### Struktur file (`deploy/`)

```
deploy/
  Dockerfile                   — multi-stage build (frontend, vendor, runtime)
  docker-compose.yml           — stack production (app, queue, nginx, db)
  docker-compose.override.yml  — override buat dev lokal (bind mount + Vite HMR), auto-load
  nginx.conf                   — reverse proxy PHP-FPM
  .env.docker.example          — template env buat compose (DB_CONNECTION=pgsql)
  deploy-docker.sh             — deploy Docker dari nol
  update-docker.sh             — update kode di deploy Docker yang sudah jalan
  deploy.sh / update.sh        — deploy/update native (lihat DEPLOYMENT.md)
```

---

## Testing / Development Lokal

`docker-compose.override.yml` otomatis ke-load bareng `docker-compose.yml` (perilaku default Docker Compose) kalau dijalankan dari dalam folder `deploy/`:

```bash
cd deploy
cp .env.docker.example .env   # isi manual, atau jalankan deploy-docker.sh biasa
docker compose up -d
```

Override ini bind-mount source code (bukan hasil build) dan nambah service `vite` buat HMR (`http://localhost:5173`). Cocok buat coba-coba lokal tanpa install PHP/Node di host sama sekali.

Untuk stack production murni (tanpa override), jalankan eksplisit dengan `-f`:

```bash
docker compose -f docker-compose.yml up -d
```

---

## Migrasi Data dari Instalasi Lama

Format backup `.sql` dari halaman Admin Settings (`DatabaseBackupController`) sudah driver-aware (SQLite/MySQL/Postgres — beda quote identifier dan beda cara toggle FK check), tapi cara paling robust buat pindah antar **tiga** engine berbeda sekaligus adalah command baru `malas:migrate-data` — nyalin data baris-per-baris lewat query builder Laravel (bukan lewat teks SQL), jadi nggak peduli sama sekali soal beda dialect SQL antar SQLite/MySQL/Postgres.

**Kapan dipakai:** kamu sudah punya instalasi lama (native, SQLite dev atau MySQL prod) dan mau pindah ke Postgres di Docker ini tanpa kehilangan data.

### Cara 1 — lewat `deploy-docker.sh` (paling gampang)

Script fresh-install di atas otomatis nanya "Ada data lama yang mau disalin?" di step terakhir sebelum caching. Jawab `y`, isi nama connection sumber (`sqlite` atau `mysql`), dan pastikan config koneksi sumber itu ada di `.env` container `app` (kalau sumbernya MySQL server terpisah, tambahkan env `DB_HOST`/`DB_USERNAME`/dst untuk koneksi `mysql` sebelum lanjut — Laravel sudah otomatis punya connection `mysql` di `config/database.php`, tinggal diisi).

### Cara 2 — manual, kapan saja setelah stack jalan

```bash
cd deploy
docker compose exec app php artisan malas:migrate-data --from=sqlite --to=pgsql --truncate
```

Opsi:
- `--from` — nama connection sumber (`sqlite`, `mysql`, atau connection lain yang terdaftar di `config/database.php`)
- `--to` — nama connection tujuan, default `pgsql`
- `--truncate` — kosongkan dulu tabel tujuan sebelum menyalin (aman dipakai berkali-kali/re-run kalau migrasi gagal di tengah jalan)
- `--chunk=500` — jumlah baris per batch insert, naikkan kalau datanya besar

Command ini menyalin **semua** tabel (termasuk `users`, beda dengan backup `.sql` biasa yang sengaja skip `users` karena dikelola SSO) — cocok buat cutover penuh ke database baru. Jalankan `php artisan migrate` dulu di sisi tujuan (sudah otomatis dilakukan `deploy-docker.sh`) supaya semua tabel ada sebelum menyalin.

**Setelah migrasi data selesai**, cek manual jumlah baris tabel penting (`series`, `collections`, `users`) di kedua sisi sebelum menganggap cutover selesai dan mematikan instalasi lama.

---

## Update Kode

```bash
cd /opt/malas
bash deploy/update-docker.sh
```

Melakukan `git pull`, rebuild image, restart container, jalankan migration baru (kalau ada), rebuild cache, restart queue worker — pola yang sama dengan `deploy/update.sh` versi native.

---

## HTTPS / Akses Domain Publik

Sama seperti metode native (lihat [`docs/DEPLOYMENT.md`](DEPLOYMENT.md#setelah-deploy-akses-via-domain)) — nginx container cuma dengar di port HTTP (`APP_PORT`, default `8080`). Untuk HTTPS:

- **Cloudflare Tunnel (rekomendasi, tanpa IP publik)**: install `cloudflared` di host (LXC/VM) — bukan di dalam container — arahkan tunnel ke `http://localhost:${APP_PORT}`.
- **Certbot/reverse proxy lain di host**: kalau punya IP publik, taruh Nginx/Caddy/Traefik lain di host yang reverse-proxy ke `http://localhost:${APP_PORT}`, urus SSL di situ.

---

## Troubleshooting

### `docker compose` gagal, "Cannot connect to the Docker daemon"

Docker service belum jalan atau user belum masuk grup `docker`:
```bash
sudo systemctl start docker
sudo usermod -aG docker $(whoami)   # lalu logout/login ulang
```

### Database `db` tidak pernah "healthy"

```bash
docker compose logs db
```
Password di `deploy/.env` (`DB_PASSWORD`) harus sudah terisi sebelum `db` pertama kali start — kalau container `db` sudah pernah dibuat dengan password lain, volume `db-data` menyimpan password lama. Hapus volume kalau memang mau reset total (**data hilang**):
```bash
docker compose down -v
```

### Error 500, cek log

```bash
docker compose exec app tail -n 50 storage/logs/laravel.log
```

### Upload backup gagal (file besar)

`upload_max_filesize`/`post_max_size` sudah dinaikkan ke 128M di image (`deploy/Dockerfile`) — kalau masih gagal, cek juga limit di reverse proxy depan nginx container (kalau ada).

### Storage/cover gambar tidak muncul

```bash
docker compose exec app php artisan storage:link
```
Symlink butuh permission tulis ke `public/` — sudah di-handle di `Dockerfile` (`chown` ke user `malas` non-root), tapi kalau pernah override image ini, pastikan `public/` owned oleh user yang menjalankan PHP-FPM.
