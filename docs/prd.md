# PRD — MALAS (Manga Library Admin System)

**Versi:** 2.2
**Tanggal:** 2026-06-26, diperbarui 2026-07-26
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
- Login via SSO whitearchive.id (PKCE-based OAuth2) — tidak ada form register/login lokal
- Semua akun (termasuk admin) dikelola di sisi SSO; MALAS hanya menyimpan `sso_id`, `name`, `username`, `email`, `avatar` dari klaim SSO
- Profil ditampilkan read-only di `/settings` (edit profil dilakukan di sisi SSO)
- Session management via Laravel session standar setelah callback SSO sukses

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
| `cover_path` | upload manual atau URL dari AniList — diakses lewat `StorageSettingsService` (Local/S3-compatible, dikonfigurasi via UI admin) |
| `synopsis` | text, nullable |
| `score` | decimal 0–10, nullable |
| `total_volumes` | int, nullable |
| `anilist_id` | unique, nullable |
| `genres`, `authors`, `themes`, `demographics` | json, nullable — dari AniList |
| `is_adult` | boolean — dipakai untuk blur konten 18+ (opt-in per instalasi, tab Konten di Pengaturan) |
| `published_from` / `published_to` | date range, nullable |

User akses:
- Browse list + filter (status, tipe, search judul, sudah/belum di koleksi)
- Lihat detail: sinopsis, genre/theme/demographic lengkap, daftar volume, galeri media tambahan, avatar (tanpa nama) + jumlah user lain yang mengoleksi series ini
- Tombol "Tambah ke Koleksi" dari halaman detail
- Cari cepat lewat Global Search (⌘K) dari halaman manapun

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
| `personal_rating` | smallint, nullable, -10 s/d 10 (gaya MyAnimeList — negatif = tidak direkomendasikan) |
| `personal_review` | text, nullable — komentar pribadi user tentang series ini |

Di dalam koleksi, user mencatat volume yang dimiliki via `collection_volumes`. User input nomor volume sebagai CSV (misal: `1,2,3,5`) dan memilih format per batch. Volume tidak terikat ke tabel `volumes` admin — user yang tentukan sendiri nomor apa yang mereka punya.

**Format volume:** `physical` / `ebook` / `online` / `webtoon`

**Tracking baca:** tiap `collection_volume` punya `read_at` (nullable) — user toggle baca/belum lewat icon mata per volume (volume yang sudah dibaca ditampilkan greyed out), atau tandai semua sekaligus lewat satu tombol. Datatable koleksi menampilkan progres baca (`N/M dibaca`) dan "Terakhir dibaca: Vol. N" dihitung otomatis dari volume bernomor tertinggi yang sudah dibaca.

**Mode hapus volume:** toolbar volume punya toggle "Hapus" yang mengubah icon mata di tiap volume jadi checkbox (posisi sama) untuk seleksi bulk-delete, supaya tidak konflik dengan aksi tandai-baca.

**Cara tambah series:** dari halaman `/my-collection` via dialog search + multi-select. Bisa tambah lebih dari satu series sekaligus.

Akses:
- User: hanya bisa lihat & edit koleksi sendiri
- Admin: bisa lihat semua koleksi semua user (dikelompokkan per user), beserta detail kepemilikan per volume

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

### F-07 — AniList API Integration *(Admin)*

- Cari manga/manhwa/manhua/novel di AniList via GraphQL API
- Preview data sebelum import (card overlay, bukan popover/modal terpisah)
- Import otomatis isi semua field series termasuk genre/author/theme/demographic
- Jika AniList ID sudah ada → tampil info + tombol lihat, bukan duplicate
- Sync ulang metadata ke series yang sudah ada (Popover "Sync AniList" di Edit Series)
- Filter sembunyikan konten 18+ saat mencari, badge 18+ di hasil

### F-08 — Announcements

- Admin buat: title, body (markdown), type (info/warning/danger/success), aktif, tanggal mulai-selesai
- User: lihat di dashboard, bisa dismiss per pengumuman
- Expired → otomatis tidak tampil

