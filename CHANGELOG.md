# Changelog

Semua perubahan penting pada MALAS dicatat di file ini. Format mengikuti prinsip [Keep a Changelog](https://keepachangelog.com/), disederhanakan untuk histori internal (bukan rilis versi berpenomor).

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
