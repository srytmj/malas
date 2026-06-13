# Runbook — Prosedur Operasional

## Startup Checklist (Setiap Sesi Development)

```bash
# 1. Pastikan XAMPP MySQL berjalan
# 2. Jalankan dev server
php artisan serve

# 3. Jalankan Vite (untuk hot reload frontend)
npm run dev

# 4. Jalankan queue worker (untuk Jikan scraping)
php artisan queue:work
```

## Jikan Scraping

### Menjalankan Scrape Manual

1. Buka `/admin/jikan`
2. Klik **Scrape Now**
3. Opsional: isi Start Year dan End Year untuk filter manga per periode
4. Klik **Mulai Scraping**
5. Pantau progress di halaman (polling otomatis setiap 2 detik)

### Menambah Jadwal Otomatis

1. Buka `/admin/jikan`
2. Klik **+ Tambah Jadwal**
3. Isi: Nama, Jam (0-23), Menit (0-59), rentang tahun opsional
4. Klik **Simpan**

Jadwal akan aktif saat `php artisan schedule:run` dieksekusi setiap menit oleh cron.

### Scraping Gagal / Stuck

```bash
# Lihat session yang gagal
php artisan tinker
>>> App\Modules\Jikan\Models\JikanScrapeSession::where('status','failed')->latest()->first();

# Lihat job queue yang pending
php artisan queue:monitor

# Lihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Reset session stuck di 'running'
>>> App\Modules\Jikan\Models\JikanScrapeSession::where('status','running')->update(['status'=>'failed','error_message'=>'Manual reset']);
```

## Database Operations

### Backup Manual

```bash
# Windows (XAMPP)
mysqldump -u root malas > backup_$(date +%Y%m%d).sql

# Restore
mysql -u root malas < backup_2026-06-13.sql
```

### Cek Migration Status

```bash
php artisan migrate:status
```

### Reset Development Database

```bash
php artisan migrate:fresh --seed
# PERHATIAN: Hapus semua data!
```

## Queue Management

```bash
# Jalankan worker sekali (untuk development)
php artisan queue:work

# Jalankan worker daemon (untuk production)
php artisan queue:work --daemon --tries=3 --timeout=120

# Restart semua worker (setelah deploy)
php artisan queue:restart

# Monitor queue
php artisan queue:monitor

# Lihat semua failed jobs
php artisan queue:failed

# Hapus semua failed jobs
php artisan queue:flush
```

## R2 Storage

### Test Koneksi

```bash
php artisan tinker
>>> Storage::disk('r2')->put('ping.txt', 'pong');
>>> Storage::disk('r2')->url('ping.txt');
# Expected: https://pub-xxx.r2.dev/ping.txt
>>> Storage::disk('r2')->delete('ping.txt');
```

### Hapus File Orphan

Jika series/volume dihapus tapi cover imagenya masih di R2 (tidak ada auto-cleanup):

```bash
php artisan tinker
>>> Storage::disk('r2')->delete('covers/series/namafile.jpg');
```

## Cache & Config

```bash
# Clear semua cache
php artisan optimize:clear

# Atau spesifik:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild cache (untuk production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting Umum

| Problem | Kemungkinan Penyebab | Solusi |
|---|---|---|
| 500 error di semua halaman | `APP_KEY` kosong | `php artisan key:generate` |
| Cover tidak muncul | R2 credential salah | Cek `.env` AWS_* variables |
| Jikan tidak auto-scrape | Cron/scheduler tidak aktif | Pastikan `php artisan schedule:run` jalan setiap menit |
| Queue tidak jalan | Worker tidak aktif | Jalankan `php artisan queue:work` |
| Route not found | Cache stale | `php artisan route:clear` |
| CSRF token mismatch | Session expired | Refresh halaman, login ulang |
| "Series ini belum punya volume" padahal ada | Volumes belum dimuat (bug lama) | Sudah diperbaiki di session 2026-06-13 |
| TomSelect tidak muncul | Vite build belum run | `npm run build` atau `npm run dev` |

## Log Files

```bash
# Lihat log terbaru
tail -f storage/logs/laravel.log

# Filter error
grep -i "error\|exception" storage/logs/laravel.log | tail -50
```
