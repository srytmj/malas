# Entity Relationship Diagram (ERD)

## Penjelasan Skema

Database MALAS menggunakan MySQL (MariaDB 10.4.32 via XAMPP) dengan dua kelompok entitas utama:

- **Kelompok Fisik**: `series`, `volumes`, `user_libraries`, `user_collections`, `loans`, `loan_items` — inti sistem koleksi dan peminjaman.
- **Kelompok Jikan**: `jikan_schedules`, `jikan_scrape_sessions` — manajemen scraping dari MyAnimeList API.
- **Kelompok Sistem**: `users`, `activity_logs`, `jobs`, `failed_jobs` — autentikasi, audit trail, dan queue.

Semua tabel utama menggunakan UUID sebagai primary key (via `HasUuids` trait) dan memiliki kolom `deleted_at`, `deleted_by`, `deletion_reason` untuk soft deletes dengan actor tracking (via `HasSoftDeletesWithActor`).

## Diagram ERD

```mermaid
erDiagram
    USERS ||--o{ USER_LIBRARIES : "owns"
    USERS ||--o{ LOANS : "borrows"
    USERS ||--o{ ACTIVITY_LOGS : "creates"

    SERIES ||--o{ VOLUMES : "has"
    SERIES ||--o{ USER_LIBRARIES : "tracked_in"

    USER_LIBRARIES ||--o{ USER_COLLECTIONS : "contains"
    VOLUMES ||--o{ USER_COLLECTIONS : "owned_as"

    USER_COLLECTIONS ||--o{ LOAN_ITEMS : "loaned_via"
    LOANS ||--o{ LOAN_ITEMS : "includes"

    JIKAN_SCHEDULES ||--o{ JIKAN_SCRAPE_SESSIONS : "spawns"

    SERIES {
        uuid id PK
        varchar title_romaji
        varchar title_english "nullable"
        varchar title_japanese "nullable"
        text synopsis "nullable"
        enum status "publishing|finished|on_hiatus|discontinued|not_yet_published"
        int total_volumes "nullable"
        date published_from "nullable"
        date published_to "nullable"
        decimal score "nullable, 0-10"
        int rank "nullable"
        int mal_id "nullable, unique"
        varchar cover_image_path "nullable, R2 path"
        timestamp last_synced_at "nullable"
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable, FK users"
        varchar deletion_reason "nullable"
    }

    VOLUMES {
        uuid id PK
        uuid series_id FK
        decimal volume_number "supports 0.5, 1.5"
        varchar title "nullable"
        varchar isbn "nullable"
        varchar publisher "nullable"
        date release_date "nullable"
        varchar cover_image_path "nullable, R2 path"
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable, FK users"
        varchar deletion_reason "nullable"
    }

    USER_LIBRARIES {
        uuid id PK
        uuid user_id FK
        uuid series_id FK
        timestamps created_at_updated_at
    }

    USER_COLLECTIONS {
        uuid id PK
        uuid user_library_id FK
        uuid volume_id FK
        enum condition "mint|very_good|good|fair|poor"
        bool is_for_loan "default true"
        decimal purchase_price "nullable"
        date purchase_date "nullable"
        text notes "nullable"
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable, FK users"
        varchar deletion_reason "nullable"
    }

    LOANS {
        uuid id PK
        uuid user_id FK
        varchar borrower_name
        varchar borrower_contact "nullable"
        date loan_date
        date due_date "nullable"
        date return_date "nullable"
        enum status "active|returned|overdue|lost"
        text notes "nullable"
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable, FK users"
        varchar deletion_reason "nullable"
    }

    LOAN_ITEMS {
        uuid id PK
        uuid loan_id FK
        uuid user_collection_id FK
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable, FK users"
        varchar deletion_reason "nullable"
    }

    USERS {
        uuid id PK
        varchar name
        varchar email "unique"
        varchar password
        enum role "user|super_admin"
        bool is_banned "default false"
        text ban_reason "nullable"
        timestamps created_at_updated_at
        timestamp deleted_at "nullable"
        uuid deleted_by "nullable"
        varchar deletion_reason "nullable"
    }

    ACTIVITY_LOGS {
        uuid id PK
        uuid user_id "nullable, FK users"
        varchar action "e.g. series.created, series.deleted"
        varchar entity_type
        varchar entity_id
        varchar reason "nullable"
        varchar ip_address "nullable"
        timestamp created_at
    }

    JIKAN_SCHEDULES {
        bigint id PK
        varchar name "100 chars"
        tinyint hour "0-23"
        tinyint minute "0-59"
        smallint start_year "nullable"
        smallint end_year "nullable"
        int sort_order "default 0"
        timestamp last_run_at "nullable"
        timestamps created_at_updated_at
    }

    JIKAN_SCRAPE_SESSIONS {
        bigint id PK
        bigint schedule_id "nullable, FK jikan_schedules"
        enum status "pending|queued|running|completed|failed"
        int current_page "default 0"
        int total_pages "nullable"
        smallint start_year "nullable"
        smallint end_year "nullable"
        timestamp started_at "nullable"
        timestamp completed_at "nullable"
        text error_message "nullable"
        timestamps created_at_updated_at
    }
```

## Daftar Tabel dan Fungsi

| Tabel | Fungsi |
|---|---|
| `users` | Data pengguna: nama, email, role (`user`/`super_admin`), status ban. UUID PK. |
| `series` | Entitas utama — metadata judul manga/manhwa. Penghubung antara volumes dan user_libraries. UUID PK, unique `mal_id`. |
| `volumes` | Buku fisik per series. Mendukung `volume_number` desimal. UUID PK. Cover disimpan di R2. |
| `user_libraries` | Tabel bridge `users` ↔ `series`. Dibuat saat user pertama kali memiliki/mendaftarkan koleksi dari series ini. |
| `user_collections` | Kepemilikan satu volume fisik oleh user (via `user_library_id`), beserta kondisi, harga beli, dan status pinjam. |
| `loans` | Header peminjaman satu sesi — ke siapa, kapan, due date, status. |
| `loan_items` | Baris detail peminjaman: volume mana yang masuk dalam loan ini. |
| `jikan_schedules` | Konfigurasi jadwal scraping otomatis: jam, menit, rentang tahun, urutan eksekusi. |
| `jikan_scrape_sessions` | Log satu sesi scraping — halaman saat ini, status, error, waktu mulai/selesai. |
| `activity_logs` | Audit trail: setiap aksi CRUD/delete oleh admin dicatat di sini. |
| `jobs` | Antrian pekerjaan background (queue driver: database). |
| `failed_jobs` | Pekerjaan background yang gagal setelah semua retry. |

## Catatan Penting

- `user_libraries` adalah entitas implisit: dibuat otomatis via `UserLibrary::firstOrCreate()` saat menambah koleksi.
- Cover image (series dan volume) disimpan sebagai path relatif di R2. URL publik: `https://pub-da18e323b0e64eadadb3ac8e6a28064b.r2.dev/{path}`.
- Tidak ada tabel `chapters`, `sources`, `user_tracking`, atau `loan_events` — fitur tracking digital belum diimplementasi.
