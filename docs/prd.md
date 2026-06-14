# PRD — Product Requirements Document
# MALAS (Manga Library Admin System)

**Versi:** 1.0  
**Tanggal:** 2026-06-14  
**Status:** Draft

---

## 1. Overview

### Latar Belakang

Kolektor manga fisik sering kesulitan melacak koleksi mereka sendiri — volume mana yang sudah dimiliki, kondisinya, lokasinya di rak, dan siapa yang sedang meminjamnya. Pengelolaan manual via spreadsheet tidak skalabel: tidak ada validasi, rawan human error, dan tidak bisa diakses orang lain secara real-time.

Proyek lama MALAS (`src/`) sudah membuktikan konsep ini, tapi dibangun dengan custom Blade admin yang sulit dikembangkan. Rebuild ini menggunakan Filament v3 untuk mendapatkan admin panel yang lebih solid dengan effort lebih kecil.

### Problem Statement

| Pain Point | Dampak |
|-----------|--------|
| Tidak tahu volume mana yang sudah dimiliki | Beli dobel |
| Tidak ada catatan siapa yang meminjam | Volume hilang tanpa jejak |
| Input data manga manual satu per satu | Butuh waktu lama, rawan typo |
| Tidak bisa lihat status koleksi dari mana saja | Harus buka spreadsheet lokal |

### Tujuan Produk

1. Menyediakan panel admin terpusat untuk mengelola koleksi manga fisik
2. Mengotomatisasi pengisian data manga dari MyAnimeList via Jikan API
3. Melacak peminjaman volume dengan status yang jelas dan terdokumentasi
4. Memberikan akses read-only kepada teman/peminjam untuk melihat katalog

### Scope

**In scope:**
- Admin panel (Filament) untuk CRUD semua data
- Import data series dari Jikan API
- Upload dan manajemen cover image ke Cloudflare R2
- Sistem peminjaman dengan state machine
- Manajemen user dengan role dan ban
- Activity log

**Out of scope:**
- Aplikasi mobile
- Marketplace / jual beli koleksi
- Integrasi payment
- Scraping sumber selain MyAnimeList
- Rating / review oleh user

---

## 2. User Personas

### Persona A — Admin / Pemilik Koleksi

> **"Aku mau tahu persis di mana setiap volume-ku berada, dan siapa yang lagi pegang."**

| Atribut | Detail |
|---------|--------|
| **Nama fiktif** | Andi |
| **Role** | `super_admin` atau `admin` |
| **Kebiasaan** | Koleksi 200+ volume, sering dipinjam teman |
| **Tech savvy** | Menengah — nyaman pakai web app, tidak perlu CLI |
| **Goal utama** | Input koleksi cepat, lacak peminjaman, tidak ada yang hilang |
| **Frustrasi** | Input manual lambat, lupa siapa yang pegang volume tertentu |

### Persona B — Borrower / Teman Peminjam

> **"Aku mau lihat koleksi apa yang ada dan apakah bisa dipinjam sekarang."**

| Atribut | Detail |
|---------|--------|
| **Nama fiktif** | Budi |
| **Role** | `user` |
| **Kebiasaan** | Sesekali pinjam manga, tidak rutin |
| **Tech savvy** | Rendah-menengah — hanya butuh tampilan sederhana |
| **Goal utama** | Lihat katalog, cek status pinjaman sendiri |
| **Frustrasi** | Harus tanya manual ke pemilik apakah volume tersedia |

---

## 3. User Stories

### Epic 1: Series Management

| ID | User Story | Priority |
|----|-----------|----------|
| US-S01 | Sebagai admin, saya ingin menambah series manga baru secara manual, sehingga koleksi saya terdokumentasi | Must |
| US-S02 | Sebagai admin, saya ingin mencari series di MyAnimeList dan mengimpornya langsung, sehingga tidak perlu input data manual | Must |
| US-S03 | Sebagai admin, saya ingin melihat daftar semua series dengan filter status terbit dan pencarian judul, sehingga mudah ditemukan | Must |
| US-S04 | Sebagai admin, saya ingin mengedit data series (synopsis, cover, status), sehingga data tetap akurat | Must |
| US-S05 | Sebagai admin, saya ingin menghapus series (soft delete) beserta alasannya, sehingga ada jejak penghapusan | Should |
| US-S06 | Sebagai user, saya ingin melihat katalog series yang tersedia, sehingga tahu apa yang bisa dipinjam | Must |

