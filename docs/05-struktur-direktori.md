# Struktur Direktori Modular

## app/Modules/

```
app/Modules/
├── Core/
│   ├── Models/
│   │   ├── Series.php              # Entitas penghubung Tracking & Collection
│   │   └── User.php
│   ├── Repositories/
│   │   └── SeriesRepository.php    # Query series, search trigram
│   ├── Services/
│   │   ├── SeriesService.php       # CRUD series, cache invalidation
│   │   └── CacheService.php        # Helper untuk tagged cache
│   ├── Traits/
│   │   └── HasSoftDeletesWithActor.php  # deleted_by, deletion_reason, activity_log
│   └── Observers/
│       └── ActivityLogObserver.php # Catat deleted/restored ke activity_log
│
├── Tracking/
│   ├── Models/
│   │   ├── UserTracking.php
│   │   └── Chapter.php
│   ├── Repositories/
│   │   └── UserTrackingRepository.php
│   ├── Services/
│   │   └── TrackingService.php     # Update progress, status, score
│   └── Http/
│       ├── Controllers/
│       │   └── TrackingController.php
│       └── Resources/
│           └── UserTrackingResource.php
│
├── Collection/
│   ├── Models/
│   │   ├── Volume.php
│   │   ├── UserCollection.php
│   │   ├── Loan.php
│   │   └── LoanEvent.php           # Immutable, tanpa SoftDeletes
│   ├── Repositories/
│   │   └── UserCollectionRepository.php
│   ├── Services/
│   │   ├── CollectionService.php   # Manage owned/missing/wishlist
│   │   └── LoanService.php         # Peminjaman, validasi double-loan
│   ├── Jobs/
│   │   └── CheckOverdueLoans.php
│   └── Http/
│       ├── Controllers/
│       │   ├── CollectionController.php
│       │   └── LoanController.php
│       └── Resources/
│           ├── UserCollectionResource.php
│           └── LoanResource.php
│
├── Sources/
│   ├── Models/
│   │   └── Source.php
│   ├── Contracts/
│   │   └── SourceAdapterInterface.php  # getId, searchSeries, fetchChapters, normalizeChapter
│   ├── Adapters/
│   │   ├── MangaDexAdapter.php
│   │   └── AniListAdapter.php
│   ├── Normalizers/
│   │   └── ChapterNormalizer.php
│   ├── Services/
│   │   ├── SourceRegistry.php      # Registrasi adapter aktif
│   │   └── DeduplicationService.php
│   └── Jobs/
│       ├── FetchChaptersFromSource.php
│       ├── UpdateAllSeriesFromSource.php
│       ├── HealthCheckSource.php
│       └── DeduplicateNewSeries.php
│
└── Admin/
    └── Filament/
        ├── Resources/
        │   ├── SeriesResource.php
        │   ├── VolumeResource.php
        │   ├── ChapterResource.php
        │   ├── SourceResource.php
        │   ├── LoanResource.php
        │   ├── UserResource.php
        │   ├── TrashedDataResource.php   # onlyTrashed + restore form
        │   └── ActivityLogResource.php   # super_admin only
        └── Pages/
            └── MergeSeries.php           # Preview & merge duplikat
```

## Tanggung Jawab Setiap Module

### Core
Modul fondasi yang dipakai modul lain. Berisi model `Series` (penghubung Tracking dan Collection) dan `User`. Menyediakan trait `HasSoftDeletesWithActor` yang dipakai hampir semua model di sistem untuk mengelola `deleted_by`, `deletion_reason`, dan pencatatan ke `activity_log` melalui `ActivityLogObserver`. Juga menyediakan `CacheService` sebagai helper tagged cache yang dipakai modul lain.

### Tracking
Mengelola progress bacaan digital user: `UserTracking` (status, current_chapter, score) dan `Chapter` (data chapter dari sumber eksternal). Tidak boleh mengandung logika terkait kepemilikan fisik atau peminjaman. Endpoint utama: update progress, ubah status baca, hitung chapters-read secara dinamis.

### Collection
Mengelola koleksi fisik: `Volume`, `UserCollection` (kepemilikan), `Loan`, dan `LoanEvent` (log immutable). `LoanService` bertanggung jawab memvalidasi agar satu volume tidak dipinjam dua orang sekaligus (partial unique index). `CheckOverdueLoans` job berjalan tiap jam untuk mendeteksi pinjaman terlambat.

### Sources
Mengelola integrasi sumber eksternal melalui `SourceAdapterInterface` (kontrak yang wajib diimplementasikan tiap adapter seperti MangaDex, AniList). `SourceRegistry` mendaftarkan adapter aktif. `DeduplicationService` mendeteksi series duplikat berdasarkan confidence score. Semua operasi ke sumber eksternal dijalankan via queue job, tidak pernah sinkron (kecuali search).

### Admin
Berisi seluruh Filament resource untuk panel admin: CRUD series/volumes/chapters/sources/loans/users, halaman `TrashedDataResource` untuk melihat dan restore data soft-deleted, `ActivityLogResource` (khusus super_admin), serta halaman `MergeSeries` untuk preview dan eksekusi merge series duplikat.

## tests/

```
tests/
├── Unit/
│   ├── Core/
│   │   └── HasSoftDeletesWithActorTest.php
│   ├── Tracking/
│   │   └── UserTrackingServiceTest.php
│   ├── Collection/
│   │   ├── LoanServiceTest.php          # validasi double-loan
│   │   └── LoanOverdueCheckerTest.php
│   └── Sources/
│       ├── SourceNormalizerTest.php
│       └── DeduplicationServiceTest.php
│
├── Feature/
│   ├── Auth/
│   │   ├── RegisterTest.php
│   │   └── LoginTest.php
│   ├── Series/
│   │   ├── SeriesCrudTest.php
│   │   └── SeriesSearchTest.php
│   ├── Collection/
│   │   ├── UserCollectionTest.php
│   │   └── LoanFlowTest.php             # full flow: pinjam -> overdue -> return
│   ├── Tracking/
│   │   └── UserTrackingTest.php
│   └── Admin/
│       ├── SoftDeleteRestoreTest.php    # delete -> trashed -> restore -> activity_log
│       └── ActivityLogAccessTest.php    # super_admin only
│
├── Integration/
│   └── Sources/
│       └── FetchChaptersFromSourceJobTest.php  # mock MangaDex adapter
│
└── Fixtures/
    ├── mangadex_search_response.json
    ├── mangadex_chapters_response.json
    └── csv_import_sample.csv
```