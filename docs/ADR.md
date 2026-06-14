# ADR — Architecture Decision Records
# MALAS (Manga Library Admin System)

**Versi:** 1.0  
**Tanggal:** 2026-06-14  
**Format:** Title / Status / Context / Decision / Consequences

---

## ADR-001: Menggunakan Filament v5 sebagai Admin Panel

**Status:** Accepted

### Context

Proyek lama (`src/`) menggunakan custom Blade admin panel yang dibangun dari nol. Hasilnya fungsional tapi memakan waktu besar untuk fitur-fitur boilerplate: DataTable, form validation, breadcrumb, dark mode, pagination, filter, dan sebagainya. Setiap resource baru butuh ~200–400 baris Blade + controller + request class.

Alternatif yang dipertimbangkan:
- Custom Blade admin (dilanjutkan dari proyek lama)
- Laravel Nova (berbayar, $299/project)
- Filament v5 (open source, TALL stack)
- Backpack for Laravel (berbayar)

### Decision

Menggunakan **Filament v5** sebagai admin panel.

Alasan utama:
- Open source, tidak ada biaya lisensi
- CRUD resource selesai dalam ~50–100 baris PHP
- Built-in: dark mode, responsive, filter, search, pagination, relation manager
- Ekosistem plugin yang aktif (Shield, Breezy, Logger, dll)
- Berbasis Livewire — tidak butuh SPA/API layer terpisah
- Community support besar, dokumentasi lengkap

### Consequences

**Positif:**
- Development speed 3–5× lebih cepat untuk admin CRUD
- Dark mode, responsive, dan accessibility sudah tersedia
- Plugin Shield dan Breezy menangani RBAC dan auth profile

**Negatif:**
- Terikat pada opini Filament — kustomisasi di luar pola standar lebih sulit
- Livewire menambah complexity untuk interaksi real-time yang kompleks
- Filament v5 masih relatif baru — beberapa plugin mungkin belum kompatibel

---

## ADR-002: UUID sebagai Primary Key

**Status:** Accepted

### Context

Laravel default menggunakan auto-increment integer sebagai PK (`id` bigint). Ini sederhana dan performa index-nya optimal. Namun ada trade-off yang perlu dipertimbangkan untuk MALAS.

Alternatif:
- Auto-increment integer (Laravel default)
- UUID v4 (random)
- UUID v7 (time-ordered)
- ULID

### Decision

Menggunakan **UUID v7** via Laravel `HasUuids` trait.

Alasan:
- **Security:** Integer sequential (`/series/1`, `/series/2`) mudah dienumerasi oleh pihak lain. UUID tidak bisa ditebak.
- **Distributed-safe:** UUID bisa di-generate di aplikasi tanpa round-trip ke DB.
- **UUID v7 spesifik:** Time-ordered — 48-bit prefix adalah timestamp, sehingga index B-tree lebih efisien dibanding UUID v4 yang sepenuhnya random.
- Laravel `HasUuids` secara default sudah menggunakan UUID v7 sejak Laravel 11.

### Consequences

**Positif:**
- Tidak ada ID sequential yang bisa ditebak
- Aman untuk multi-tenant atau distributed insert di masa depan
- UUID v7 hampir setara performa auto-increment untuk index

**Negatif:**
- Storage lebih besar: `char(36)` vs `bigint(8)` — ~4.5× lebih besar per PK
- FK joins sedikit lebih lambat karena string comparison vs integer comparison
- Debug / log kurang mudah dibaca ("kenapa `a1b2c3...` ini error?")
- Tidak ada native UUID type di MySQL — disimpan sebagai `char(36)` atau `binary(16)`

---

## ADR-003: Cloudflare R2 sebagai File Storage

**Status:** Accepted

### Context

Cover manga perlu disimpan di suatu tempat yang bisa diakses via URL. Opsi yang dipertimbangkan:

- **Local disk** — simpan di `/storage/app/public` server
- **AWS S3** — object storage populer, ada egress cost
- **DigitalOcean Spaces** — S3-compatible, $5/bulan
- **Cloudflare R2** — S3-compatible, **zero egress cost**
- **Bunny CDN** — CDN + storage, murah

### Decision

Menggunakan **Cloudflare R2**.

Alasan utama:
- **Zero egress cost** — tidak bayar untuk bandwidth keluar (ini pembeda utama dari S3)
- **S3-compatible** — bisa pakai AWS SDK / Laravel's S3 driver tanpa perubahan code
- **Free tier** — 10GB storage + 1 juta Class A operations/bulan gratis
- Terintegrasi baik dengan Cloudflare CDN jika diperlukan

### Consequences