### Epic 2: Volume Management

| ID | User Story | Priority |
|----|-----------|----------|
| US-V01 | Sebagai admin, saya ingin menambah volume ke suatu series dengan nomor, ISBN, dan kondisi fisiknya | Must |
| US-V02 | Sebagai admin, saya ingin melihat semua volume dalam satu series sekaligus | Must |
| US-V03 | Sebagai admin, saya ingin mencatat lokasi rak dari setiap volume | Should |
| US-V04 | Sebagai admin, saya ingin mengedit kondisi fisik volume setelah dikembalikan | Must |
| US-V05 | Sebagai admin, saya ingin menghapus volume (soft delete) jika rusak/hilang | Should |

### Epic 3: Collection Management

| ID | User Story | Priority |
|----|-----------|----------|
| US-C01 | Sebagai admin, saya ingin mencatat bahwa user tertentu memiliki volume tertentu | Must |
| US-C02 | Sebagai admin, saya ingin melihat semua volume yang dimiliki seorang user | Must |
| US-C03 | Sebagai admin, saya ingin melihat siapa yang memiliki volume tertentu | Must |
| US-C04 | Sebagai user, saya ingin melihat daftar volume yang saya miliki | Should |

### Epic 4: Loan System

| ID | User Story | Priority |
|----|-----------|----------|
| US-L01 | Sebagai admin, saya ingin mencatat peminjaman volume (siapa, due date) sehingga ada catatan resmi | Must |
| US-L02 | Sebagai admin, saya ingin mengubah status pinjaman (active → returned) saat volume dikembalikan | Must |
| US-L03 | Sebagai admin, saya ingin melihat semua pinjaman yang sedang aktif sekaligus | Must |
| US-L04 | Sebagai admin, sistem otomatis menandai pinjaman sebagai overdue jika melewati due date | Must |
| US-L05 | Sebagai admin, saya ingin menandai volume sebagai hilang (lost) | Should |
| US-L06 | Sebagai user, saya ingin melihat status pinjaman saya saat ini | Should |

### Epic 5: Jikan Integration

| ID | User Story | Priority |
|----|-----------|----------|
| US-J01 | Sebagai admin, saya ingin mencari manga di panel dan mengimpornya dengan satu klik | Must |
| US-J02 | Saat import, cover manga otomatis diunduh dan disimpan di R2 | Must |
| US-J03 | Jika Jikan API lambat atau down, sistem memberikan pesan error yang jelas tanpa crash | Must |
| US-J04 | Import dijalankan di background (queue) agar panel tidak freeze | Should |

### Epic 6: User Management

| ID | User Story | Priority |
|----|-----------|----------|
| US-U01 | Sebagai super admin, saya ingin mendaftarkan user baru dan menentukan role-nya | Must |
| US-U02 | Sebagai super admin, saya ingin memban user dengan alasan, sehingga tidak bisa login | Must |
| US-U03 | Sebagai super admin, saya ingin menghapus user (soft delete) beserta alasannya | Should |
| US-U04 | Sebagai admin, saya ingin melihat activity log user tertentu | Should |
| US-U05 | Sebagai user, saya ingin mengubah password dan profil saya | Must |

---

## 4. Functional Requirements

### FR-S — Series Management

| Kode | Requirement |
|------|------------|
| FR-S-001 | Sistem menyimpan data series: `title_romaji`, `title_english`, `title_japanese`, `synopsis`, `cover_path`, `status`, `published_from`, `published_to`, `total_volumes`, `score`, `rank`, `mal_id` |
| FR-S-002 | Status series: `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published` |
| FR-S-003 | Series dapat di-soft-delete dengan `deleted_at`, `deleted_reason`, `deleted_by` |
| FR-S-004 | Daftar series mendukung pencarian by judul dan filter by status |
| FR-S-005 | Satu series bisa memiliki banyak volume |
| FR-S-006 | `mal_id` bersifat nullable (untuk series yang diinput manual) dan unique jika diisi |

