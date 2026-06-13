# Glossary — Istilah Teknis MALAS

| Istilah | Definisi |
|---|---|
| **MALAS** | Manga Library System. Platform manajemen koleksi fisik manga/manhwa dengan fitur peminjaman dan scraping data dari Jikan. |
| **Series** | Entitas utama yang merepresentasikan satu judul manga/manhwa. Penghubung antara `volumes` (fisik) dan `user_libraries`. UUID primary key. |
| **Volume** | Buku fisik manga/manhwa yang bisa dikoleksi. Terhubung ke satu series, mendukung nomor volume desimal (mis. 0.5, 1.5). Cover disimpan di R2. |
| **UserLibrary** | Tabel bridge antara `users` dan `series`. Dibuat otomatis via `firstOrCreate()` saat user pertama kali memiliki volume dari suatu series. |
| **UserCollection** | Kepemilikan satu volume fisik oleh user (via `user_library_id`). Berisi kondisi, harga beli, tanggal beli, flag `is_for_loan`. |
| **Loan** | Header peminjaman — ke siapa volume dipinjamkan, tanggal, due date, status (active/returned/overdue/lost). |
| **LoanItem** | Detail baris dalam satu peminjaman: volume mana yang ikut dipinjamkan. |
| **SoftDeletes** | Fitur Laravel yang mengganti DELETE dengan pengisian `deleted_at`. Data tidak benar-benar hilang dari database. |
| **HasSoftDeletesWithActor** | Trait custom MALAS yang menambah `deleted_by` (UUID user yang menghapus) dan `deletion_reason` (alasan) ke soft deletes, serta auto-catat ke `activity_logs`. |
| **ActivityLog** | Tabel audit trail yang mencatat semua aksi admin (create, update, delete). Berisi: `action`, `entity_type`, `entity_id`, `reason`, `ip_address`. |
| **HasUuids** | Trait Laravel bawaan yang membuat UUID otomatis sebagai primary key saat model dibuat. Digunakan di semua model utama MALAS. |
| **$guarded = []** | Pola di MALAS: semua model menggunakan `$guarded = []` (bukan `$fillable`), artinya semua kolom bisa mass-assigned. Validasi dilakukan di controller/request. |
| **R2 (Cloudflare R2)** | Layanan object storage dari Cloudflare, S3-compatible. Digunakan untuk cover image series dan volume. Public URL: `https://pub-da18e323b0e64eadadb3ac8e6a28064b.r2.dev`. |
| **FILESYSTEM_DISK=r2** | Konfigurasi Laravel: disk default (`Storage::disk()`) diarahkan ke Cloudflare R2. |
| **Jikan API** | Wrapper tidak resmi untuk MyAnimeList API (jikan.moe/v4). Digunakan untuk scraping metadata manga. |
| **JikanSchedule** | Konfigurasi jadwal scraping Jikan: nama, jam/menit eksekusi, rentang tahun (start/end), urutan (sort_order). |
| **JikanScrapeSession** | Log satu sesi scraping Jikan. Status: `pending` → `queued` → `running` → `completed`/`failed`. |
| **Queue** | Mekanisme Laravel untuk menjalankan job asinkron di background. MALAS menggunakan driver `database` (bukan Redis). |
| **ScrapeJikanPageJob** | Job queue yang memproses satu halaman hasil Jikan API, upsert ke tabel `series`, lalu dispatch dirinya sendiri ke halaman berikutnya. |
| **AJAX DataTable** | Pola custom di panel admin: tabel data di-render via JavaScript dari endpoint AJAX (bukan server-side HTML). Controller detect `$request->ajax()` untuk return JSON. |
| **TomSelect** | Library JavaScript untuk dropdown/autocomplete yang dapat melakukan search AJAX. Digunakan untuk pilih series (di form tambah koleksi). |
| **SortableJS** | Library JavaScript drag-and-drop. Digunakan di halaman Jikan untuk mengubah urutan schedules. |
| **Alpine.js** | Framework JavaScript reaktif ringan. Digunakan di semua halaman admin yang membutuhkan interaktivitas (form tambah koleksi, Jikan schedules, dll). |
| **Flowbite** | Library komponen UI berbasis Tailwind CSS. Digunakan untuk modal, badge, dan komponen form. |
| **Vite** | Build tool frontend. `npm run dev` untuk development dengan HMR, `npm run build` untuk production. |
| **Trashed** | Record dengan `deleted_at IS NOT NULL` — sudah di-soft-delete. Ditampilkan di DataTable dengan `withTrashed()`, biasanya dengan opacity lebih rendah. |
| **BatchDestroy** | Fitur di SeriesController: hapus banyak series sekaligus via POST `/admin/series/batch-destroy` dengan array `ids` dan `reason`. |
| **BulkStore** | Fitur di CollectionController: tambah banyak volume dari berbagai series sekaligus via POST `/admin/collections/bulk` dengan array `entries`. |
| **Super Admin** | Role tertinggi di MALAS (`role = 'super_admin'`). Memiliki akses ke semua halaman admin. |
