# Test Plan

## Status Saat Ini

Belum ada automated test suite yang aktif. Semua pengujian dilakukan secara manual.

## Pengujian Manual — Critical Path

### 1. Auth

| Test Case | Steps | Expected |
|---|---|---|
| Login super_admin | Buka /login, isi credential | Redirect ke /admin |
| Login user biasa | Isi credential role 'user' | Redirect ke /, tidak bisa akses /admin |
| Login salah | Isi password salah | Error message muncul, tidak masuk |
| Akses /admin tanpa login | Buka /admin langsung | Redirect ke /login |

### 2. Series Management

| Test Case | Steps | Expected |
|---|---|---|
| Tambah series baru | /admin/series/create → isi form → submit | Series muncul di DataTable |
| Upload cover | Isi form + pilih gambar | Cover muncul di show page (URL dari R2) |
| Search series | Ketik di search box DataTable | Hasil filter real-time |
| Filter status | Pilih dropdown status | DataTable filter sesuai |
| Soft delete | Klik Hapus, isi alasan | Series ter-mark trashed, opacity berkurang di DataTable |
| Batch delete | Centang beberapa series → Hapus Terpilih | Konfirmasi + alasan prompt → series dihapus |

### 3. Collection Bulk Add

| Test Case | Steps | Expected |
|---|---|---|
| Pilih anggota | Buka /admin/collections/create → ketik nama user | TomSelect autocomplete muncul |
| Cari series | Ketik 2+ karakter di field manga | Dropdown hasil search muncul |
| Load volumes | Pilih series dari dropdown | Volume list muncul, volume yang sudah dimiliki user tidak ada |
| Volume tidak ada | Pilih series yang tidak punya volume | Pesan "belum punya volume" muncul |
| Per-entry detail | Isi kondisi/harga per manga entry | Tersimpan per-entry, bukan global |
| Tambah manga lain | Klik + Tambah Manga Lain | Entry baru muncul dengan TomSelect baru |
| Submit | Isi semua lalu klik Simpan | Redirect ke collections index, pesan sukses |
| Reload volumes saat ganti user | Pilih series, lalu ganti user | Volumes di-reload (filter berubah) |

### 4. Jikan Scraping

| Test Case | Steps | Expected |
|---|---|---|
| Tambah jadwal | Klik + Tambah Jadwal, isi form | Jadwal muncul di list |
| Reorder jadwal | Drag-drop urutan | Urutan berubah, tersimpan setelah drop |
| Scrape Now | Klik Scrape Now → submit | Status berubah ke 'running', progress muncul |
| Polling status | Tunggu scraping | Status di-update setiap 2 detik |
| Scrape selesai | Tunggu sampai selesai | Status 'completed', jumlah series ditampilkan |

### 5. User Management

| Test Case | Steps | Expected |
|---|---|---|
| DataTable | Buka /admin/users | User list muncul via AJAX |
| Filter role | Pilih dropdown role | List filter |
| Ban user | Klik Ban, isi alasan | User status jadi Banned |
| Unban user | Klik Unban pada user yang banned | Status kembali Aktif |

## Scenario Regression — Setelah Update

Setelah setiap perubahan signifikan, jalankan:

1. Login → akses `/admin` → tidak ada error 500
2. `/admin/series` → DataTable load
3. `/admin/collections/create` → pilih user → cari series → pilih volume → submit
4. `/admin/jikan` → status load → tambah jadwal → hapus jadwal
5. Upload cover series baru → gambar muncul di show page

## Setup Test Database (Manual)

```bash
# Buat database test terpisah
mysql -u root -e "CREATE DATABASE malas_test;"

# Edit phpunit.xml atau .env.testing
# DB_DATABASE=malas_test

# Jalankan migrasi di test DB
php artisan migrate --database=mysql_test

# Jalankan PHPUnit (jika ada test)
php artisan test
# atau
./vendor/bin/phpunit
```

## Contoh Struktur Test yang Direkomendasikan (Future)

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── LoginTest.php
│   ├── Admin/
│   │   ├── SeriesControllerTest.php
│   │   │   ├── test_can_list_series_as_datatable
│   │   │   ├── test_can_create_series
│   │   │   ├── test_can_soft_delete_series_with_reason
│   │   │   └── test_can_batch_delete_series
│   │   └── CollectionControllerTest.php
│   │       ├── test_bulk_store_creates_collections_per_entry
│   │       └── test_bulk_store_skips_already_owned_volumes
│   └── Jikan/
│       └── ScrapeJobTest.php
└── Unit/
    └── HasSoftDeletesWithActorTest.php
```
