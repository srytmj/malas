# Deployment Checklist

## Setup Lingkungan Baru

- [ ] Clone repo: `git clone <repo-url>`
- [ ] Masuk ke direktori: `cd malas/src`
- [ ] Copy env: `cp .env.example .env`
- [ ] Isi semua variabel di `.env` (lihat [env-setup.md](./env-setup.md))
- [ ] Install PHP dependencies: `composer install`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Install Node dependencies: `npm install`
- [ ] Build assets: `npm run build`
- [ ] Jalankan migrasi: `php artisan migrate`
- [ ] (Opsional) Seed data awal: `php artisan db:seed`

## Checklist Pre-Deployment

- [ ] Semua migration terbaru sudah dijalankan tanpa error
- [ ] `npm run build` berhasil — cek `public/build/` ada isinya
- [ ] Variabel wajib di `.env` sudah diisi:
  - [ ] `APP_KEY` (tidak kosong)
  - [ ] `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - [ ] `FILESYSTEM_DISK=r2`
  - [ ] `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
  - [ ] `AWS_ENDPOINT` (R2 endpoint)
  - [ ] `FILESYSTEM_URL` (R2 public URL)
  - [ ] `QUEUE_CONNECTION=database`
- [ ] `php artisan config:cache` berjalan tanpa error
- [ ] `php artisan route:cache` berjalan tanpa error

## Deployment Steps

1. [ ] Pull kode terbaru: `git pull origin main`
2. [ ] Install dependencies: `composer install --no-dev --optimize-autoloader`
3. [ ] Build frontend: `npm ci && npm run build`
4. [ ] Jalankan migrasi: `php artisan migrate --force`
5. [ ] Clear & rebuild cache:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. [ ] Restart queue worker: `php artisan queue:restart`
7. [ ] Pastikan queue worker berjalan: `php artisan queue:work --daemon`
8. [ ] Pastikan scheduler terdaftar di cron: `* * * * * cd /path/to/src && php artisan schedule:run >> /dev/null 2>&1`

## Post-Deployment Verification

- [ ] Buka `/login` — form muncul, tidak ada error 500
- [ ] Login dengan akun super_admin — berhasil redirect ke `/admin`
- [ ] Dashboard admin memuat data tanpa error
- [ ] Buka `/admin/series` — DataTable AJAX load tanpa error
- [ ] Upload cover series — verifikasi file muncul di R2 bucket
- [ ] Buka `/admin/jikan` — halaman load, schedule list tampil
- [ ] Cek `failed_jobs` kosong: `php artisan queue:failed`
- [ ] Cek log error: `tail -f storage/logs/laravel.log`

## Rollback

Jika ada masalah setelah deploy:

1. [ ] Rollback migrasi terakhir: `php artisan migrate:rollback`
2. [ ] Checkout versi sebelumnya: `git checkout <previous-tag>`
3. [ ] Rebuild: `composer install` + `npm run build`
4. [ ] Clear cache: `php artisan optimize:clear`
5. [ ] Restart queue: `php artisan queue:restart`

## Cloudflare R2 Setup (Sekali Saja)

- [ ] Buat bucket `malas-storage` di Cloudflare R2
- [ ] Set bucket sebagai **Public** (untuk akses cover image)
- [ ] Buat R2 API Token dengan permission `Object Read & Write`
- [ ] Catat: Account ID, Access Key ID, Secret Access Key
- [ ] Isi `.env` dengan credential R2 (jangan commit ke git!)
- [ ] Test koneksi: `php artisan tinker` → `Storage::disk('r2')->put('test.txt', 'ok')`