### FR-V — Volume Management

| Kode | Requirement |
|------|------------|
| FR-V-001 | Sistem menyimpan: `series_id`, `volume_number`, `isbn`, `cover_path`, `published_at` |
| FR-V-002 | `volume_number` unik dalam satu series |
| FR-V-003 | Volume dapat di-soft-delete |
| FR-V-004 | Daftar volume dalam series ditampilkan berurutan by `volume_number` |

### FR-C — Collection Management

| Kode | Requirement |
|------|------------|
| FR-C-001 | Sistem menyimpan: `user_id`, `volume_id`, `condition`, `location`, `notes`, `acquired_at` |
| FR-C-002 | `condition` enum: `mint`, `good`, `fair`, `poor` |
| FR-C-003 | Satu user hanya bisa memiliki satu record per volume (unique constraint) |
| FR-C-004 | Collection dapat di-soft-delete |

### FR-L — Loan System

| Kode | Requirement |
|------|------------|
| FR-L-001 | Sistem menyimpan: `collection_id`, `borrower_user_id` (nullable), `borrower_name`, `borrower_contact`, `status`, `loaned_at`, `due_date`, `returned_at`, `notes` |
| FR-L-002 | Status enum: `pending`, `active`, `returned`, `overdue`, `lost` |
| FR-L-003 | Transisi status yang valid: `pending→active`, `active→returned`, `active→overdue`, `active→lost`, `overdue→returned`, `overdue→lost`, `pending→cancelled` |
| FR-L-004 | Scheduler harian menandai loan `active` yang `due_date < now()` menjadi `overdue` |
| FR-L-005 | Hanya satu loan `active` atau `pending` per collection record pada satu waktu |
| FR-L-006 | Loan dapat di-soft-delete dengan alasan |

### FR-J — Jikan Integration

| Kode | Requirement |
|------|------------|
| FR-J-001 | Admin dapat mencari manga via nama di halaman Jikan panel |
| FR-J-002 | Hasil pencarian menampilkan: judul, cover thumbnail, status, score, MAL ID |
| FR-J-003 | Admin dapat memilih satu hasil dan mengkonfirmasi import |
| FR-J-004 | Import membuat record series + mengunduh cover ke R2 |
| FR-J-005 | Import dijalankan via queue job dengan retry 3x (backoff: 10s, 60s, 180s) |
| FR-J-006 | Rate limiting: maksimal 3 request/detik ke Jikan API |
| FR-J-007 | Jika `mal_id` sudah ada di DB, import gagal dengan pesan "Series sudah ada" |

### FR-U — User Management

| Kode | Requirement |
|------|------------|
| FR-U-001 | Role user: `super_admin`, `admin`, `user` |
| FR-U-002 | `super_admin` dapat membuat, mengedit, memban, dan menghapus semua user |
| FR-U-003 | `admin` tidak dapat mengubah `super_admin` lain |
| FR-U-004 | User yang di-ban tidak dapat login, menerima pesan ban reason |
| FR-U-005 | User di-soft-delete dengan `deleted_at` dan `deleted_reason` |

### FR-A — Activity Log

| Kode | Requirement |
|------|------------|
| FR-A-001 | Setiap create/update/delete pada model utama dicatat: `causer`, `subject`, `event`, `properties` (before/after) |
| FR-A-002 | Activity log dapat dilihat di panel via Filament Logger UI |
| FR-A-003 | Log tidak dapat dihapus oleh `admin`, hanya `super_admin` |

### FR-ST — Storage

| Kode | Requirement |
|------|------------|
| FR-ST-001 | Cover image disimpan di Cloudflare R2 |
| FR-ST-002 | Cover dioptimasi (compress) sebelum upload |
| FR-ST-003 | Jika cover URL Jikan tidak valid, series tetap tersimpan dengan `cover_path = null` |
| FR-ST-004 | URL cover di-generate via R2 public URL atau signed URL |

---

## 5. Non-Functional Requirements