**Positif:**
- Tidak ada biaya bandwidth untuk serve cover image
- Free tier cukup untuk proyek personal skala ini
- S3 compatibility berarti migrasi ke S3/Spaces mudah jika diperlukan

**Negatif:**
- **Vendor lock-in ringan** — meski S3-compatible, ada beberapa fitur S3 yang tidak tersedia di R2 (lifecycle policies terbatas, event notifications berbeda)
- Tidak ada built-in CDN — URL langsung ke R2, bukan edge-cached (bisa tambah Cloudflare CDN di atas R2 jika butuh)
- Debug upload error bisa lebih susah karena error message R2 kurang informatif

---

## ADR-004: Database Queue daripada Redis

**Status:** Accepted

### Context

Laravel mendukung beberapa queue driver: `sync`, `database`, `redis`, `sqs`, dan lainnya. Jikan import dan cover download perlu dijalankan di background — butuh queue.

Alternatif:
- **Redis** — performan, fitur lengkap, tapi butuh service tambahan
- **Database** — pakai tabel `jobs` di MySQL, sudah ada
- **SQS** — AWS managed, berbayar, overkill
- **Sync** — tidak ada background, blocking

### Decision

Menggunakan **database queue driver**.

Alasan:
- MALAS adalah proyek personal — volume job rendah (< 100 job/hari)
- Database queue tidak butuh service Redis tambahan (tidak ada di VPS basic)
- Setup minimal: `QUEUE_CONNECTION=database`, jalankan `php artisan queue:work`
- Cukup untuk use case: Jikan import, notifikasi email, cover download

Kapan upgrade ke Redis:
- Volume > 500 job/hari
- Latency queue mulai terasa (database queue polling setiap 1 detik)
- Butuh fitur Redis-only: sorted sets, pub/sub, priority queues

### Consequences

**Positif:**
- Zero dependency tambahan — tidak perlu install/maintain Redis
- Failed jobs tersimpan di `failed_jobs` table yang bisa dilihat langsung di DB

**Negatif:**
- Performa lebih rendah dari Redis — polling setiap 1 detik vs push
- Database queue menambah load ke MySQL saat job banyak
- Tidak bisa pakai fitur Redis-specific: delayed jobs dengan presisi tinggi, pub/sub

---

## ADR-005: Soft Delete dengan Reason & Actor

**Status:** Accepted

### Context

Data perpustakaan punya value historis tinggi. Menghapus series secara hard delete berarti kehilangan riwayat loan, koleksi, dan referensi. Soft delete standar Laravel hanya menyimpan `deleted_at`.

Pertanyaan: apakah cukup `deleted_at` saja, atau perlu lebih?

### Decision

Menggunakan **extended soft delete**: `deleted_at` + `deleted_reason` + `deleted_by`.

Alasan:
- **Audit trail:** Tahu siapa yang menghapus dan mengapa — penting untuk accountability
- **Undo support:** Data tidak hilang, bisa di-restore jika ada kesalahan
- **Activity log complement:** Spatie activity log mencatat event, tapi `deleted_reason` langsung di model lebih mudah diakses
- `deleted_by` diisi otomatis via Model Observer — tidak perlu kode manual di setiap delete

Implementasi: trait `SoftDeletes` (Laravel) + kolom tambahan `deleted_reason TEXT` dan `deleted_by UUID FK`.

### Consequences

**Positif:**
- Full audit trail tanpa harus query `activity_log` setiap saat
- Data terhapus tetap ada dan bisa di-restore
- Filament bisa tampilkan "trashed records" dengan filter

**Negatif:**
- Storage lebih besar — soft-deleted records tidak pernah benar-benar hilang
- Query harus selalu aware `WHERE deleted_at IS NULL` (Eloquent handle ini otomatis)
- Unique constraint perlu dipikirkan ulang — misal: `unique(user_id, volume_id)` akan gagal jika record di-soft-delete lalu coba insert ulang. Perlu partial unique index atau custom validation.

---

## ADR-006: Jikan API (tidak resmi) vs. Data Manual

**Status:** Accepted

### Context

Data manga (judul, synopsis, cover, score, rank) bisa diinput:
1. **Manual** — admin ketik sendiri semua data
2. **Jikan API** — scrape dari MAL via API tidak resmi

Jikan API risiko:
- Tidak ada SLA — bisa down kapan saja
- Bisa berubah tanpa notice
- Rate limit ketat (3 req/detik)
- Bukan official MyAnimeList API

### Decision

Menggunakan **Jikan API sebagai opsional import**, bukan dependency wajib.

Sistem dirancang sehingga:
- Import dari Jikan adalah **shortcut**, bukan satu-satunya cara
- Admin tetap bisa input manual jika Jikan down
- Data yang sudah diimport **disalin ke DB sendiri** — tidak ada real-time dependency ke Jikan setelah import
- Cover yang didownload disimpan di R2 — tidak ada hotlink ke server Jikan/MAL

