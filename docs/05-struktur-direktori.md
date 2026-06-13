# Struktur Direktori Modular

## app/Modules/

```
app/Modules/
├── Core/
│   ├── Models/
│   │   ├── Series.php              # Entitas utama: UUID PK, soft deletes, mal_id unique
│   │   ├── User.php                # UUID PK, role enum, is_banned, HasSoftDeletesWithActor
│   │   └── ActivityLog.php         # Audit trail semua aksi admin
│   └── Traits/
│       └── HasSoftDeletesWithActor.php  # deleted_by, deletion_reason, auto-log ke activity_logs
│
├── Collection/
│   ├── Models/
│   │   ├── Volume.php              # UUID PK, series_id FK, volume_number decimal, cover R2
│   │   ├── UserLibrary.php         # Bridge users ↔ series (UUID PK)
│   │   ├── UserCollection.php      # Kepemilikan volume (UUID PK, condition, is_for_loan)
│   │   ├── Loan.php                # Header peminjaman (UUID PK, status enum)
│   │   └── LoanItem.php            # Detail volume dalam satu loan
│   └── (Services jika ada)
│
├── Admin/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AdminDashboardController.php
│   │       ├── AdminApiController.php      # AJAX: series search, volumes per series
│   │       ├── SeriesController.php        # CRUD + AJAX DataTable + batchDestroy
│   │       ├── VolumeController.php        # CRUD per series
│   │       ├── CollectionController.php    # CRUD + bulkStore JSON endpoint
│   │       ├── LoanController.php          # CRUD + markReturned + markLost
│   │       ├── UserManagementController.php # CRUD + ban/unban + AJAX DataTable
│   │       ├── JikanController.php         # Multi-schedule + scrape-now + cancel
│   │       └── ActivityLogController.php   # List activity log
│   └── routes/
│       └── admin.php               # Semua route prefix: admin/, middleware: auth + role:super_admin
│
└── Jikan/
    ├── Models/
    │   ├── JikanSchedule.php       # Konfigurasi jadwal scraping (name, hour, minute, sort_order)
    │   └── JikanScrapeSession.php  # Log satu sesi (status, pages, year filter)
    └── JikanService.php            # HTTP client ke Jikan API v4
```

## app/Jobs/

```
app/Jobs/
└── ScrapeJikanPageJob.php  # Queue job: scrape 1 halaman Jikan, dispatch next page / next queued schedule
```

## resources/

```
resources/
├── css/
│   └── app.css             # @import Tailwind directives + TomSelect styles
├── js/
│   └── app.js              # Import: Alpine, Flowbite, TomSelect, SortableJS, DataTables.net
└── views/
    ├── admin/
    │   ├── layouts/
    │   │   └── app.blade.php       # Layout admin: sidebar, nav, @stack('scripts')
    │   ├── series/
    │   │   ├── index.blade.php     # AJAX DataTable + batch mode (checkbox + batch delete)
    │   │   ├── create.blade.php
    │   │   ├── show.blade.php
    │   │   └── edit.blade.php
    │   ├── volumes/
    │   │   └── (index per series, create, edit)
    │   ├── collections/
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php    # Alpine.js: TomSelect + AJAX volumes + per-entry details
    │   │   ├── show.blade.php
    │   │   └── edit.blade.php
    │   ├── loans/
    │   │   └── (index, create, show, edit)
    │   ├── users/
    │   │   ├── index.blade.php     # AJAX DataTable + ban modal
    │   │   ├── create.blade.php
    │   │   └── show.blade.php
    │   ├── jikan/
    │   │   └── index.blade.php     # Multi-schedule + SortableJS + Alpine.js modal + status polling
    │   └── activity-log/
    │       └── index.blade.php
    └── auth/                       # Login/register views
```

## Tanggung Jawab Setiap Modul

### Core
Fondasi bersama. `HasSoftDeletesWithActor` menambah `deleted_by`, `deletion_reason` ke model, dan auto-mencatat ke `activity_logs` saat `delete()`. Semua model utama extend trait ini.

### Collection
Murni domain fisik: buku, kepemilikan, peminjaman. `UserLibrary` adalah bridge yang dibuat otomatis saat pertama kali user memiliki volume dari suatu series. `UserCollection` terhubung ke volume melalui `user_library_id`, bukan langsung ke `user_id`.

### Admin
Panel super_admin murni. Tidak ada Filament — semua controller Blade custom. Pattern AJAX DataTable: controller cek `$request->ajax()` → return JSON `{draw, recordsTotal, recordsFiltered, data[]}`. 

### Jikan
Scraping asinkron. `JikanSchedule` mendefinisikan kapan scrape, `JikanScrapeSession` mencatat apa yang terjadi. `ScrapeJikanPageJob` berjalan di queue (database driver), dispatch dirinya sendiri ke halaman berikutnya, atau dispatch session dari schedule berikutnya (ordered by `sort_order`) saat selesai.

## routes/

```
routes/
├── web.php         # Route publik / user (minimal)
├── console.php     # Scheduler: loop jikan_schedules, buat session jika jadwal cocok
└── (admin routes di app/Modules/Admin/routes/admin.php, di-load via ServiceProvider)
```

## Konfigurasi Penting

```
config/
├── filesystems.php     # Disk 'r2' (S3 driver, R2 endpoint, use_path_style_endpoint=true)
└── app.php             # APP_TIMEZONE, locale 'id'
```