### Performance
- Halaman daftar (list) resource Filament: load < 2 detik untuk 1000 records (dengan pagination)
- Import Jikan via queue: tidak memblokir UI, estimasi < 30 detik per series
- Concurrent users: mendukung minimal 10 admin aktif bersamaan

### Security
- Autentikasi wajib untuk semua halaman admin
- CSRF protection aktif di semua form
- Password di-hash dengan bcrypt (Laravel default)
- Upload file: validasi tipe dan ukuran, disimpan di R2 (bukan public disk server)
- Role-based access dikontrol Shield — setiap resource punya permission granular
- SQL injection: dicegah via Eloquent ORM (no raw queries tanpa binding)
- XSS: Blade auto-escape, Filament sanitize input

### Scalability
- Queue-based job untuk operasi berat (Jikan import, cover download)
- Database queue cukup untuk < 100 job/hari; upgrade ke Redis jika melebihi

### Availability
- Target uptime: 99% (single VPS, tanpa HA)
- Recovery dari down: restart PHP-FPM + queue worker via Supervisor

### Usability
- Admin panel responsive (Filament default responsive)
- Dark mode tersedia (Filament built-in)
- Semua form memiliki validasi inline dengan pesan error yang jelas

---

## 6. Acceptance Criteria

### Series Management
- [ ] Admin dapat membuat series baru via form manual
- [ ] Admin dapat mengimport series dari Jikan (search → preview → confirm)
- [ ] Cover otomatis tersimpan di R2 saat import
- [ ] Series yang di-soft-delete tidak muncul di daftar default, tapi bisa difilter
- [ ] Duplikasi `mal_id` ditolak dengan pesan error

### Loan System
- [ ] Tidak bisa membuat loan baru untuk volume yang sedang dipinjam (status `active`/`pending`)
- [ ] Status otomatis berubah jadi `overdue` saat scheduler berjalan dan due date terlewat
- [ ] Admin dapat memproses pengembalian dan mencatat `returned_at`
- [ ] State transition yang tidak valid ditolak

### Auth & RBAC
- [ ] User yang di-ban tidak bisa login
- [ ] `admin` tidak bisa mengakses resource yang tidak diizinkan Shield
- [ ] `super_admin` punya akses penuh ke semua resource

### Jikan Integration
- [ ] Rate limit tidak menyebabkan crash, hanya delay dengan retry
- [ ] Jika Jikan down, job masuk `failed_jobs` dengan pesan yang jelas
- [ ] Import yang gagal bisa di-retry manual dari panel

---

## 7. Prioritization (MoSCoW)

### Must Have
- CRUD Series, Volume, Collection, Loan
- Import Jikan + download cover ke R2
- Loan state machine + scheduler overdue
- RBAC via Shield (`super_admin`, `admin`, `user`)
- Auth (login, logout, Breezy profile page)
- Activity log
- Ban user

### Should Have
- Soft delete dengan reason & actor
- Filter & search di semua list resource
- Date range filter untuk loan
- Export daftar loan ke Excel/CSV
- Notifikasi email untuk due date reminder

### Could Have
- Dashboard widget (statistik: total series, active loans, overdue)
- Spotlight / ⌘K launcher
- Import bulk volume via CSV
- User-facing portal (lihat katalog tanpa akses admin)

### Won't Have (for now)
- Aplikasi mobile
- Integrasi API publik (REST untuk third party)
- Marketplace / jual beli
- Multi-bahasa (i18n)
- WebSocket / real-time notifications

---

## 8. Success Metrics

| Metrik | Target | Cara Ukur |
|--------|--------|-----------|
| Waktu input series baru via Jikan | < 1 menit | Manual test: search → import → done |
| Volume yang berhasil dilacak | 100% dari koleksi fisik | Jumlah record vs inventaris aktual |
| Loan overdue yang terdeteksi otomatis | 100% | Verifikasi scheduler log |
| Tidak ada duplikasi data series | 0 duplikat | DB constraint `unique(mal_id)` |
| Test coverage | ≥ 70% | `pest --coverage` |
| Zero downtime saat deploy | Setiap deploy | Health check post-deploy |
