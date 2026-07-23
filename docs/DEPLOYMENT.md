# MALAS — Panduan Deployment

> Dokumen ini mencakup cara deploy MALAS ke berbagai environment: **Local Server**, **AWS EC2**, dan **Azure VM**, menggunakan script otomatis maupun manual.

---

## Daftar Isi

- [Prasyarat](#prasyarat)
- [Metode 1: Otomatis dengan deploy.sh](#metode-1-otomatis-dengan-deploysh)
- [Metode 2: Manual Step-by-Step](#metode-2-manual-step-by-step)
- [Platform: AWS EC2](#platform-aws-ec2)
- [Platform: Azure VM](#platform-azure-vm)
- [Platform: Local Server (VPS / Bare Metal)](#platform-local-server-vps--bare-metal)
- [Setelah Deploy: Akses via Domain](#setelah-deploy-akses-via-domain)
- [Memperbarui Kode (update.sh)](#memperbarui-kode-updatesh)
- [Troubleshooting](#troubleshooting)

---

## Prasyarat

Semua platform membutuhkan:

| Komponen | Versi Minimum |
|----------|--------------|
| Ubuntu Server | 24.04 LTS |
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Node.js | 20+ |
| Composer | 2.x |
| Nginx | terbaru |

Sebelum mulai, pastikan kamu punya:
- Akses SSH ke server
- Repo MALAS sudah di-clone ke server
- Credentials SSO whitearchive.id (`SSO_CLIENT_ID` dan `SSO_CLIENT_SECRET`)
- (Opsional) Domain yang sudah diarahkan ke server

---

## Metode 1: Otomatis dengan deploy.sh

Cara tercepat. Satu script handle semuanya.

### Langkah-langkah

**1. Clone repo ke server**

```bash
git clone https://github.com/USERNAME/malas.git /var/www/malas
cd /var/www/malas
```

Kalau repo private, setup SSH key dulu:

```bash
# Generate key (kalau belum ada)
ssh-keygen -t ed25519 -C "server-deploy"
cat ~/.ssh/id_ed25519.pub
# Tambahkan output di atas ke GitHub → Settings → Deploy Keys
git clone git@github.com:USERNAME/malas.git /var/www/malas
```

**2. Jalankan script**

```bash
cd /var/www/malas
bash deploy.sh
```

Script akan:
- Install PHP 8.2, MySQL 8, Nginx, Node 20, Composer
- Membuat database dan user MySQL
- Setup file `.env` (script akan bertanya domain, DB credentials, SSO credentials)
- Jalankan `composer install`, `npm run build`
- Migrasi database + seed menu
- Konfigurasi Nginx otomatis
- Print instruksi untuk setup domain

**3. Ikuti instruksi di akhir script**

Script akan print dua pilihan untuk akses via domain: Cloudflare Tunnel atau SSL langsung. Lihat bagian [Setelah Deploy](#setelah-deploy-akses-via-domain).

---

## Metode 2: Manual Step-by-Step

Gunakan ini kalau ingin kontrol penuh atau script gagal di tengah jalan.

### 1. Install dependency sistem

```bash
sudo apt update
sudo apt install -y nginx mysql-server \
    php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-zip php8.2-intl \
    unzip curl git

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Clone repo

```bash
git clone https://github.com/USERNAME/malas.git /var/www/malas
cd /var/www/malas
```

### 3. Konfigurasi MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE malas_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'malas'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_DISINI';
GRANT ALL PRIVILEGES ON malas_prod.* TO 'malas'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Setup .env

```bash
cp .env.example .env
nano .env
```

Nilai yang wajib diubah:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com
APP_KEY=                          # akan di-generate di langkah berikutnya

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=malas_prod
DB_USERNAME=malas
DB_PASSWORD=PASSWORD_KUAT_DISINI

SESSION_ENCRYPT=true

SSO_CLIENT_ID=isi_dari_whitearchive
SSO_CLIENT_SECRET=isi_dari_whitearchive
SSO_REDIRECT_URI=https://domain-kamu.com/auth/callback
```

### 5. Install dependencies & build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 6. Generate key & migrasi

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=MenuSeeder --force
php artisan storage:link
```

### 7. Cache untuk production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Set permission

```bash
sudo chown -R www-data:www-data /var/www/malas/storage /var/www/malas/bootstrap/cache
sudo chmod -R 775 /var/www/malas/storage /var/www/malas/bootstrap/cache
```

### 9. Konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/malas
```

```nginx
server {
    listen 80;
    server_name domain-kamu.com;
    root /var/www/malas/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/malas /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl enable nginx php8.2-fpm mysql
```

---

## Platform: AWS EC2

### Setup Instance

1. Launch instance: **Ubuntu Server 24.04 LTS**, tipe `t3.small` (minimum) atau `t3.medium` (rekomendasi)
2. Storage: minimal 20 GB GP3
3. Security Group — buka port:

   | Port | Protokol | Source |
   |------|----------|--------|
   | 22 | TCP | IP kamu (untuk SSH) |
   | 80 | TCP | 0.0.0.0/0 |
   | 443 | TCP | 0.0.0.0/0 |

4. Download key pair `.pem`

### Koneksi ke Instance

```bash
chmod 400 key-pair.pem
ssh -i key-pair.pem ubuntu@<EC2_PUBLIC_IP>
```

### Clone & Deploy

```bash
git clone https://github.com/USERNAME/malas.git /var/www/malas
cd /var/www/malas
bash deploy.sh
```

### Domain

- Di AWS Route 53 atau DNS provider: buat **A record** yang mengarah ke Elastic IP EC2
- Gunakan Elastic IP (bukan Public IP biasa) supaya IP tidak berubah saat instance restart

```bash
# Pasang Elastic IP dulu di AWS Console, lalu:
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d domain-kamu.com
```

### Tips AWS

- Aktifkan **AWS Backup** atau snapshot EBS secara berkala
- Pertimbangkan **RDS MySQL** untuk database production yang lebih handal (ganti `DB_HOST` di `.env` ke endpoint RDS)
- Untuk file storage, sudah bisa pakai **S3** langsung — konfigurasi via halaman Admin → Penyimpanan

---

## Platform: Azure VM

### Setup VM

1. Buat VM: **Ubuntu Server 24.04 LTS**, size `B2s` (minimum) atau `B2ms` (rekomendasi)
2. Disk: minimal 30 GB Premium SSD
3. Buka port di **Networking → Inbound port rules**:

   | Port | Protokol |
   |------|----------|
   | 22 | TCP |
   | 80 | TCP |
   | 443 | TCP |

### Koneksi ke VM

```bash
ssh azureuser@<VM_PUBLIC_IP>
```

### Clone & Deploy

```bash
git clone https://github.com/USERNAME/malas.git /var/www/malas
cd /var/www/malas
bash deploy.sh
```

### Domain

Di Azure DNS atau DNS provider eksternal: buat **A record** ke Public IP VM.

```bash
# Pasang SSL
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d domain-kamu.com
```

### Tips Azure

- Gunakan **Static Public IP** (bukan Dynamic) supaya IP tidak berubah
- Untuk storage, bisa pakai **Azure Blob Storage** yang kompatibel S3 — konfigurasi via halaman Admin → Penyimpanan dengan endpoint Azure Blob

---

## Platform: Local Server (VPS / Bare Metal)

Cocok untuk server di rumah / kantor yang terhubung ke internet, atau lab internal.

### Jika punya IP publik

Deploy sama seperti di atas, lalu setup domain:
```bash
sudo certbot --nginx -d domain-kamu.com
```

### Jika tidak punya IP publik (NAT/ISP blokir port)

Gunakan **Cloudflare Tunnel** — tidak perlu IP publik atau buka port router:

```bash
# 1. Install cloudflared
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 \
    -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared

# 2. Login ke Cloudflare (buka link di browser)
cloudflared tunnel login

# 3. Buat tunnel
cloudflared tunnel create malas

# 4. Arahkan domain ke tunnel
cloudflared tunnel route dns malas domain-kamu.com

# 5. Jalankan tunnel (otomatis HTTPS, tidak perlu certbot)
cloudflared tunnel run --url http://localhost:80 malas
```

**Jadikan service supaya otomatis jalan saat boot:**

```bash
sudo cloudflared service install
sudo systemctl enable cloudflared
sudo systemctl start cloudflared
```

Update `APP_URL` di `.env`:
```env
APP_URL=https://domain-kamu.com
```

Lalu rebuild cache:
```bash
php artisan config:cache
```

---

## Setelah Deploy: Akses via Domain

Dua pilihan untuk HTTPS:

### Pilihan A: Cloudflare Tunnel (rekomendasi untuk semua kasus)
- Tidak perlu IP publik
- HTTPS otomatis via Cloudflare
- Gratis (plan Free Cloudflare)
- Instruksi di bagian [Local Server](#jika-tidak-punya-ip-publik-natISP-blokir-port) di atas

### Pilihan B: Certbot SSL (butuh IP publik & domain yang sudah diarahkan)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d domain-kamu.com
# Auto-renew sudah disetup oleh certbot
```

Setelah SSL aktif, update `.env`:
```env
APP_URL=https://domain-kamu.com
SSO_REDIRECT_URI=https://domain-kamu.com/auth/callback
```

Rebuild cache:
```bash
php artisan config:cache
```

---

## Memperbarui Kode (update.sh)

Setiap kali ada kode baru di GitHub, jalankan dari server:

```bash
cd /var/www/malas
bash update.sh
```

Script ini akan:
1. `git pull origin main`
2. Update Composer jika `composer.lock` berubah
3. Rebuild frontend jika ada perubahan di `resources/`
4. Jalankan migration baru jika ada (tidak menimpa data yang sudah ada)
5. Clear & rebuild semua cache
6. Aktifkan maintenance mode selama proses, matikan setelah selesai

### Update manual (jika tidak pakai script)

```bash
cd /var/www/malas
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

---

## Troubleshooting

### Error 500 setelah deploy

```bash
# Cek log Laravel
tail -n 50 /var/www/malas/storage/logs/laravel.log

# Pastikan .env sudah benar
php artisan config:clear
php artisan config:cache
```

### Gambar tidak muncul (403)

```bash
# Pastikan storage symlink ada
ls -la /var/www/malas/public/storage
# Harus menunjuk ke ../storage/app/public

# Kalau tidak ada:
php artisan storage:link
```

### Permission denied di storage

```bash
sudo chown -R www-data:www-data /var/www/malas/storage /var/www/malas/bootstrap/cache
sudo chmod -R 775 /var/www/malas/storage /var/www/malas/bootstrap/cache
```

### Nginx 502 Bad Gateway

```bash
# Cek PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm

# Cek log nginx
sudo tail -n 30 /var/log/nginx/error.log
```

### Migration gagal

```bash
# Lihat status migration
php artisan migrate:status

# Kalau perlu rollback 1 step
php artisan migrate:rollback

# Jangan pernah pakai migrate:fresh di production!
```

### Login SSO tidak berfungsi

Pastikan `SSO_REDIRECT_URI` di `.env` **sama persis** dengan yang didaftarkan di whitearchive.id dashboard:

```env
SSO_REDIRECT_URI=https://domain-kamu.com/auth/callback
```

Setelah ubah `.env`:
```bash
php artisan config:cache
```
