# Changelog

Semua perubahan penting pada MALAS dicatat di file ini. Format mengikuti prinsip [Keep a Changelog](https://keepachangelog.com/), disederhanakan untuk histori internal (bukan rilis versi berpenomor).

---

## 2026-07-25 — UX Overhaul: Katalog, Koleksiku, Admin Tools, Konten 18+

Batch besar perbaikan & fitur di sisi user dan admin, plus infrastruktur queue worker untuk deployment.

### Fixed
- Pagination Katalog & Admin Series balik ke halaman 1 sendiri — `useEffect` debounce search yang tidak seharusnya jalan di setiap mount (termasuk saat pindah halaman), sekarang di-skip pada render pertama.

### Added — User Side
- Modal "Tambah Series" di Koleksiku diperbesar, grid pemilihan lebih lega.
- Toggle tampilan Grid/Table + sort (nama, tanggal ditambahkan, jumlah volume) di Koleksiku, tersimpan per-device via `localStorage`.
- Toggle Grid/Table untuk daftar volume yang dimiliki di halaman detail koleksi.
- Filter "sudah/belum di koleksi" di Katalog.
- Checklist volume di koleksi kini bisa diklik di area manapun dalam kotak cover, tidak harus tepat di checkbox.
- Tombol refresh cepat di sebelah search — Katalog, Koleksiku, Admin Series.
- Filter status series (publishing/selesai/hiatus/dll) di Koleksiku.
- Genre series ditampilkan di kartu Koleksiku.
- Kondisi koleksi opsional (mint/bagus/cukup/buruk), bisa diubah dari halaman detail koleksi.
- Widget "tiket terakhir" di dashboard user.
- Galeri media tambahan (screenshot/artwork) di halaman detail Katalog; badge jumlah volume di kartu grid katalog dihapus (dianggap tidak informatif).
- Avatar user (dengan fallback inisial) ditampilkan konsisten di sidebar.

### Added — Admin Side
- Halaman Koleksi admin direstruktur: daftar dikelompokkan per user (dengan drill-down), bukan tabel flat semua koleksi.
- Import AniList tidak lagi pindah halaman setelah import — tetap di halaman cari, dengan tombol "lihat di katalog" untuk series yang sudah diimpor.
- Sidebar admin & user direstruktur jadi kategori/sub-kategori collapsible (mis. grup "AniList", grup "Lainnya").
- Halaman Storage Settings + Database Backup digabung jadi satu halaman `/admin/settings` bertab.
- Filter "sembunyikan konten 18+" saat mencari di AniList, plus badge 18+ pada hasil.
- Pengaturan global "blur konten 18+" (tab Konten di halaman Pengaturan) — cover series 18+ otomatis di-blur di seluruh halaman user, klik untuk membuka sementara (gaya Reddit/Instagram).
- Log aktivitas admin — mencatat aksi sensitif (hapus/bulk-delete series & volume, ban/unban/ganti role user, import database, ubah pengaturan storage/konten) dengan halaman viewer baru.
- Upload galeri media tambahan per series dari halaman Edit Series.
- Migrasi file storage otomatis (Local ↔ S3) saat driver/bucket/endpoint diganti — berjalan di background lewat queue job, status ditampilkan di halaman Pengaturan.

### Changed — Infrastruktur Deployment
- `deploy.sh` sekarang install & konfigurasi **Supervisor** untuk queue worker (`malas-worker`) — wajib supaya job antrian seperti migrasi storage benar-benar berjalan di production.
- `update.sh` menjalankan `php artisan queue:restart` setelah update kode, supaya worker yang sedang berjalan pakai kode terbaru.
- `docs/DEPLOYMENT.md` diperbarui: prasyarat Supervisor, langkah manual setup queue worker, troubleshooting job antrian tidak jalan.

---

## 2026-07-23 — Admin Series Bulk Delete

### Added
- Checkbox multi-select di `Admin/Series/Index.tsx` — pilih banyak series sekaligus untuk dihapus dalam satu aksi, tidak perlu satu-satu.
- `SeriesController::bulkDestroy()` + route `DELETE /admin/series/bulk` (didaftarkan sebelum resource route agar tidak bentrok dengan wildcard `{series}`).
- Toolbar "Hapus (N)" muncul di `PageHeader` saat ada series terpilih, dengan dialog konfirmasi terpisah dari delete single-item.

---

## 2026-07-22 — Storage Settings, Database Backup, Ticket System, AniList Fixes

### Added
- **Storage settings via UI admin** (`/admin/settings/storage`) — driver `local` atau `s3` (kompatibel AWS S3 maupun S3-compatible seperti Cloudflare R2), disimpan di tabel `storage_settings` dengan `secret_access_key` ter-encrypt. Konfigurasi tidak lagi lewat `.env`.
- **`StorageSettingsService`** — satu pintu untuk semua operasi file (disk, url, store, delete). Semua kode yang sebelumnya akses `Storage::` facade langsung dimigrasi ke service ini.
- **Database backup & import** (`/admin/settings/database`, super_admin only) — download dump SQL (exclude tabel sensitif: `users`, `sessions`, `jobs`, dll), import dengan `DELETE FROM` + `INSERT` per tabel dibungkus `DB::transaction()` supaya atomic dan bisa rollback kalau gagal di tengah.
- **Sistem tiket** — user bisa buat tiket request (misal minta judul baru masuk katalog) dari `User/Tickets/Create.tsx` (bisa pre-filled dari halaman katalog), admin merespon dari `Admin/Tickets/Show.tsx`. Status: `open`, `in_progress`, `resolved`, `closed`.
- Note "buat tiket request" di halaman Catalog dan Collection saat hasil pencarian kosong / koleksi belum ada di katalog.
- Volume range input syntax — `CollectionController::storeVolumes()` menerima format campuran seperti `1,2,3,5-9,11,12,15-18`, di-expand jadi list volume individual (auto-swap kalau range terbalik, dedupe, limit 100 per batch).

### Fixed
- Popover "Sync AniList" / search card yang posisinya aneh (nempel di bawah/kanan trigger, bukan di tengah) — diganti absolute overlay `inset-0` di dalam wrapper `relative` per-card, karena anchor engine Base UI Popover tidak didesain untuk centering penuh.
- Cover preview di Edit Series tidak muncul setelah upload baru — React reuse DOM node `<img>` yang sama antar render sehingga `style.display = 'none'` dari `onError` lama nyangkut. Fix: tambah `key={displayCover}` supaya React remount elemen saat source berubah.

---

## 2026-07-21 — SSO Integration

### Added
- `SsoController` — autentikasi PKCE-based OAuth2 ke whitearchive.id. Semua user (termasuk admin) login lewat SSO, tidak ada form register/login lokal lagi.
- Kolom baru di `users`: `sso_id` (unique), `username`, `avatar`. `password` diubah jadi nullable.
- Halaman `Settings/Index.tsx` — profil user ditampilkan read-only (data profil dikelola di sisi SSO).

---

## Sebelumnya — AniList Migration & Series Management

### Changed
- **Migrasi total dari Jikan (MyAnimeList) ke AniList GraphQL** — `JikanService` dihapus, diganti `AniListService`. Kolom baru di `series`: `anilist_id`, `genres`, `authors`, `themes`, `demographics` (semua json).
- Search & import series dari AniList (`Admin/AniList/Index.tsx`) dan sync ulang metadata ke series existing (Popover "Sync AniList" di Edit Series).

### Added
- Volume tracking per-user di koleksi pribadi, bulk delete volume dari halaman detail koleksi.
- Sistem peminjaman (loan) volume dari koleksi pribadi.

---

## Sebelumnya — Fondasi (Phase 0–10)

Setup awal Laravel 12 + Inertia v2 + React 19, sistem auth & role (Spatie Permission), menu management berbasis database, CRUD series/volume admin, katalog & koleksi user, announcements, user & menu management. Detail lengkap ada di [`docs/PHASES.md`](docs/PHASES.md). QA pass pertama: 2026-07-03.
