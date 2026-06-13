# Environment Setup

## Prasyarat

| Software | Versi | Keterangan |
|---|---|---|
| PHP | 8.1+ | Extension: pdo_mysql, gd, fileinfo, curl, zip, mbstring, xml |
| Composer | 2.x | PHP package manager |
| Node.js | 18+ | Untuk Vite build |
| npm | 9+ | Frontend package manager |
| MySQL/MariaDB | 10.4+ | XAMPP di Windows, atau standalone |
| Git | Any | Version control |

## Setup Lokal (Windows + XAMPP)

```bash
# 1. Clone dan masuk ke direktori
git clone <repo-url>
cd malas/src

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Salin env example
cp .env.example .env

# 5. Edit .env (lihat tabel di bawah)

# 6. Generate app key
php artisan key:generate

# 7. Buat database di phpMyAdmin atau MySQL CLI
# CREATE DATABASE malas;

# 8. Jalankan migrasi
php artisan migrate

# 9. Build frontend assets
npm run build
# Atau untuk development:
npm run dev

# 10. Jalankan server
php artisan serve
# Buka: http://localhost:8000/admin
```

## Variabel .env Lengkap

### Aplikasi

| Variable | Contoh Nilai | Keterangan |
|---|---|---|
| `APP_NAME` | MALAS | Nama aplikasi |
| `APP_ENV` | local | `local` / `production` |
| `APP_KEY` | (generate) | Generate via `php artisan key:generate` |
| `APP_DEBUG` | true | `false` di production |
| `APP_URL` | http://localhost:8000 | URL base aplikasi |
| `APP_LOCALE` | id | Bahasa default |
| `APP_TIMEZONE` | Asia/Jakarta | Timezone |

### Database

| Variable | Contoh Nilai | Keterangan |
|---|---|---|
| `DB_CONNECTION` | mysql | Driver database |
| `DB_HOST` | 127.0.0.1 | Host MySQL |
| `DB_PORT` | 3306 | Port MySQL |
| `DB_DATABASE` | malas | Nama database |
| `DB_USERNAME` | root | Username MySQL (XAMPP default: root) |
| `DB_PASSWORD` | (kosong) | Password MySQL (XAMPP default: kosong) |

### Queue

| Variable | Nilai | Keterangan |
|---|---|---|
| `QUEUE_CONNECTION` | database | Jangan ubah ke redis — tidak ada Redis |

### Cloudflare R2 Storage

| Variable | Contoh Nilai | Keterangan |
|---|---|---|
| `FILESYSTEM_DISK` | r2 | Default disk → R2 |
| `FILESYSTEM_URL` | https://pub-xxx.r2.dev | R2 public bucket URL |
| `AWS_ACCESS_KEY_ID` | (dari Cloudflare) | R2 Access Key ID |
| `AWS_SECRET_ACCESS_KEY` | (dari Cloudflare) | R2 Secret Access Key |
| `AWS_DEFAULT_REGION` | auto | Selalu `auto` untuk R2 |
| `AWS_BUCKET` | malas-storage | Nama bucket R2 |
| `AWS_ENDPOINT` | https://<account-id>.r2.cloudflarestorage.com | R2 endpoint (dari Cloudflare) |
| `AWS_USE_PATH_STYLE_ENDPOINT` | true | Wajib `true` untuk R2 |

> **PENTING**: Jangan commit nilai credential R2 ke git. File `.env` sudah ada di `.gitignore`.

### Session & Cache

| Variable | Nilai | Keterangan |
|---|---|---|
| `SESSION_DRIVER` | database | Atau `file` untuk lokal |
| `CACHE_STORE` | database | Atau `file` untuk lokal |

### Mail (Opsional)

| Variable | Contoh | Keterangan |
|---|---|---|
| `MAIL_MAILER` | smtp | Driver mail |
| `MAIL_HOST` | smtp.gmail.com | Host SMTP |
| `MAIL_PORT` | 587 | Port SMTP |
| `MAIL_USERNAME` | email@gmail.com | Username SMTP |
| `MAIL_PASSWORD` | (app password) | Password SMTP |

## Test Koneksi R2

```bash
php artisan tinker
>>> Storage::disk('r2')->put('test-connection.txt', 'hello');
>>> Storage::disk('r2')->url('test-connection.txt');
# Harus return: https://pub-xxx.r2.dev/test-connection.txt
```

## Menjalankan Queue Worker

Wajib dijalankan agar Jikan scraping dan job lain berjalan:

```bash
# Development (foreground, auto-restart setiap kode berubah)
php artisan queue:work

# Production (daemon, persistent)
php artisan queue:work --daemon --tries=3 --timeout=120

# Cek job yang failed
php artisan queue:failed

# Retry job yang failed
php artisan queue:retry all
```

## Menjalankan Scheduler

Untuk Jikan auto-scraping berjalan sesuai jadwal:

```bash
# Test manual (jalankan sekali)
php artisan schedule:run

# Production: tambahkan ke crontab
* * * * * cd /path/to/src && php artisan schedule:run >> /dev/null 2>&1
```
