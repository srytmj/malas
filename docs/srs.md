# SRS — Software Requirements Specification
# MALAS (Manga Library Admin System)

**Versi:** 1.0  
**Tanggal:** 2026-06-14  
**Berdasarkan:** IEEE 830 (disederhanakan)  
**Referensi PRD:** [PRD.md](./PRD.md)

---

## 1. Introduction

### 1.1 Purpose

Dokumen ini mendefinisikan persyaratan perangkat lunak secara teknis untuk sistem MALAS berbasis Filament v3. Dokumen ini ditujukan untuk developer yang akan mengimplementasikan sistem dan menjadi acuan dalam pengambilan keputusan teknis selama development.

### 1.2 Scope

MALAS adalah sistem manajemen perpustakaan manga pribadi berbasis web. Sistem ini terdiri dari:
- **Admin Panel** — Filament v3, diakses role `super_admin` dan `admin`
- **User Portal** — Laravel Breeze (read-only), diakses role `user`
- **Background Services** — Queue worker (Jikan import), Scheduler (loan overdue check)
- **External Integrations** — Jikan API (data manga), Cloudflare R2 (storage)

Sistem ini **bukan** aplikasi mobile, bukan marketplace, dan tidak menyediakan REST API publik.

### 1.3 Definitions, Acronyms, Abbreviations

| Term | Definisi |
|------|----------|
| MAL | MyAnimeList — situs database anime/manga |
| Jikan | REST API tidak resmi untuk MAL (`api.jikan.moe/v4`) |
| R2 | Cloudflare R2 — object storage S3-compatible |
| RBAC | Role-Based Access Control |
| UUID | Universally Unique Identifier (v7 recommended) |
| Soft Delete | Menandai record sebagai dihapus tanpa menghapus dari DB (`deleted_at`) |
| Shield | Package `bezhansalleh/filament-shield` untuk RBAC di Filament |
| Breezy | Package `jeffgreco13/filament-breezy` untuk profile & auth di Filament |
| State Machine | Pola desain untuk mengelola transisi status Loan |

