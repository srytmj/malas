# PRD — MALAS (Manga Library Admin System)

**Versi:** 2.0 — Rebuild  
**Tanggal:** 2026-06-26  
**Status:** Active

---

## 1. Latar Belakang

Kolektor manga fisik kesulitan melacak koleksi — volume mana yang dimiliki, kondisinya, dan siapa yang sedang meminjamnya. Pengelolaan manual via spreadsheet tidak skalabel dan tidak bisa diakses real-time.

MALAS v2 adalah rebuild total dengan stack baru (React + Inertia) untuk UI yang lebih modern (terinspirasi MangaDex) dan lebih mudah di-maintain jangka panjang.

**Perubahan dari v1:** Filament dihapus → diganti React 19 + Inertia.js v2 + shadcn/ui.

---

## 2. User Personas

### Admin / Pemilik Koleksi
> "Aku mau tahu persis di mana setiap volume-ku berada, dan siapa yang lagi pegang."

- Role: `admin` atau `super_admin`
- Koleksi 200+ volume, sering dipinjam teman
- Goal: input cepat, lacak peminjaman, tidak ada yang hilang

### User / Teman Peminjam
> "Aku mau lihat koleksi yang ada dan cek status pinjaman saya."

- Role: `user`
- Kadang pinjam manga, tidak rutin
- Goal: browse katalog, kelola koleksi sendiri, catat peminjaman

---

## 3. Roles

| Role | Deskripsi |
|------|-----------|
| `super_admin` | Akses penuh. Tidak bisa di-ban. Bisa manage role. |
| `admin` | Akses penuh kecuali manage `super_admin` dan role management. |
| `user` | Browse katalog (read-only), kelola koleksi & pinjaman milik sendiri. |

---

## 4. Fitur

### F-01 — Autentikasi
- Login email + password
- Lupa password via email
- Email verification (toggle di settings)
- Session management

### F-02 — Menu Management *(Admin)*

Admin mengontrol menu apa yang tampil dan statusnya:

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `label` | string | Nama tampil di sidebar |
| `route_name` | string | Laravel route name |
| `icon` | string | Nama icon Lucide |
| `sort_order` | int | Urutan di sidebar |
| `is_visible` | bool | Tampil/tidak di sidebar user |
| `is_maintenance` | bool | Mode maintenance aktif/tidak |
| `maintenance_message` | text | Pesan custom (ada default) |
| `role_access` | json array | Role yang bisa akses (`["admin","user"]`) |

**Maintenance mode behavior:**
- User akses route yang maintenance → redirect ke halaman maintenance dengan pesan
- `admin` & `super_admin` tidak terblokir oleh maintenance mode
- Badge "maintenance" tetap tampil di sidebar admin sebagai indikator

### F-03 — Katalog Series *(Admin CRUD / User read-only)*

| Field | Keterangan |
|-------|-----------|
| `title_romaji` | required |
| `title_english`, `title_japanese` | nullable |
| `status` | `publishing` / `finished` / `on_hiatus` / `discontinued` / `not_yet_published` |
| `type` | `manga` / `manhwa` / `manhua` / `novel` / `one_shot` / `doujinshi` |
| `cover_path` | upload ke R2 atau URL dari Jikan |
| `synopsis` | text, nullable |
| `score` | decimal 0–10, nullable |
| `total_volumes` | int, nullable |
| `mal_id` | unique, nullable |
| `published_from` / `published_to` | date range, nullable |

User akses:
- Browse list + filter (status, tipe, search judul)
- Lihat detail + daftar volume
- Tombol "Tambah ke Koleksi" dari halaman detail

### F-04 — Katalog Volume *(Admin CRUD / User read-only)*

| Field | Keterangan |
|-------|-----------|
| `volume_number` | int, required |
| `cover_path` | nullable, override series cover |
| `type` | `regular` / `digital` / `bind_up` |
| `digital_source` | nullable (`mangaplus`, `k_manga`, `viz`, `comicwalker`, dll.) |
| `isbn` | nullable |
| `published_at` | date, nullable |

### F-05 — Koleksi User

Satu user punya satu koleksi per series.

| Field | Keterangan |
|-------|-----------|
| `user_id` | FK |
| `series_id` | FK |
| `acquired_at` | date, nullable |
| `notes` | text, nullable |

Di dalam koleksi, user mencatat volume yang dimiliki via `collection_volumes`. User input nomor volume sebagai CSV (misal: `1,2,3,5`) dan memilih format per batch. Volume tidak terikat ke tabel `volumes` admin — user yang tentukan sendiri nomor apa yang mereka punya.

**Format volume:** `physical` / `ebook` / `online` / `webtoon`

**Cara tambah series:** dari halaman `/my-collection` via dialog search + multi-select. Bisa tambah lebih dari satu series sekaligus.

Akses:
- User: hanya bisa lihat & edit koleksi sendiri
- Admin: bisa lihat semua koleksi semua user beserta detail kepemilikan per volume

### F-06 — Loans (Peminjaman)

User mencatat volume yang dipinjamkan dari koleksinya:

| Field | Keterangan |
|-------|-----------|
| `collection_id` | FK ke koleksi owner |
| `collection_volume_id` | FK ke `collection_volumes` — volume user yang dipinjam |
| `borrower_name` | string (tidak harus user terdaftar) |
| `loaned_at` | date, required |
| `due_at` | date, nullable |
| `returned_at` | date, nullable — jika diisi → status = dikembalikan |
| `notes` | text, nullable |

### F-07 — Jikan API Integration *(Admin)*

- Cari manga di MyAnimeList via Jikan v4 API
- Preview data sebelum import
- Import otomatis isi semua field series
- Jika MAL ID sudah ada → update, bukan duplicate
- Rate limit: max 3 req/detik, retry dengan exponential backoff

### F-08 — Announcements

- Admin buat: title, body (markdown), type (info/warning/danger/success), aktif, tanggal mulai-selesai
- User: lihat di dashboard, bisa dismiss per pengumuman
- Expired → otomatis tidak tampil

### F-09 — User Management *(Admin)*

- List, view profil, ban/unban, ganti role, reset password
- Admin tidak bisa upgrade user ke/dari `super_admin`

### F-10 — Dashboard

**Admin:** stats sistem, grafik series by status, tabel series terbaru, announcements  
**User:** stats koleksi sendiri, grafik kondisi koleksi, koleksi terbaru diupdate, announcements

---

## 5. Access Matrix

| Fitur | super_admin | admin | user |
|-------|:-----------:|:-----:|:----:|
| Dashboard | ✓ | ✓ | ✓ |
| Menu Management | ✓ | ✓ | — |
| User Management | ✓ | ✓ | — |
| Role Management | ✓ | — | — |
| Series: lihat | ✓ | ✓ | ✓ |
| Series: CRUD | ✓ | ✓ | — |
| Volume: lihat | ✓ | ✓ | ✓ |
| Volume: CRUD | ✓ | ✓ | — |
| Koleksi: semua user | ✓ | ✓ | — |
| Koleksi: milik sendiri | ✓ | ✓ | ✓ |
| Loans: semua | ✓ | ✓ | — |
| Loans: milik sendiri | ✓ | ✓ | ✓ |
| Jikan Scraper | ✓ | ✓ | — |
| Announcements: CRUD | ✓ | ✓ | — |
| Announcements: lihat | ✓ | ✓ | ✓ |
| Settings | ✓ | ✓ | — |

---

## 6. Out of Scope

- Aplikasi mobile native
- Marketplace / jual beli
- Integrasi payment
- Rating / review oleh user
- Scraping selain MyAnimeList
