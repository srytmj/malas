# Glossary - Istilah Teknis MALAS

| Istilah | Definisi |
|---|---|
| **MALAS** | Manga Library System. Nama proyek yang menggabungkan pelacakan bacaan digital dan manajemen koleksi fisik manga/manhwa. |
| **Series** | Entitas utama yang merepresentasikan satu judul manga/manhwa/manhua/novel, menjadi penghubung antara lapisan Tracking dan Collection. |
| **Volume** | Buku fisik manga/manhwa yang bisa dipegang dan dikoleksi, mendukung nomor desimal (mis. volume 0.5). |
| **Chapter** | Unit bacaan digital dari sumber eksternal (mis. MangaDex), terkait ke satu `series` dan satu `source`. |
| **SoftDeletes** | Fitur Laravel yang mengganti operasi DELETE dengan pengisian kolom `deleted_at`, sehingga data tidak benar-benar hilang dari database. |
| **HasSoftDeletesWithActor** | Trait custom di MALAS yang memperluas SoftDeletes Laravel dengan pencatatan `deleted_by` (siapa yang menghapus) dan `deletion_reason` (alasan penghapusan). |
| **Partial Unique Index** | Index unik PostgreSQL dengan kondisi `WHERE`, sehingga constraint hanya berlaku untuk subset data tertentu (mis. hanya data yang belum di-soft-delete). |
| **Source Adapter** | Implementasi `SourceAdapterInterface` untuk satu sumber eksternal tertentu (mis. `MangaDexAdapter`), bertugas mencari series dan mengambil chapter dari sumber tersebut. |
| **Queue** | Mekanisme Laravel untuk menjalankan tugas secara asynchronous di background, menggunakan Redis sebagai driver di MALAS. |
| **Horizon** | Dashboard dan manajer queue Laravel berbasis Redis, digunakan untuk memantau dan mengatur job dengan berbagai tingkat prioritas (critical, high, default, low). |
| **Filament** | Framework admin panel berbasis Laravel (versi 3.x) yang digunakan untuk membangun seluruh antarmuka admin MALAS. |
| **R2 (Cloudflare R2)** | Layanan object storage dari Cloudflare, digunakan untuk menyimpan cover image series, dipilih karena biaya egress lebih rendah dibanding S3. |
| **Activity Log** | Tabel audit trail yang mencatat setiap aksi `deleted` dan `restored` di seluruh tabel ber-SoftDeletes, termasuk siapa, kapan, dan alasannya. |
| **Loan Events** | Tabel log immutable (append-only) yang mencatat riwayat siklus hidup satu peminjaman (created, returned, overdue_notified, lost, extended). |
| **Deduplication** | Proses mendeteksi dan menggabungkan entri `series` yang merupakan judul yang sama dari sumber berbeda, berdasarkan `external_ids`, judul exact, atau kemiripan trigram. |
| **Confidence Score** | Skor kepercayaan hasil deduplikasi (0-100%). Skor ≥95% di-auto-merge, skor 70-94% masuk antrian moderasi admin. |
| **User Tracking** | Data progress baca digital seorang user terhadap satu series: `current_chapter`, `status`, `score`, dan timestamp terkait. |
| **User Collection** | Data kepemilikan satu volume fisik oleh user, termasuk status kepemilikan (owned/missing/wishlist/preordered), kondisi, dan lokasi penyimpanan. |
| **Trashed Data** | Halaman khusus di admin panel yang menampilkan record dengan `deleted_at IS NOT NULL` menggunakan scope `onlyTrashed()`, dengan opsi restore. |
| **Trigram Index** | Index PostgreSQL (GIN, ekstensi `pg_trgm`) yang memungkinkan pencarian teks fuzzy/case-insensitive berdasarkan kemiripan substring tiga karakter. |
| **External IDs** | Kolom JSONB di tabel `series` yang menyimpan ID series tersebut di berbagai sumber eksternal, contoh `{"mangadex": "123", "anilist": 456}`. |
| **Sanctum** | Paket autentikasi resmi Laravel berbasis token, digunakan untuk mengamankan REST API MALAS. |
| **Zero-Downtime Deployment** | Strategi deploy (blue-green atau symlink release) yang memastikan aplikasi tetap dapat diakses selama proses deployment berlangsung. |
| **Smoke Test** | Pengujian otomatis ringan terhadap beberapa endpoint kritis (health, login, series) setelah deployment, untuk memastikan sistem berjalan normal sebelum dianggap sukses. |