### 1.4 References

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Filament v3 Documentation](https://filamentphp.com/docs)
- [Jikan API v4 Documentation](https://docs.api.jikan.moe/)
- [Cloudflare R2 S3 API](https://developers.cloudflare.com/r2/api/s3/)
- [PRD.md](./PRD.md)
- [ERD.md](./ERD.md)

### 1.5 Overview

Dokumen ini terstruktur sebagai berikut:
- **Bagian 2** — Deskripsi umum sistem
- **Bagian 3** — Fitur sistem secara detail (stimulus/response + requirements teknis)
- **Bagian 4** — Antarmuka eksternal
- **Bagian 5** — Persyaratan non-fungsional
- **Bagian 6** — Persyaratan database
- **Bagian 7** — Persyaratan API

---

## 2. Overall Description

### 2.1 Product Perspective

MALAS adalah aplikasi standalone. Tidak ada sistem eksternal yang bergantung padanya. Sistem bergantung pada:
- **Jikan API** (read-only, tidak ada auth) — untuk data manga
- **Cloudflare R2** (baca/tulis) — untuk cover image
- **SMTP server** (opsional) — untuk notifikasi email

```
┌─────────────────────────────────────────────┐
│                  MALAS                       │
│                                             │
│  ┌──────────────┐   ┌────────────────────┐  │
│  │ Filament     │   │  Background        │  │
│  │ Admin Panel  │   │  (Queue + Cron)    │  │
│  └──────┬───────┘   └────────┬───────────┘  │
│         │                    │              │
│  ┌──────▼────────────────────▼───────────┐  │
│  │           MySQL / MariaDB             │  │
│  └───────────────────────────────────────┘  │
└──────────┬──────────────────┬───────────────┘
           │                  │
    ┌──────▼──────┐   ┌───────▼──────┐
    │  Jikan API  │   │ Cloudflare   │
    │  (MAL data) │   │     R2       │
    └─────────────┘   └──────────────┘
```

### 2.2 Product Functions (Ringkasan)

1. CRUD Series, Volume, Collection, Loan
2. Import otomatis dari Jikan API + download cover ke R2
3. Loan state machine dengan scheduler overdue
4. RBAC (Shield) + Auth (Breezy)
5. Activity log otomatis pada semua model utama
6. User management (ban, soft delete)

### 2.3 User Classes and Characteristics

| Role | Akses | Karakteristik |
|------|-------|---------------|
| `super_admin` | Penuh — semua resource + user management | 1 orang (pemilik koleksi), tech-savvy |
| `admin` | Resource koleksi (series, volume, collection, loan), bukan user management | Opsional, asisten |
| `user` | Read-only portal — lihat katalog & status pinjaman sendiri | Teman peminjam |

### 2.4 Operating Environment

| Komponen | Spesifikasi |
|----------|-------------|
| Server OS | Ubuntu 22.04 LTS |
| Web Server | Nginx 1.24+ |
| PHP | 8.3 dengan FPM |
| Database | MySQL 8.0 / MariaDB 10.4+ |
| Node.js | 20.x (untuk build assets) |
| Browser | Chrome 120+, Firefox 120+, Safari 17+ (modern browsers, no IE) |

### 2.5 Design and Implementation Constraints

- Primary key: UUID (bukan auto-increment integer)
- Semua model utama menggunakan soft delete
- Queue driver: database (tidak ada Redis)
- File storage: hanya Cloudflare R2, tidak ada local disk untuk user uploads
- Jikan API: tidak ada autentikasi, rate limit 3 req/detik
- PHP extension wajib: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `intl`, `zip`, `bcmath`

### 2.6 Assumptions and Dependencies

- R2 bucket sudah dibuat dan credentials tersedia sebelum deployment
- SMTP tersedia jika fitur notifikasi email diaktifkan
- Jikan API bersifat best-effort — sistem harus graceful jika API down
- Filament Shield di-generate ulang setiap kali ada resource baru (`php artisan shield:generate --all`)

---

## 3. System Features

### 3.1 Authentication & Authorization

**Description:** Sistem autentikasi untuk admin panel (Filament + Breezy) dan user portal (Breeze). RBAC dikelola Shield.

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| User submit form login dengan kredensial valid | Redirect ke dashboard sesuai role |
| User submit form login dengan kredensial invalid | Pesan error, form dikosongkan |
| User yang di-ban mencoba login | Pesan "Akun Anda di-ban: [reason]", tidak bisa masuk |
| Token session expired | Redirect ke halaman login |
| User mencoba akses resource yang tidak diizinkan Shield | HTTP 403, pesan "Unauthorized" |

**Requirements Teknis:**

- `SRS-AUTH-001` — Login menggunakan email + password, di-hash bcrypt
- `SRS-AUTH-002` — Filament Breezy menyediakan: profile page, update password, 2FA (TOTP), browser sessions
- `SRS-AUTH-003` — Shield generate permission otomatis dari semua Filament resource yang terdaftar
- `SRS-AUTH-004` — Pengecekan ban via `AuthenticateMiddleware` custom — cek field `is_banned` sebelum session dibuat
- `SRS-AUTH-005` — Role disimpan di kolom `role` tabel `users` (enum), bukan tabel pivot terpisah; Shield membaca role ini

### 3.2 Series Management

**Description:** CRUD untuk data manga series. Data dapat diinput manual atau diimport dari Jikan.

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| Admin buka `/admin/series` | Tampil tabel paginated, sortable, searchable |
| Admin klik "New Series" | Form create terbuka |
| Admin submit form valid | Record tersimpan, redirect ke list dengan notifikasi sukses |
| Admin submit form dengan `mal_id` duplikat | Validasi gagal: "MAL ID sudah ada" |
| Admin klik "Delete" pada series | Modal konfirmasi muncul, minta `deleted_reason` |
| Admin konfirmasi delete | Soft delete: `deleted_at`, `deleted_reason`, `deleted_by` diisi |

**Requirements Teknis:**

- `SRS-S-001` — Model `Series` menggunakan `HasUuids`, `SoftDeletes`, `LogsActivity`
- `SRS-S-002` — Kolom `mal_id` nullable, unique dengan constraint `unique_mal_id_when_not_null` (partial unique index)
- `SRS-S-003` — Kolom `status` menggunakan PHP enum `SeriesStatus` dengan nilai: `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published`
- `SRS-S-004` — Filament resource: table columns (`title_romaji`, `status` badge, `total_volumes`, `score`), filter by status, search by `title_romaji`/`title_english`
- `SRS-S-005` — Soft delete action memerlukan input `deleted_reason` (wajib, min 10 karakter)
- `SRS-S-006` — Trashed records dapat dilihat via filter "Termasuk yang dihapus"

### 3.3 Volume Management

**Description:** CRUD untuk volume fisik dalam suatu series.

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| Admin buka detail series | Tab "Volumes" menampilkan semua volume series ini |
| Admin tambah volume | Form inline dengan `volume_number`, `isbn`, `published_at` |
| `volume_number` duplikat dalam series | Validasi gagal: "Nomor volume sudah ada dalam series ini" |

**Requirements Teknis:**

- `SRS-V-001` — Model `Volume` menggunakan `HasUuids`, `SoftDeletes`, `LogsActivity`
- `SRS-V-002` — Unique constraint: `(series_id, volume_number)`
- `SRS-V-003` — Volume ditampilkan sebagai `RelationManager` di dalam `SeriesResource`
- `SRS-V-004` — `cover_path` nullable; jika kosong, tampilkan placeholder image

### 3.4 Collection Management

**Description:** Pencatatan kepemilikan volume oleh user.

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| Admin assign volume ke user | Record collection dibuat dengan `condition`, `location`, `acquired_at` |
| User yang sama mencoba assign volume yang sama | Validasi gagal: "User ini sudah memiliki volume tersebut" |

**Requirements Teknis:**

- `SRS-C-001` — Model `Collection` menggunakan `HasUuids`, `SoftDeletes`, `LogsActivity`
- `SRS-C-002` — Unique constraint: `(user_id, volume_id)` pada record yang tidak soft-deleted
- `SRS-C-003` — `condition` menggunakan PHP enum `VolumeCondition`: `mint`, `good`, `fair`, `poor`
- `SRS-C-004` — Collection resource menampilkan: user name, series title, volume number, condition badge, location

### 3.5 Loan System

**Description:** Pencatatan peminjaman dengan state machine lengkap.

**State Machine:**

```
           ┌─────────┐
    ┌──────►  pending ├──────────────────────┐
    │      └────┬────┘                       │
    │           │ approve                    │ cancel
    │      ┌────▼────┐                       │
    │      │  active ├──────────────────────►│
    │      └────┬────┘                       ▼
    │           │         ┌──────────┐  ┌────────────┐
    │      ┌────▼────┐    │ overdue  │  │ cancelled  │
    │      │scheduler├───►│          │  └────────────┘
    │      └─────────┘    └────┬─────┘
    │                          │
    │              ┌───────────┴──────────┐
    │              ▼                      ▼
    │         ┌──────────┐          ┌──────────┐
    └─────────│ returned │          │   lost   │
              └──────────┘          └──────────┘
```

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| Admin buat loan baru untuk volume yang sedang dipinjam | Validasi gagal: "Volume ini sedang dipinjam" |
| Admin klik "Approve" pada loan `pending` | Status → `active`, `loaned_at` diset ke now() |
| Admin klik "Return" pada loan `active`/`overdue` | Modal konfirmasi, status → `returned`, `returned_at` diset |
| Scheduler jalan, ada loan `active` dengan `due_date < now()` | Status → `overdue` (bulk update) |
| Admin klik "Mark as Lost" | Status → `lost`, notifikasi dicatat di activity log |

**Requirements Teknis:**

- `SRS-L-001` — Model `Loan` menggunakan `HasUuids`, `SoftDeletes`, `LogsActivity`
- `SRS-L-002` — Transisi status divalidasi via method `canTransitionTo(LoanStatus $status): bool`
- `SRS-L-003` — Hanya satu loan dengan status `active` atau `pending` per `collection_id` (constraint di DB + validasi Eloquent)
- `SRS-L-004` — Scheduler `MarkOverdueLoansCommand` berjalan setiap hari jam 01:00 WIB
- `SRS-L-005` — Filament table menampilkan: borrower name, volume, due date (dengan highlight merah jika overdue), status badge
- `SRS-L-006` — `borrower_user_id` nullable — peminjam boleh bukan user terdaftar di sistem

### 3.6 Jikan API Integration

**Description:** Import data manga dari Jikan API dengan rate limiting dan error handling.

**Stimulus/Response:**

| Stimulus | Response |
|----------|----------|
| Admin ketik query di halaman Jikan Search | Debounce 500ms → request ke Jikan `/manga?q=...` |
| Hasil muncul | Grid card: cover thumbnail, judul, status, score, MAL ID |
| Admin klik "Import" pada satu hasil | Modal konfirmasi tampil dengan preview data |
| Admin konfirmasi | `ImportMangaFromJikanJob` di-dispatch ke queue |
| Job berhasil | Notifikasi Filament: "Series berhasil diimport" |
| Job gagal setelah 3 retry | Record masuk `failed_jobs`, admin dapat retry dari panel |
| `mal_id` sudah ada | Job fail dengan `DuplicateSeriesException`, tidak retry |

**Requirements Teknis:**

- `SRS-J-001` — `JikanService` mengimplementasi `JikanServiceInterface` dengan method: `searchManga()`, `getMangaDetail()`, `getMangaPictures()`, `importSeries()`
- `SRS-J-002` — Rate limiting: `RateLimiter::attempt('jikan', 3, fn() => ..., 1)` — 3 hit per 1 detik
- `SRS-J-003` — HTTP client timeout: 10 detik connect, 30 detik read
- `SRS-J-004` — `ImportMangaFromJikanJob`: `$tries = 3`, `$backoff = [10, 60, 180]`, implements `ShouldQueue`
- `SRS-J-005` — Cover download: `Http::get($imageUrl)` → stream ke temp → `Storage::disk('r2')->put(...)`
- `SRS-J-006` — Jika cover download gagal, series tetap tersimpan dengan `cover_path = null`
- `SRS-J-007` — Response Jikan di-cache: search results 5 menit, detail 1 jam (cache key: `jikan.search.{md5(query)}`, `jikan.detail.{malId}`)

### 3.7 File Storage (R2)

**Description:** Semua upload file (cover manga) disimpan di Cloudflare R2.

**Requirements Teknis:**

- `SRS-ST-001` — Laravel filesystem disk `r2` dikonfigurasi dengan driver `s3` menggunakan R2 endpoint
- `SRS-ST-002` — Cover di-resize/compress sebelum upload menggunakan `joshembling/filament-image-optimizer`
- `SRS-ST-003` — Path storage: `covers/series/{uuid}.{ext}`, `covers/volumes/{uuid}.{ext}`
- `SRS-ST-004` — Public URL: `config('filesystems.disks.r2.url') . '/' . $path`
- `SRS-ST-005` — Jika R2 upload gagal, exception di-log dan user mendapat pesan error yang jelas

### 3.8 User Management

**Description:** Manajemen user oleh super admin.

**Requirements Teknis:**

- `SRS-U-001` — Model `User` menggunakan `HasUuids`, `SoftDeletes`, `LogsActivity`
- `SRS-U-002` — Field ban: `is_banned` (boolean), `ban_reason` (text nullable), `banned_at` (timestamp nullable)
- `SRS-U-003` — `super_admin` tidak bisa di-ban atau di-delete oleh `admin` (policy check)
- `SRS-U-004` — Soft delete user menyimpan `deleted_reason`; data koleksi & loan milik user tidak ikut dihapus
- `SRS-U-005` — Breezy menyediakan halaman "My Profile" untuk semua user (update name, email, password, 2FA)

### 3.9 Activity Logging

**Description:** Log otomatis setiap perubahan data menggunakan `spatie/laravel-activitylog`.

**Requirements Teknis:**

- `SRS-A-001` — Semua model utama (`Series`, `Volume`, `Collection`, `Loan`, `User`) menggunakan trait `LogsActivity`
- `SRS-A-002` — Log mencatat: `event` (created/updated/deleted), `causer` (user yang melakukan), `subject` (model yang diubah), `properties` (nilai before/after untuk updated)
- `SRS-A-003` — UI activity log via `z3d0x/filament-logger` — tampil di panel admin
- `SRS-A-004` — Log tidak di-soft-delete — hanya `super_admin` yang bisa clear log (via custom action)
- `SRS-A-005` — Retention policy: log lebih dari 1 tahun dapat dibersihkan via artisan command

### 3.10 Notifications

**Description:** Notifikasi untuk due date reminder.

**Requirements Teknis:**

- `SRS-N-001` — Scheduler mengirim email reminder H-1 sebelum `due_date` ke `borrower_user_id` (jika terdaftar dan punya email)
- `SRS-N-002` — Email menggunakan Laravel Notification dengan channel `mail`
- `SRS-N-003` — Jika SMTP tidak dikonfigurasi (`MAIL_MAILER=log`), notifikasi ditulis ke log tanpa error

---

## 4. External Interface Requirements

### 4.1 User Interfaces

**Admin Panel (`/admin`):**
- Dibangun seluruhnya dengan Filament v3
- Responsive (mobile, tablet, desktop)
- Dark mode tersedia via toggle
- Navigation: sidebar (default Filament) dengan grouping: Koleksi, Peminjaman, Jikan, Users, Settings

**User Portal (`/`) — Future:**
- Laravel Breeze dengan Blade + Alpine.js
- Read-only: katalog series, detail series + volume, status pinjaman sendiri

### 4.2 Hardware Interfaces

Tidak ada hardware interface khusus. Sistem berjalan di VPS standard.

### 4.3 Software Interfaces

**Jikan API v4:**
- Base URL: `https://api.jikan.moe/v4`
- Method: GET only
- Auth: tidak ada
- Rate limit: 3 req/detik, 60 req/menit
- Endpoint yang digunakan:
  - `GET /manga?q={query}&limit=10&page={page}` — search
  - `GET /manga/{id}` — detail by MAL ID
  - `GET /manga/{id}/pictures` — daftar gambar

**Cloudflare R2 (S3-compatible):**
- SDK: AWS SDK via Laravel's S3 driver
- Operations: `putObject`, `getObject`, `deleteObject`, `headObject`
- Bucket: single bucket untuk semua media
- Access: private bucket dengan public R2.dev URL atau signed URL

### 4.4 Communications Interfaces

- HTTPS untuk semua traffic (Certbot + Let's Encrypt)
- SMTP untuk notifikasi email (opsional)
- Tidak ada WebSocket

---

## 5. Non-Functional Requirements

### 5.1 Performance

| Requirement | Target |
|-------------|--------|
| Response time halaman list Filament | < 2 detik (1000 records, pagination 25) |
| Response time form submit | < 1 detik |
| Jikan search response | < 5 detik (termasuk round-trip ke API) |
| Cover upload (5MB image) | < 10 detik (termasuk optimize + R2 upload) |
| Queue job throughput | Minimal 10 job/menit (database queue cukup) |

### 5.2 Safety Requirements

- Tidak ada operasi yang langsung menghapus data secara permanen dari UI (semua soft delete)
- Konfirmasi modal wajib untuk semua aksi destruktif (delete, ban)
- Backup database: disarankan cron harian (di luar scope aplikasi)

### 5.3 Security Requirements

Berdasarkan OWASP Top 10:

| Ancaman | Mitigasi |
|---------|----------|
| A01 Broken Access Control | Shield RBAC — setiap resource punya policy |
| A02 Cryptographic Failures | HTTPS wajib, password bcrypt, R2 private bucket |
| A03 Injection | Eloquent ORM, no raw SQL tanpa binding, Filament sanitize input |
| A04 Insecure Design | State machine Loan mencegah transisi status ilegal |
| A05 Security Misconfiguration | `APP_DEBUG=false` di production, `.env` tidak di-commit |
| A07 Auth Failures | Rate limiting login (Laravel default 5 attempts/menit), 2FA via Breezy |
| A08 Data Integrity | UUID PK mencegah ID enumeration, activity log untuk audit trail |

### 5.4 Software Quality Attributes

- **Maintainability:** Filament resource terpisah per model, service layer untuk Jikan logic
- **Testability:** Pest PHP, `Http::fake()` untuk Jikan, `Storage::fake()` untuk R2, `Queue::fake()` untuk jobs
- **Reliability:** Queue dengan retry, Scheduler dengan overlap prevention (`withoutOverlapping()`)

### 5.5 Business Rules

- `BR-001` — Satu volume hanya bisa dipinjam oleh satu orang pada satu waktu
- `BR-002` — Volume yang di-soft-delete tidak bisa dibuat loan baru
- `BR-003` — User yang di-ban tidak bisa login tapi data historisnya tetap ada
- `BR-004` — `super_admin` tidak bisa di-ban atau di-hapus oleh `admin`
- `BR-005` — Import Jikan yang duplikat (`mal_id` sama) langsung ditolak tanpa masuk queue

---

## 6. Database Requirements

### 6.1 Tabel Utama

| Tabel | Model | Keterangan |
|-------|-------|------------|
| `users` | `User` | Auth + profile + role + ban |
| `series` | `Series` | Data manga series |
| `volumes` | `Volume` | Volume fisik per series |
| `collections` | `Collection` | Kepemilikan volume oleh user |
| `loans` | `Loan` | Transaksi peminjaman |
| `activity_log` | (Spatie) | Log aktivitas otomatis |
| `jobs` | (Laravel) | Queue jobs |
| `failed_jobs` | (Laravel) | Failed queue jobs |
| `cache` | (Laravel) | Cache store |
| `sessions` | (Laravel) | Database sessions |

Detail lengkap kolom, relasi, dan migration ada di [ERD.md](./ERD.md).

### 6.2 Indexing Strategy

| Tabel | Index | Alasan |
|-------|-------|--------|
| `series` | `mal_id` (unique partial) | Lookup saat import Jikan |
| `series` | `title_romaji` (fulltext) | Search by judul |
| `volumes` | `(series_id, volume_number)` (unique) | Constraint + lookup |
| `collections` | `(user_id, volume_id)` (unique) | Constraint kepemilikan |
| `loans` | `collection_id`, `status` | Filter active loans |
| `loans` | `due_date` | Scheduler overdue check |
| `activity_log` | `(subject_type, subject_id)` | Lookup log per model |

### 6.3 Soft Delete Strategy

Semua model utama menggunakan:
- `deleted_at` — timestamp penghapusan (Eloquent SoftDeletes standard)
- `deleted_reason` — alasan penghapusan (wajib diisi saat delete)
- `deleted_by` — UUID user yang menghapus (diisi otomatis via observer)

Query default Eloquent otomatis exclude soft-deleted records. Untuk include: `withTrashed()`.

### 6.4 UUID Strategy

- Semua PK menggunakan UUID (Laravel `HasUuids` trait — UUID v7 ordered)
- UUID v7 dipilih karena time-ordered, lebih efisien untuk B-tree index dibanding UUID v4
- FK menggunakan tipe `char(36)` atau `uuid` (tergantung driver)
- Keuntungan: tidak ada ID sequential yang bisa ditebak (security), aman untuk distributed insert

---

## 7. API Requirements

### 7.1 Internal API

Tidak ada REST API publik di v1. Semua interaksi via Filament Livewire (server-side rendering). Jika diperlukan di masa depan, endpoint akan menggunakan Laravel Sanctum untuk autentikasi token.

### 7.2 Jikan API Endpoints

| Endpoint | Digunakan Untuk | Cache TTL |
|----------|----------------|-----------|
| `GET /manga?q={q}&limit=10` | Search di panel Jikan | 5 menit |
| `GET /manga/{id}` | Detail series saat import | 1 jam |
| `GET /manga/{id}/pictures` | Ambil gambar alternatif | 1 jam |

Response fields yang digunakan dari Jikan:

```json
{
  "data": {
    "mal_id": 1,
    "title": "...",
    "title_english": "...",
    "title_japanese": "...",
    "synopsis": "...",
    "status": "Finished",
    "published": { "from": "...", "to": "..." },
    "volumes": 10,
    "score": 8.5,
    "rank": 42,
    "images": {
      "jpg": { "large_image_url": "https://..." }
    }
  }
}
```

### 7.3 Rate Limiting Policy

- Jikan: 3 request/detik, 60 request/menit (enforced via Laravel `RateLimiter`)
- Implementasi: sebelum setiap HTTP call ke Jikan, cek `RateLimiter::tooManyAttempts('jikan-api', 3)`; jika true, sleep 1 detik dan retry
- Tidak ada auth token Jikan — semua anonymous