Mitigation strategy:
- Error handling komprehensif (timeout, 429, 503)
- Retry dengan exponential backoff
- Cache response (search 5 menit, detail 1 jam)
- Jika Jikan down total, sistem tetap berfungsi 100% — hanya fitur import yang tidak tersedia

### Consequences

**Positif:**
- UX jauh lebih baik — import 1 klik vs input manual 15+ field
- Data akurat karena berasal dari MAL community
- Tidak ada runtime dependency setelah data tersimpan

**Negatif:**
- Jikan bisa berubah struktur response tanpa notice — butuh update mapping
- Rate limit memaksa antrian job saat import banyak sekaligus
- Jika MAL mengubah kebijakan dan blokir Jikan, fitur import mati total

---

## ADR-007: Filament Shield untuk RBAC

**Status:** Accepted

### Context

MALAS punya 3 role: `super_admin`, `admin`, `user`. Perlu sistem yang mengontrol siapa bisa akses resource apa di Filament panel.

Opsi:
- **Custom policy** — tulis `SeriesPolicy`, `VolumePolicy`, dst. manual
- **Filament Shield** (`bezhansalleh/filament-shield`) — auto-generate permission dari resource
- **Spatie Permission** (`spatie/laravel-permission`) — DB-based, lebih fleksibel

### Decision

Menggunakan **Filament Shield** dengan role disimpan di kolom `role` (enum) di tabel `users`.

Alasan:
- Shield auto-generate permission untuk setiap Filament resource tanpa menulis policy manual
- Integrasi native dengan Filament — permission langsung terbaca di resource
- Tidak butuh tabel `roles` dan `permissions` terpisah (lebih simpel untuk use case ini)
- Command `php artisan shield:generate --all` sinkronisasi permission setiap ada resource baru

Trade-off vs Spatie Permission:
- Shield lebih opinionated — cocok untuk Filament-centric apps
- Spatie Permission lebih fleksibel (dynamic roles, multiple roles per user) — overkill untuk 3 role fixed

Catatan integrasi: Shield membaca role dari field `role` di model `User`. Konfigurasi di `config/filament-shield.php` untuk map enum value ke Shield role.

### Consequences

**Positif:**
- Zero boilerplate policy — Shield generate otomatis
- UI permission management tersedia di panel (bisa toggle permission per role)
- Konsisten dengan Filament ekosistem

**Negatif:**
- Jika butuh permission granular di luar Filament (misal: REST API), Shield tidak cukup — perlu Spatie Permission
- Upgrade Filament versi major bisa membutuhkan regenerate Shield config
- Role bersifat static (enum) — tidak bisa tambah role baru tanpa migration

---

## ADR-008: MySQL/MariaDB daripada PostgreSQL

**Status:** Accepted

### Context

Pilihan database: MySQL 8 / MariaDB 10.4+ vs PostgreSQL 16.

Konteks deployment:
- Development: XAMPP (MariaDB bundled)
- Staging/Production: VPS Ubuntu (bisa pilih keduanya)
- Tim: satu orang, lebih familiar dengan MySQL

### Decision

Menggunakan **MySQL 8 / MariaDB 10.4+**.

Alasan:
- Familiar — tidak ada learning curve baru
- MariaDB tersedia di XAMPP (development environment saat ini)
- Shared hosting yang umum dipakai hampir selalu MySQL
- Untuk use case MALAS (single-tenant, low concurrency), perbedaan performa MySQL vs PostgreSQL tidak signifikan

Kapan migrate ke PostgreSQL:
- Butuh fitur PostgreSQL-specific: JSONB query, full-text search yang lebih powerful, array types, window functions yang kompleks
- Deploy ke platform yang default PostgreSQL (Railway, Heroku, Supabase)
- Tim lebih familiar dengan PostgreSQL

Catatan: Migration files yang ditulis menggunakan Blueprint abstraction — hampir semua bisa dipakai di PostgreSQL dengan perubahan minimal (terutama kolom `enum` yang di PostgreSQL perlu cast berbeda).

### Consequences

**Positif:**
- Zero learning curve untuk database
- Compatible dengan XAMPP development setup
- Support luas di shared hosting

**Negatif:**
- Beberapa fitur modern hanya ada di PostgreSQL: native UUID type, JSONB, partial index yang lebih fleksibel
- Partial unique index (untuk `mal_id` nullable) butuh raw statement di MySQL — di PostgreSQL ini native
- Jika di masa depan pindah ke PostgreSQL, ada beberapa migration yang perlu diupdate
