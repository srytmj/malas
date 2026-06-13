# Agent Deploy Guide

Panduan bagi AI agent yang membantu proses deployment MALAS.

## Konteks Deployment Saat Ini

MALAS saat ini **hanya berjalan lokal** (XAMPP + Windows). Belum ada server production yang aktif. Panduan ini untuk skenario deployment ke server baru.

## Deploy ke Server Baru (VPS/Shared Hosting)

### Prasyarat Server
- PHP 8.1+ dengan extension: pdo_mysql, gd, fileinfo, curl, zip, mbstring, xml, openssl
- MariaDB/MySQL 10.4+
- Composer 2.x
- Node.js 18+ + npm
- Nginx atau Apache

### Steps

```bash
# 1. Clone repo ke server
git clone <repo-url> /var/www/malas
cd /var/www/malas/src

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Setup env
cp .env.example .env
# Edit .env dengan credential production (DB, R2, APP_KEY, dll)
php artisan key:generate

# 4. Database
mysql -u root -p -e "CREATE DATABASE malas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force

# 5. Permission
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .

# 6. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Queue worker (via systemd atau supervisor)
php artisan queue:work --daemon --tries=3 --timeout=120

# 8. Cron (tambahkan ke crontab)
* * * * * cd /var/www/malas/src && php artisan schedule:run >> /dev/null 2>&1
```

### Nginx Config

```nginx
server {
    listen 80;
    server_name malas.example.com;
    root /var/www/malas/src/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Supervisor Config (Queue Worker)

```ini
[program:malas-queue]
command=php /var/www/malas/src/artisan queue:work --daemon --tries=3 --timeout=120
directory=/var/www/malas/src
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/malas-queue.log
stderr_logfile=/var/log/malas-queue-error.log
```

## Update / Re-deploy

```bash
cd /var/www/malas/src

# Pull kode baru
git pull origin main

# Update dependencies (jika berubah)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Jalankan migrasi baru
php artisan migrate --force

# Rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
php artisan queue:restart
# supervisor akan restart worker secara otomatis
```

## Checklist Sebelum Deploy ke Production

- [ ] `.env` production sudah diisi — **bukan copy dari `.env` development**
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] `APP_URL` sudah sesuai domain production
- [ ] Credential R2 valid dan bucket ada
- [ ] Database backup sudah ada sebelum migrate
- [ ] `php artisan migrate --force` berjalan tanpa error
- [ ] Cover image lama (jika ada dari disk lain) sudah dimigrasi ke R2
- [ ] Login berhasil di URL production
- [ ] Cek `failed_jobs` kosong setelah queue worker restart

## Hal yang TIDAK Boleh Agent Lakukan Tanpa Konfirmasi User

- `php artisan migrate:fresh` — menghapus semua data!
- `php artisan migrate:rollback` — rollback migration production
- Ubah credential di server production langsung
- Force push ke branch main/production
- Hapus atau truncate tabel di database production
