# Software Requirements Specification (SRS)

## Lingkup Sistem

MALAS adalah sistem manajemen perpustakaan manga berbasis web untuk admin (super_admin). Sistem ini mencakup:
1. Manajemen katalog series dan volume fisik
2. Pencatatan kepemilikan koleksi per anggota
3. Manajemen peminjaman volume
4. Otomasi pengisian data via Jikan API
5. Manajemen akun pengguna

**Di luar lingkup (saat ini):**
- Halaman self-service user (tracking bacaan, lihat koleksi sendiri)
- Notifikasi email
- Mobile app

## Persyaratan Fungsional

### FR-01: Manajemen Series
- Sistem harus bisa menyimpan metadata series: judul (romaji/inggris/jepang), status publikasi, sinopsis, cover image, MAL ID, skor, rank
- Status publikasi: `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published`
- Cover image diupload dan disimpan di Cloudflare R2
- Series bisa di-soft-delete dengan alasan; data tetap ada di database
- Admin bisa batch-delete banyak series sekaligus

### FR-02: Manajemen Volume
- Setiap series memiliki satu atau lebih volume
- Data volume: nomor (mendukung desimal), judul, ISBN, penerbit, tanggal terbit, cover
- Cover volume juga disimpan di R2

### FR-03: Manajemen Koleksi
- Admin bisa menambah koleksi volume ke akun anggota
- Satu form bisa menambah volume dari banyak series sekaligus (bulk add)
- Per-entry (per manga): kondisi, harga beli, tanggal beli, catatan, flag `bisa dipinjamkan`
- Kondisi: `mint`, `very_good`, `good`, `fair`, `poor`
- Sistem harus exclude volume yang sudah dimiliki anggota dari pilihan

### FR-04: Manajemen Peminjaman
- Admin bisa mencatat peminjaman volume dari koleksi anggota ke pihak eksternal
- Data peminjaman: nama peminjam, kontak, tanggal pinjam, due date, catatan
- Status: `active`, `returned`, `overdue`, `lost`
- Admin bisa update status ke `returned` (isi tanggal kembali) atau `lost`

### FR-05: Manajemen User
- Admin bisa CRUD akun user
- Role: `user` (tidak punya akses admin), `super_admin` (akses penuh)
- Admin bisa ban/unban user dengan alasan
- Banned user tidak bisa login

### FR-06: Jikan Scraping
- Admin bisa mendaftarkan jadwal scraping otomatis (jam, menit, rentang tahun)
- Admin bisa mengurutkan jadwal via drag-and-drop
- Jadwal dieksekusi berurutan sesuai urutan yang ditetapkan admin
- Admin bisa trigger scrape manual dengan opsional filter rentang tahun
- Halaman admin menampilkan status scraping secara real-time (polling)

### FR-07: Activity Log
- Semua aksi create/update/delete oleh admin dicatat otomatis
- Catatan: siapa, apa, entitas mana, alasan, IP address, waktu

## Persyaratan Non-Fungsional

### NFR-01: Keamanan
- Semua halaman admin diproteksi autentikasi session + role check
- CSRF protection di semua form mutasi
- Input dari user di-escape sebelum dirender di HTML

### NFR-02: Performa
- DataTable menggunakan server-side pagination — tidak load semua record ke browser
- Cover image disajikan via R2 CDN, bukan dari server langsung
- Jikan scraping berjalan di background (queue) agar tidak blocking request

### NFR-03: Data Integrity
- Soft delete: data tidak benar-benar hilang
- Semua soft delete harus menyimpan siapa yang menghapus dan alasannya
- Volume yang sudah dimiliki user tidak bisa ditambahkan lagi ke koleksi yang sama

### NFR-04: Usability
- Response time halaman admin < 2 detik untuk data normal
- Autocomplete series search berjalan dari 2 karakter input
- Form bulk add mendukung unlimited entry (dalam satu sesi)

## Batasan Teknis

- Database: MySQL/MariaDB (bukan PostgreSQL)
- Queue: database driver (bukan Redis)
- Tidak ada realtime WebSocket — status polling via AJAX
- Storage: Cloudflare R2 (S3-compatible)
- Frontend: server-rendered Blade + Alpine.js (bukan SPA)
