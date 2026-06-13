# Changelog

Format: **[Tanggal] Deskripsi**. Perubahan signifikan yang mempengaruhi arsitektur atau kontrak dicatat di sini.

---

## [2026-06-13] Multi-Schedule Jikan, R2 Storage, DataTable AJAX, Bulk Collection, Batch Series

### Ditambahkan
- **Cloudflare R2 Storage**: `FILESYSTEM_DISK=r2`, package `league/flysystem-aws-s3-v3`. Semua cover image baru disimpan di R2.
- **Jikan Multi-Schedule**: Jadwal scraping bisa lebih dari satu, dengan nama, jam/menit, rentang tahun, dan sort_order. Drag-and-drop reorder via SortableJS. Schedule dieksekusi berurutan via queue.
- **AJAX DataTable**: Series index dan Users index menggunakan custom vanilla JS DataTable (AJAX server-side). Filter, search dengan debounce 350ms, paginasi.
- **AdminApiController**: Endpoint internal `GET /admin/api/series/search` dan `GET /admin/api/series/{series}/volumes`.
- **Bulk Collection Add**: Form tambah koleksi bisa handle banyak series sekaligus. TomSelect untuk user select dan series search. Alpine.js reactive form.
- **Per-Entry Collection Details**: Kondisi, harga, tanggal, catatan, dan flag is_for_loan ada per manga entry (bukan shared global).
- **Batch Delete Series**: Checkbox di DataTable series, batch action bar, `POST /admin/series/batch-destroy`. Max 200 IDs per request.
- **TomSelect** dan **SortableJS** ditambahkan ke `resources/js/app.js` sebagai global.

### Diubah
- **Jikan schedule table**: `jikan_scrape_schedule` → `jikan_schedules`. Ditambah kolom: `name`, `start_year`, `end_year`, `sort_order`.
- **Jikan sessions**: Ditambah kolom `schedule_id`, `start_year`, `end_year`. Status enum diperluas: `queued` ditambahkan.
- **CollectionController::bulkStore()**: Validasi diubah dari shared settings ke per-entry settings (`entries.*.condition`, `entries.*.is_for_loan`, dll.).
- **ScrapeJikanPageJob**: Mendukung year filter (start/end) dari session. Setelah selesai, auto-dispatch session berikutnya ordered by `sort_order`.
- **routes/console.php**: Loop semua `JikanSchedule` aktif setiap menit, buat session sesuai jadwal.

### Diperbaiki
- **Volumes loading bug** di form tambah koleksi: `loadVolumes` sekarang uid-based (re-find after await), bukan mutasi langsung pada entry object setelah async.
- **TomSelect init timing**: `addEntry()` menggunakan `$nextTick + setTimeout(50ms)` agar DOM siap sebelum TomSelect init.
- **Alpine.js callback scope**: `window._collApp = this` di `initApp()` untuk memastikan TomSelect callback bisa akses Alpine data.

### Migrasi yang Dijalankan
- `2026_06_13_220000_update_jikan_for_multi_schedule.php`

---

## [2026-06-13] Admin CRUD + Auth Foundation (Session Awal)

### Ditambahkan
- Admin panel custom (`app/Modules/Admin/`) dengan controller untuk Series, Volume, Collection, Loan, User, ActivityLog
- Modular architecture: `app/Modules/{Core,Collection,Admin,Jikan}/`
- `HasSoftDeletesWithActor` trait: `deleted_by`, `deletion_reason`, auto-log ke `activity_logs`
- UUID primary keys via `HasUuids` di semua model utama
- `UserLibrary` sebagai bridge `users ↔ series`
- Soft delete dengan actor tracking di semua model utama
- Activity Log: semua aksi admin dicatat otomatis
- Jikan scraping dasar (single schedule)
- Auth: login/register, middleware `role:super_admin`
- Frontend stack: Alpine.js 3.x, Tailwind CSS, Flowbite, Vite

### Keputusan Arsitektur
- **MySQL** (bukan PostgreSQL yang ada di spesifikasi awal) — XAMPP lokal
- **Database queue** (bukan Redis/Horizon) — tidak perlu Redis server
- **Custom admin panel** (bukan Filament) — kontrol penuh UI/UX
- **`$guarded = []`** di semua model — validasi di controller layer
