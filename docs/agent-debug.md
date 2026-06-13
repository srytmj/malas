# Agent Debug Guide

Panduan bagi AI agent (Claude, Copilot, dll.) yang membantu debug MALAS.

## Konteks Penting untuk Agent

### Stack
- **Laravel 12**, **PHP 8.x**, **MariaDB 10.4.32** (XAMPP)
- **NO Redis** — queue driver: `database`
- **NO Filament** — admin panel custom Blade
- **Cloudflare R2** sebagai default filesystem (`FILESYSTEM_DISK=r2`)
- **Alpine.js 3.x** untuk interaktivitas frontend
- Semua model: `$guarded = []`, UUID PK via `HasUuids`

### Pola Kode yang Harus Diketahui

**Soft delete dengan actor:**
```php
// JANGAN: $model->delete()
// HARUS:
$model->deleteWithReason($request->reason);
// → sets deleted_at, deleted_by (auth()->id()), deletion_reason
// → auto-log ke activity_logs
```

**Storage URL:**
```php
// Default disk adalah R2 (bukan 'public')
Storage::disk()->url($path); // atau
Storage::disk('r2')->url($path);

// JANGAN pakai disk 'public' untuk file yang diupload post-session-2026-06-13
```

**AJAX DataTable controller pattern:**
```php
public function index(Request $request) {
    if ($request->ajax()) return $this->datatableResponse($request);
    return view('admin.xxx.index');
}
```

**Alpine.js + TomSelect reactivity:**
```js
// JANGAN akses this.entries.find() lalu mutate setelah await
// HARUS: re-find by uid setelah setiap await
const uid = entry.uid;
const res = await fetch(...);
const j = this.entries.findIndex(e => e.uid === uid); // re-find!
if (j !== -1) this.entries[j].volumes = data.volumes;
```

**TomSelect init di Alpine x-for:**
```js
// JANGAN: x-init="initSeriesSelect(entry.uid)" langsung
// HARUS: dari addEntry() method Alpine dengan $nextTick + setTimeout
addEntry() {
    const uid = ++_uidCounter;
    this.entries.push({...});
    this.$nextTick(() => setTimeout(() => this.initSeriesSelect(uid), 50));
}
// Dan gunakan window._collApp = this untuk akses Alpine dari TomSelect callback
```

## Checklist Debug

### Frontend tidak muncul / blank page
1. Cek browser console — ada JS error?
2. `npm run build` sudah dijalankan? Cek `public/build/` ada isinya.
3. `npm run dev` berjalan untuk development?

### DataTable kosong / gagal load
1. Buka browser DevTools → Network → lihat request AJAX ke `/admin/xxx`
2. Cek response: ada error JSON atau HTML (502/500)?
3. Cek `storage/logs/laravel.log` untuk exception

### Alpine.js tidak reaktif
1. Pastikan `window._collApp = this` ada di `initApp()` (dipanggil dari `x-init`)
2. `x-data` dan `x-init` harus ada di element yang sama atau parent
3. Cek apakah TomSelect `onChange` menggunakan `window._collApp` (bukan `this`)

### TomSelect tidak muncul
1. Pastikan `TomSelect` tersedia global: `window.TomSelect`
2. Cek `resources/js/app.js`: `window.TomSelect = TomSelect;`
3. Cek apakah `npm run build` sudah dijalankan

### Volume tidak load setelah pilih series
File: `resources/views/admin/collections/create.blade.php`
1. Cek endpoint: `GET /admin/api/series/{id}/volumes?user_id=...`
2. Buka DevTools Network: apakah request terkirim?
3. Cek `AdminApiController::seriesVolumes()` — apakah query-nya benar?
4. Pastikan `loadVolumes(uid)` dipanggil dari `onChange`, bukan `loadVolumes(entry)`

### Jikan scraping tidak jalan
1. Queue worker aktif? `php artisan queue:work`
2. Cek `jobs` table: `SELECT * FROM jobs;`
3. Cek `failed_jobs` table: `SELECT * FROM failed_jobs;`
4. Cek session: `SELECT * FROM jikan_scrape_sessions ORDER BY id DESC LIMIT 5;`
5. Cek `storage/logs/laravel.log` untuk exception dari job

### R2 upload gagal
1. Cek `.env`: semua `AWS_*` variables sudah benar?
2. `AWS_USE_PATH_STYLE_ENDPOINT=true` — wajib ada
3. Test: `php artisan tinker` → `Storage::disk('r2')->put('test.txt', 'ok')`
4. Cek bucket di Cloudflare apakah sudah Public

## File yang Paling Sering Diedit

| Task | File |
|---|---|
| Tambah route admin | `app/Modules/Admin/routes/admin.php` |
| Logic controller | `app/Modules/Admin/Http/Controllers/*.php` |
| View admin | `resources/views/admin/*/index.blade.php` |
| Model | `app/Modules/{Core,Collection,Jikan}/Models/*.php` |
| Job Jikan | `app/Jobs/ScrapeJikanPageJob.php` |
| Scheduler | `routes/console.php` |
| JS global | `resources/js/app.js` |
| CSS global | `resources/css/app.css` |
| R2 config | `config/filesystems.php` |

## Hal yang TIDAK Boleh Dilakukan Agent

- Commit credential R2 ke file apapun selain `.env`
- Menggunakan `$model->delete()` langsung — harus `deleteWithReason()`
- Menambah Redis dependency — stack tidak pakai Redis
- Mengubah `QUEUE_CONNECTION` ke selain `database`
- Membuat file migration yang mengubah tipe kolom UUID ke integer
- `Storage::disk('public')` untuk cover image baru — harus `Storage::disk('r2')` atau default
