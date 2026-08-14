# PRD — MALAS (Manga Library Admin System)

**Versi:** 2.6
**Tanggal:** 2026-06-26, diperbarui 2026-08-03
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
| `slug` | auto-generated dari `title_romaji`, dipakai untuk URL `/catalog/{slug}` (user) dan `/admin/series/{slug}` (admin) — user/admin tidak input manual |
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
- Browse list + filter (status, tipe, search judul, sudah/belum di koleksi, genre — searchable multi-select combobox, OR-match: series lolos kalau punya salah satu genre yang dipilih)
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

**Stepper progres baca (+/-):** alternatif cepat dari toggle ikon mata satu-satu, khusus buat kasus baca linear (baca berurutan dari volume 1 ke atas) — `+` menandai volume-belum-dibaca bernomor terendah jadi sudah dibaca, `-` membalik volume-sudah-dibaca bernomor tertinggi jadi belum dibaca. Cuma menggeser satu batas per klik, tidak menyentuh volume yang ditandai manual di luar urutan lewat ikon mata.

**Quick-edit jumlah volume per format (+/-):** alternatif cepat dari dialog "Tambah Volume" (yang tetap ada buat kasus non-sekuensial/gap), khusus kasus umum "nambah/kurang satu volume berurutan". `+` menambah volume bernomor berikutnya yang belum dimiliki sama sekali (nomor volume dibagi bersama lintas format dalam satu koleksi), `-` menghapus volume bernomor tertinggi dari format yang dipilih. Volume yang sedang dipinjamkan dilindungi — tombol `-` didisable + tooltip kalau volume tertinggi format itu lagi dipinjam, bukan diam-diam ganti target ke volume lain.

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
- **Batch import**: filter genre (dropdown, enum AniList) dan tahun rilis (range `startDate_greater`/`startDate_lesser` — AniList's `seasonYear` tidak berlaku untuk manga), toggle urutkan berdasarkan popularitas (`POPULARITY_DESC`); boleh browse cuma dari genre/tahun tanpa ketik judul apa pun
- Checkbox multi-select per hasil (cuma yang belum ada di katalog) + "Pilih Semua" + import sekaligus lewat satu request GraphQL (`id_in`) — bukan N request terpisah per series, supaya hemat kuota rate-limit AniList (~90 req/menit)
- Toggle "Sembunyikan yang sudah ada di katalog" — filter client-side, dikombinasikan dengan badge "Sudah diimpor" yang sudah ada

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

### F-16 — Wishlist *(User)*

- User bisa tandai series yang ingin dibaca tapi belum dikoleksi (`wishlist_items`, unique per user+series)
- Berbeda dari Koleksi — tidak ada tracking volume/format, murni penanda minat
- Akses: `/wishlist` (index), tambah dari halaman Katalog, hapus dengan Undo (`restore`)

### F-17 — Profil Publik & Follow *(User, opt-in)*

- User bisa opt-in tampilkan profil publik (`users.is_profile_public`, default false) via toggle di Settings
- Profil publik (`/u/{username-atau-id}`) **bisa diakses tanpa login** — guest bisa lihat koleksi user (cover + judul, tidak bisa diklik ke detail katalog karena itu butuh login), tapi tidak bisa follow atau lihat menu internal
- User yang sudah login bisa follow/unfollow user lain dari profil publik atau Direktori Pengguna (`/directory`, butuh login)
- Direktori Pengguna: daftar user dengan `is_profile_public = true`, cari & lihat profil orang lain
- Route model binding profil: coba match `username` dulu, fallback ke `id`

### F-18 — Selera Genre (AI Funfact) *(User)*

- Dashboard user menampilkan word cloud genre dari koleksi + "funfact" singkat hasil generate AI berdasarkan pola koleksi user
- Provider AI dikonfigurasi admin di `/admin/settings` tab AI: **Puter.js** (default — client-side, jalan di browser user, gratis, tanpa API key server) atau Gemini/OpenAI/Claude (server-side, butuh `api_key` tersimpan ter-encrypt di `ai_settings`)
- Auto-generate ulang saat koleksi user bertambah signifikan; generate manual dibatasi kuota (`GenreFunfact::DEFAULT_MANUAL_QUOTA` = 5×/minggu), admin bisa override kuota per user di `/admin/funfact-quota`
- Rate limit (HTTP 429) dari provider server-side ditangani khusus (`AiRateLimitException`) — funfact jatuh ke fallback text, tidak memotong kuota generate-ulang manual user
- Log aktivitas mencatat kategori `ai` untuk aksi generate/error funfact

### F-19 — Import Metadata Light Novel (RanobeDB) *(Admin)*

- Cari & import metadata light novel dari [RanobeDB](https://ranobedb.org) REST API (tanpa auth), paralel dengan AniList — sisi manga/manhwa/manhua tetap pakai AniList, RanobeDB khusus light novel
- Staff di-split native jadi `authors` dan `illustrators` (kolom terpisah), lebih detail dari AniList yang menggabungkan semua staff
- Sentinel tanggal `99999999` dari RanobeDB (artinya ongoing) ditangani eksplisit, tidak di-parse sebagai tanggal literal
- Sync ulang metadata ke series yang sudah ada (Popover "Sync RanobeDB" di Edit Series, sama pola dengan "Sync AniList")
- Import duplikat (`ranobedb_id` sama) update series yang ada, bukan bikin baru
- Detail lengkap: [`docs/RANOBEDB_INTEGRATION.md`](RANOBEDB_INTEGRATION.md)

### F-20 — Multi-Bahasa (id/en/ja)

- Seluruh UI mendukung Bahasa Indonesia (default), Inggris, dan Jepang — preferensi disimpan per-user (`users.locale`)
- Ganti bahasa dari kartu "Bahasa" di Settings atau tombol quick-switch di sidebar footer (tidak perlu buka Settings)
- Pesan validasi & paginator Laravel bawaan ikut bahasa aktif user (`lang/{id,en,ja}/validation.php`, `pagination.php`)
- **Wajib untuk semua fitur baru** — setiap teks user-facing baru harus langsung disiapkan terjemahannya di ketiga bahasa, tidak boleh ditunda. Seluruh halaman `User/**` dan `Admin/**` sudah diterjemahkan penuh; lihat [`CLAUDE.md`](../CLAUDE.md) untuk cakupan lengkap dan gap yang masih terdokumentasi (flash message controller)
- Flash message dari controller (`->with('success', '...')`) belum masuk sistem terjemahan terpusat — backlog terpisah, dicatat di CLAUDE.md

### F-21 — Reorder Menu Sidebar *(Admin)*

- Admin bisa drag-and-drop urutan menu di `/admin/menus` (`SortableMenuList.tsx`, `@dnd-kit`), langsung update `menus.sort_order` via `PATCH /admin/menus/reorder`
- Preview sidebar user tersedia di `/admin/menus/user` untuk melihat hasil susunan tanpa harus login sebagai user biasa

### F-22 — Login dengan Email (Magic Link)

- Opsi login setara SSO, dipilih dari modal "Masuk ke MALAS" (`LoginMethodDialog.tsx`) yang muncul begitu tombol Login di Landing page diklik — **bukan** cuma link kecil tersembunyi buat kondisi darurat lagi (awalnya dibangun sebagai fallback SSO-down, dipromosikan jadi opsi harian setelah mekanismenya terbukti aman)
- Verifikasi identitas lewat kepemilikan inbox email yang sudah tersinkron dari SSO — **bukan** password lokal (tidak ada yang disimpan) dan **bukan** approval admin
- Token (`sso_fallback_tokens`) tersimpan ter-hash, TTL 15 menit, single-use
- Rate limit 5x/10 menit per endpoint request (dinaikkan dari 3x/15 menit setelah dipromosikan jadi opsi harian); response selalu pesan generik yang sama (anti email-enumeration, tidak membocorkan status akun)
- Butuh provider email terkonfigurasi (Resend, F-23) — kalau belum dikonfigurasi, fitur ini diam-diam tidak mengirim apa pun (tidak error ke user)
- **Trade-off yang disengaja**: profil (nama/avatar/username) cuma ikut ke-sync ulang dari whitearchive.id pas login lewat SSO. User yang seterusnya selalu login lewat email tidak dapat update profil otomatis — didiskusikan dan disetujui, bukan bug
- Halaman mandiri `/auth/fallback` tetap tersedia sebagai direct link (dipakai juga oleh CLI `sso:emergency-login`, lihat F-24)

### F-23 — Konfigurasi Email (Resend) *(Admin, super_admin only)*

- Provider email (Resend) dikonfigurasi dari `/admin/settings` tab Email — `api_key` ter-encrypt, sama pola dengan Storage/AI, bukan `.env`
- Dipakai untuk fitur Login dengan Email (F-22)

### F-24 — Login Darurat via CLI *(Operator dengan akses SSH server)*

- `php artisan sso:emergency-login {identifier=super_admin}` — reuse token magic link yang sama dengan F-22, tapi diterbitkan dari terminal, bukan dikirim lewat email
- Identifier boleh role (`super_admin`/`admin`/`user`) atau email/username spesifik; kalau ada beberapa user dengan role yang sama, command kasih pilihan interaktif
- Selalu minta konfirmasi dan tampilkan siapa yang bakal dikasih akses sebelum menerbitkan link
- Berguna kalau mail service belum sempat dikonfigurasi (F-23), atau butuh akses instan tanpa nunggu email — satu-satunya syarat cuma akses SSH ke server, setara akses langsung ke database

### F-25 — Tema Light/Dark/System

- 3 opsi tema eksplisit (bukan cuma toggle 2-state) — preferensi disimpan per-user (`users.theme`, default `system`)
- Ganti tema dari kartu "Tema" di Settings atau `ThemeSwitcher` (Popover) di sidebar footer/Landing page — pola sama persis dengan ganti bahasa (F-20)
- Opsi `system` resolve otomatis dari preferensi OS (`prefers-color-scheme`) dan live-update kalau preferensi OS berubah selagi aplikasi terbuka
- Guest-safe — tema tersimpan di localStorage kalau belum login, sync ke DB begitu login

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
| Wishlist | ✓ | ✓ | ✓ |
| Profil Publik & Follow | ✓ | ✓ | ✓ |
| Direktori Pengguna | ✓ | ✓ | ✓ |
| Selera Genre (AI Funfact): lihat & generate | ✓ | ✓ | ✓ |
| Kuota AI Funfact: kelola | ✓ | ✓ | — |
| Konfigurasi Provider AI | ✓ | — | — |
| RanobeDB Import | ✓ | ✓ | — |
| Reorder Menu Sidebar | ✓ | ✓ | — |
| Ganti Bahasa (id/en/ja) | ✓ | ✓ | ✓ |
| Login dengan Email (Magic Link) | ✓ | ✓ | ✓ |
| Konfigurasi Email (Resend) | ✓ | — | — |
| Login Darurat via CLI | ✓ (butuh akses SSH) | ✓ (butuh akses SSH) | — |

---

## 6. Out of Scope

- Aplikasi mobile native
- Marketplace / jual beli
- Integrasi payment
- Scraping selain AniList/RanobeDB
- Activity feed di profil publik (gaya Steam) — profil publik + follow sudah dibangun (F-17), activity feed-nya masih ditunda
- Badge/label selera genre ("Genre Explorer" vs "Genre Loyalist") berdasar distribusi genre koleksi — ditunda, numpang di data yang sama dengan fitur Selera Genre (F-18)