### F-09 — User Management *(Admin)*

- List, view profil, ban/unban, ganti role
- Admin tidak bisa upgrade user ke/dari `super_admin`
- Reset password tidak berlaku — password dikelola di sisi SSO, bukan di MALAS

### F-10 — Dashboard

**Admin:** stat cards + chart (Recharts): Series per Status, Koleksi per Tipe, Status Pinjaman
**User:** stat cards, chart Koleksi per Status, Carousel rekomendasi (F-11), widget tiket terakhir

### F-11 — Rekomendasi & Surprise Me *(User)*

- Dashboard user nampilkan rekomendasi series berdasarkan overlap genre dengan koleksi user, dihitung di PHP (bukan raw JSON query DB) supaya portabel antara SQLite (dev) dan MySQL (prod)
- Fallback ke pilihan random dari series yang belum dikoleksi kalau scoring genre tidak menghasilkan kandidat (user baru, atau sisa katalog belum punya data genre)
- Tiap rekomendasi tampil dalam Carousel: cover, judul, author, genre/tags, sinopsis singkat
- Tombol "Surprise Me" — pilih satu series random (genre-weighted, fallback random murni) dengan dialog reveal

### F-12 — Global Search & Command Palette

- **User side:** search bar di header (desktop) / icon search (mobile), atau ⌘K/Ctrl+K dari halaman manapun — cari judul di Katalog + Koleksiku sendiri, atau navigasi cepat ke halaman lain (fuzzy-match, misal ketik "pinjaman" langsung muncul menu terkait)
- **Admin side:** Command Palette (⌘K/Ctrl+K) — navigasi cepat ke semua halaman admin + search Series/Users/Tiket

### F-13 — Storage & Database Backup *(Admin, super_admin only)*

- Konfigurasi driver storage (`local` / `s3`-compatible seperti Cloudflare R2) langsung dari UI admin (`/admin/settings`, tab Storage), bukan `.env`
- Semua operasi file (cover series/volume, media tambahan) lewat `StorageSettingsService`
- Migrasi file otomatis (Local ↔ S3) saat driver diganti, berjalan di background lewat queue job
- Download/import dump SQL database dari UI (tab Database), exclude tabel sensitif

### F-14 — Sistem Tiket

- User buat tiket request (misal minta judul baru masuk katalog) dari halaman Tiket atau pre-filled dari Katalog
- Admin merespon dari halaman detail tiket admin
- Status: `open`, `in_progress`, `resolved`, `closed`

### F-15 — Undo pada Aksi Reversible

- Toast notifikasi (sonner) bisa menampilkan tombol "Undo" untuk aksi yang reversible, didorong dari flash session (`undo_url` + `undo_payload`)
- Contoh: tandai baca per-volume, tandai-semua-baca (undo hanya revert volume yang baru diubah aksi tersebut, bukan semua)

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
| Koleksi: milik sendiri (termasuk tracking baca, review & rating) | ✓ | ✓ | ✓ |
| Loans: semua | ✓ | ✓ | — |
| Loans: milik sendiri | ✓ | ✓ | ✓ |
| AniList Import | ✓ | ✓ | — |
| Announcements: CRUD | ✓ | ✓ | — |
| Announcements: lihat | ✓ | ✓ | ✓ |
| Tiket: respond | ✓ | ✓ | — |
| Tiket: buat & lihat milik sendiri | ✓ | ✓ | ✓ |
| Log Aktivitas | ✓ | ✓ | — |
| Storage & Database Backup | ✓ | — | — |
| Global Search / Command Palette | ✓ | ✓ | ✓ |

---

## 6. Out of Scope

- Aplikasi mobile native
- Marketplace / jual beli
- Integrasi payment
- Scraping selain AniList
- Profil publik + sistem follow + activity feed (gaya Steam) — sengaja ditunda, lihat backlog di [`PHASES.md`](PHASES.md)
