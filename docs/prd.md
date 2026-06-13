# Product Requirements Document (PRD)

## Ringkasan

MALAS (Manga Library System) adalah platform untuk administrator perpustakaan manga/manhwa yang ingin:
1. Mengelola inventaris koleksi fisik (volume per series)
2. Melacak kepemilikan volume oleh anggota
3. Mengelola peminjaman volume antar anggota
4. Mengisi data series dari MyAnimeList secara otomatis (via Jikan API)

## Target Pengguna

| Role | Akses | Kebutuhan Utama |
|---|---|---|
| **Super Admin** | Panel `/admin` penuh | CRUD semua data, manajemen jadwal scraping, ban user |
| **User** | (belum diimplementasi) | Lihat koleksi sendiri, lacak bacaan |

## Fitur yang Sudah Ada (MVP Admin)

### Series Management
- CRUD series: judul, status publikasi, sinopsis, cover (upload ke R2), MAL ID
- AJAX DataTable dengan search dan filter status
- Batch delete dengan konfirmasi dan alasan
- Soft delete (data tetap ada, bisa dilihat di DataTable)

### Volume Management
- CRUD volume per series: nomor, judul, ISBN, penerbit, tanggal terbit, cover
- Cover upload ke R2

### Collection Management
- Tambah koleksi bulk: satu form untuk banyak series sekaligus
  - Pilih anggota (TomSelect)
  - Tambah N manga entry dengan series search AJAX
  - Per-entry: pilih volume (dengan filter volume yang sudah dimiliki), kondisi, harga, tanggal, catatan, bisa-dipinjam
- CRUD individual collection
- Lihat daftar koleksi semua anggota

### Loan Management
- Buat peminjaman: pilih anggota, volume dari koleksinya, peminjam eksternal, due date
- Update status: active → returned / lost
- Lihat riwayat peminjaman

### User Management
- CRUD user: nama, email, role, status
- Ban/unban user dengan alasan
- AJAX DataTable dengan filter role dan status

### Jikan Scraping
- Multi-schedule: buat banyak jadwal scraping dengan jam/menit berbeda
- Setiap jadwal bisa punya rentang tahun (start_year/end_year) untuk filter publikasi
- Reorder jadwal via drag-and-drop
- Scrape Now: trigger manual
- Antrian otomatis: jadwal dieksekusi berurutan sesuai sort_order
- Status polling real-time di halaman admin

### Activity Log
- Semua aksi admin otomatis dicatat (create, update, delete)
- Bisa difilter dan dilihat di `/admin/activity-log`

## Fitur Belum Ada (Backlog)

- Halaman user (non-admin): lihat koleksi sendiri, request pinjam
- Tracking bacaan digital (chapter progress per series)
- Notifikasi email untuk peminjaman jatuh tempo
- Import koleksi via CSV
- Restore data yang sudah di-soft-delete dari panel admin
- Mobile view yang dioptimasi

## Batasan Sistem Saat Ini

- Hanya berjalan di lingkungan lokal (XAMPP) — belum ada deployment production
- Database: MySQL/MariaDB (bukan PostgreSQL seperti spesifikasi awal)
- Queue: database driver (bukan Redis/Horizon)
- Admin panel custom (bukan Filament)
- Tidak ada Redis, tidak ada caching (kecuali file/array cache default)
