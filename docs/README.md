# MALAS — Manga Library Admin System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-3.x-F59E0B?style=flat)](https://filamentphp.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![CI](https://github.com/username/malas/actions/workflows/ci.yml/badge.svg)](https://github.com/username/malas/actions)

Sistem manajemen perpustakaan manga pribadi berbasis web. Admin mengelola koleksi fisik (series, volume, peminjaman), sementara user terdaftar dapat melihat katalog dan status pinjaman mereka. Data manga diambil otomatis dari Jikan API (MyAnimeList), cover disimpan di Cloudflare R2.

---

## Fitur Utama

- **Series Management** — Kelola judul manga dengan data dari MAL: synopsis, cover, status terbit, skor, rank
- **Volume Management** — Nomor volume, ISBN, kondisi fisik, lokasi rak
- **Koleksi per User** — Catat volume mana yang dimiliki siapa
- **Sistem Peminjaman** — Loan tracking lengkap dengan state machine: `pending → active → returned / overdue / lost`
- **Import Jikan** — Search MAL langsung dari panel, import data + auto-download cover ke R2
- **Manajemen User** — Ban user dengan alasan, soft delete, activity log
- **RBAC** — Role `super_admin`, `admin`, `user` via Filament Shield
- **Activity Log** — Pencatatan setiap perubahan data
- **Dark Mode** — Built-in via Filament

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.3) |
| Admin Panel | Filament v3 |
| Database | MySQL 8 / MariaDB 10.4+ |
| Storage | Cloudflare R2 (S3-compatible) |
| Cache & Queue | Database driver |
| Frontend | Livewire (via Filament) + Alpine.js |
| Auth | Filament Breezy (admin) + Laravel Breeze (user portal) |
| RBAC | Filament Shield |
| Testing | Pest PHP |
| CI/CD | GitHub Actions |
| Deployment | VPS Ubuntu 22.04 — Nginx + PHP-FPM |

---

## Prerequisites

- PHP 8.3+ dengan extension: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `intl`, `zip`
- Composer 2.x
- Node.js 20+ & NPM
- MySQL 8 / MariaDB 10.4+
- Cloudflare R2 bucket + API credentials

---

## Installation

```bash
# 1. Clone repo
git clone https://github.com/username/malas.git
cd malas

# 2. Install dependencies
composer install
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi .env (lihat bagian Environment Variables)

# 5. Database
php artisan migrate
php artisan db:seed

# 6. Build assets
npm run build

# 7. (Opsional) Link storage
php artisan storage:link
```

---

## Environment Variables

Salin `.env.example` dan isi variabel berikut:

```env
# App
APP_NAME="MALAS"
APP_ENV=local
APP_KEY=
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=malas
DB_USERNAME=root
DB_PASSWORD=

# Queue (gunakan database driver)
QUEUE_CONNECTION=database

# Cloudflare R2
FILESYSTEM_DISK=r2
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_URL=https://pub-<hash>.r2.dev  # public URL bucket (jika bucket public)

# Mail (untuk notifikasi due date)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Filament
FILAMENT_FILESYSTEM_DISK=r2
```

---

## Struktur Folder

```
malas/
├── app/
│   ├── Filament/               # Resources, Pages, Widgets
│   │   ├── Resources/
│   │   │   ├── SeriesResource.php
│   │   │   ├── VolumeResource.php
│   │   │   ├── CollectionResource.php
│   │   │   ├── LoanResource.php
│   │   │   └── UserResource.php
│   │   ├── Pages/
│   │   └── Widgets/
│   ├── Models/
│   │   ├── Series.php
│   │   ├── Volume.php
│   │   ├── Collection.php
│   │   ├── Loan.php
│   │   └── User.php
│   ├── Services/
│   │   └── JikanService.php
│   ├── Jobs/
│   │   └── ImportMangaFromJikanJob.php
│   └── Policies/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── docs/                       # Dokumentasi proyek
├── tests/
│   ├── Feature/
│   └── Unit/
└── .github/workflows/          # CI/CD pipelines
```

---

## Menjalankan Development Server

```bash
# Jalankan semua sekaligus (requires concurrently)
npm run dev &
php artisan serve

# Atau gunakan Laravel Herd / Valet untuk zero-config
```

Queue worker (untuk import Jikan):

```bash
php artisan queue:work --queue=default
```

Scheduler (untuk update status loan overdue):

```bash
php artisan schedule:work
```

---

## Menjalankan Test

```bash
# Semua test
./vendor/bin/pest

# Dengan coverage
./vendor/bin/pest --coverage

# Filter test tertentu
./vendor/bin/pest --filter SeriesTest
```

---

## Deployment Notes

1. Set `APP_ENV=production` dan `APP_DEBUG=false`
2. Jalankan optimasi cache:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```
3. Queue worker dijalankan via Supervisor
4. Scheduler dikonfigurasi via crontab:
   ```
   * * * * * cd /var/www/malas && php artisan schedule:run >> /dev/null 2>&1
   ```
5. Nginx dikonfigurasi untuk PHP-FPM, root ke `/public`

Lihat [`docs/CICD.md`](./CICD.md) untuk panduan lengkap.

---

## Filament Admin Panel

Akses panel di `/admin`. Default super admin dibuat via seeder:

```bash
php artisan db:seed --class=UserSeeder
# Email: admin@example.com
# Password: password
```

Untuk reset permission Shield setelah menambah resource:

```bash
php artisan shield:generate --all
```

---

## Contributing

1. Fork repo dan buat branch: `git checkout -b feat/nama-fitur`
2. Commit dengan format Conventional Commits: `feat:`, `fix:`, `chore:`
3. Push dan buat Pull Request ke `main`
4. Pastikan semua test lulus sebelum PR

---

## License

MIT — lihat [LICENSE](../LICENSE) untuk detail.
