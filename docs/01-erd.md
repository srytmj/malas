# Entity Relationship Diagram (ERD)

## Penjelasan Skema

Database MALAS terbagi menjadi dua kelompok entitas yang dihubungkan oleh `series`:

- **Kelompok Digital**: `chapters`, `sources`, `user_tracking` - mengelola progress bacaan dan sumber eksternal.
- **Kelompok Fisik**: `volumes`, `user_collections`, `loans`, `loan_events` - mengelola koleksi fisik dan peminjaman.
- **Kelompok Audit**: `activity_log` - mencatat semua aksi soft delete dan restore di seluruh tabel.

Semua tabel (kecuali `loan_events`) menggunakan UUID sebagai primary key dan memiliki kolom `deleted_at`, `deleted_by`, `deletion_reason` untuk SoftDeletes dengan actor tracking.

## Diagram ERD

```mermaid
erDiagram
    USERS ||--o{ USER_COLLECTIONS : "owns"
    USERS ||--o{ USER_TRACKING : "tracks"
    USERS ||--o{ ACTIVITY_LOG : "performs"

    SERIES ||--o{ VOLUMES : "has"
    SERIES ||--o{ CHAPTERS : "has"
    SERIES ||--o{ USER_TRACKING : "tracked_by"

    SOURCES ||--o{ CHAPTERS : "provides"

    VOLUMES ||--o{ USER_COLLECTIONS : "collected_as"

    USER_COLLECTIONS ||--o{ LOANS : "loaned_via"

    LOANS ||--o{ LOAN_EVENTS : "logs"

    SERIES {
        uuid id PK
        text title_canonical "GIN trigram idx_series_title_trgm"
        text title_original
        text title_en
        jsonb alternative_titles
        enum type "manga|manhwa|manhua|novel"
        enum status "ongoing|completed|hiatus|cancelled"
        text synopsis
        text cover_url
        jsonb external_ids "GIN idx_series_external"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    VOLUMES {
        uuid id PK
        uuid series_id FK
        numeric volume_number "supports 0.5, 1, 1.5"
        text title
        integer total_chapters
        text isbn "indexed"
        text publisher
        date release_date
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    CHAPTERS {
        uuid id PK
        uuid series_id FK
        uuid source_id FK
        numeric chapter_number "supports decimal"
        text title
        timestamp release_date
        text url
        char_2 language "default 'en'"
        text group_name
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    SOURCES {
        uuid id PK
        text name
        text key "unique"
        text base_url
        integer rate_limit_per_second "default 1"
        timestamp health_check_at
        integer failure_count "default 0"
        jsonb config
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    USER_COLLECTIONS {
        uuid id PK
        uuid user_id FK
        uuid volume_id FK
        enum ownership_status "owned|missing|wishlist|preordered"
        enum condition "mint|very_good|good|acceptable|poor"
        text storage_location
        date purchase_date
        decimal purchase_price
        text notes
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    LOANS {
        uuid id PK
        uuid user_collection_id FK
        text borrower_name
        text borrower_contact
        date loan_date "default today"
        date due_date
        date return_date
        enum status "active|overdue|returned|lost"
        timestamp reminder_sent_at
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    LOAN_EVENTS {
        uuid id PK
        uuid loan_id FK
        enum event_type "created|returned|overdue_notified|lost|extended"
        jsonb metadata
        timestamp created_at
    }

    USER_TRACKING {
        uuid id PK
        uuid user_id FK
        uuid series_id FK
        decimal current_chapter "default 0"
        timestamp last_read_at
        date started_at
        date completed_at
        integer score "1-10"
        enum status "reading|completed|paused|dropped|plan_to_read"
        timestamp deleted_at
        uuid deleted_by FK
        text deletion_reason
    }

    ACTIVITY_LOG {
        uuid id PK
        uuid user_id FK
        text entity_type
        uuid entity_id
        enum action "deleted|restored"
        text reason
        jsonb metadata
        timestamp created_at
    }

    USERS {
        uuid id PK
        enum role "user|admin|super_admin"
    }
```

## Daftar Tabel dan Fungsi

| Tabel | Fungsi |
|---|---|
| `series` | Entitas utama, menyimpan metadata judul manga/manhwa/manhua/novel. Penghubung antara lapisan Tracking dan Collection. |
| `volumes` | Representasi buku fisik per series, mendukung nomor volume desimal (mis. 0.5, 1.5). |
| `chapters` | Unit bacaan digital dari sumber eksternal, terkait ke `sources`. |
| `sources` | Registry sumber eksternal (mis. MangaDex), menyimpan konfigurasi, rate limit, status health check. |
| `user_collections` | Kepemilikan volume fisik oleh user (owned/missing/wishlist/preordered) beserta kondisi dan lokasi penyimpanan. |
| `loans` | Data peminjaman volume fisik ke pihak luar (nama peminjam, due date, status). |
| `loan_events` | Log event immutable (append-only) untuk riwayat peminjaman (created, returned, overdue_notified, lost, extended). |
| `user_tracking` | Progress bacaan digital user per series (chapter saat ini, status, skor). |
| `activity_log` | Audit trail untuk semua aksi soft delete dan restore di seluruh tabel. |
| `users` | Data pengguna, termasuk kolom `role` untuk pembagian akses (user/admin/super_admin). |

## Catatan Partial Unique Index

Partial unique index tidak dapat direpresentasikan langsung dalam Mermaid ERD, sehingga dicatat secara terpisah:

| Tabel | Partial Unique Index | Kondisi | Tujuan |
|---|---|---|---|
| `user_collections` | `(user_id, volume_id)` | `WHERE deleted_at IS NULL` | Satu user tidak boleh punya entri aktif duplikat untuk volume yang sama |
| `loans` | `(user_collection_id)` | `WHERE deleted_at IS NULL AND status IN ('active','overdue')` | Satu volume tidak boleh dipinjam dua orang sekaligus |
| `user_tracking` | `(user_id, series_id)` | `WHERE deleted_at IS NULL` | Satu user hanya boleh punya satu tracking aktif per series |
| `chapters` | `(series_id, source_id, chapter_number, language)` | unique constraint biasa (non-partial) | Mencegah duplikasi chapter dari sumber yang sama |

**Catatan khusus**: `loan_events` adalah tabel append-only (immutable). Tidak memiliki `deleted_at`, `deleted_by`, atau `deletion_reason`. Tidak ada operasi UPDATE atau DELETE yang diperbolehkan pada tabel ini.
