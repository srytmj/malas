<div align="center">
  <img src="public/images/favicon/favicon-512.png" alt="Logo Malas" width="96" height="96">

  # Malas
  ### Manga Library Admin System

  Aplikasi pengelolaan koleksi pribadi manga / manhwa / manhua / light novel.

  [![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![React](https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black)](https://react.dev)
  [![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?style=flat-square&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
  [![Inertia.js](https://img.shields.io/badge/Inertia.js-v2-9553E9?style=flat-square)](https://inertiajs.com)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
  [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](#lisensi)

  **Bahasa:** [English](README.md) · Bahasa Indonesia (kamu di sini)
</div>

---

> Dibuat untuk kolektor yang perlu melacak volume mana yang dimiliki, kondisinya, dan siapa yang sedang meminjam — tanpa spreadsheet manual, tanpa langganan bulanan, dan datanya tetap punya kamu sendiri.

## Fitur Utama

<table>
<tr><td width="50%" valign="top">

**Koleksi & Katalog**
- Katalog + koleksi pribadi, input volume dengan syntax range (`1,2,5-9,11,12`)
- Tracking baca per volume — tandai sudah/belum, progres otomatis di datatable
- Stepper cepat progres-baca & jumlah volume per format — bisa langsung dari daftar koleksi, nggak perlu buka halaman detail
- Grup koleksi custom ala MDList/MangaDex — bikin daftar bernama (mis. "RomCom"), isi dengan manga dari koleksi, satu manga bisa masuk beberapa grup; grup bisa diset publik (muncul di profilmu) atau privat
- Review & rating pribadi (-10 s/d +10, gaya MyAnimeList)
- Wishlist — series yang ingin dibaca tapi belum dikoleksi
- Peminjaman volume — siapa pinjam, jatuh tempo, status terlambat otomatis
- Badge "volume kurang" — nandain gap dari total volume yang diketahui series-nya, klik langsung buka form "Tambah Volume" yang udah keisi
- Export/import koleksi sebagai backup JSON pribadi (termasuk progres baca, review, rating)

**Import & Data**
- Import metadata dari [AniList](https://anilist.co) (GraphQL) — judul, sinopsis, genre, author, skor
- Import light novel dari RanobeDB — author/illustrator ter-split native
- Batch import: filter genre + tahun + sort popularitas, multi-select sekaligus
- Filter genre searchable multi-select di halaman Katalog (OR-match)

</td><td width="50%" valign="top">

**Pengalaman Pengguna**
- Rekomendasi & "Surprise Me" berbasis overlap genre koleksi
- Global search / Command Palette (Cmd/Ctrl+K) — navigasi instan
- Dashboard dengan chart (Recharts), bukan cuma angka
- Word cloud "Selera Genre" + funfact AI (BYO API key Gemini/OpenAI/Claude, dikonfigurasi admin)
- Profil publik opt-in + follow + direktori pengguna
- Multi-account switching — sambungin & switch cepat antar akun di sesi browser yang sama
- Undo di toast untuk aksi reversible
- Tema Light/Dark/System, tiga bahasa (id/en/ja) — UI, pesan validasi, *dan* flash message controller semuanya sudah full diterjemahkan

**Admin & Infrastruktur**
- Login SSO (PKCE OAuth2) *atau* magic link email — dua jalur setara
- Storage fleksibel (Local / S3-compatible) dari UI, tanpa sentuh `.env`
- Backup & restore database dari UI admin
- Role `super_admin` > `admin` > `user`, menu dinamis drag-and-drop

</td></tr>
</table>

Detail lengkap tiap fitur ada di [`CLAUDE.md`](CLAUDE.md) bagian "Fitur yang Sudah Ada".

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| Frontend UI | React 19 + TypeScript 5 |
| Komponen UI | shadcn/ui (berbasis Base UI) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database | SQLite (dev) / MySQL 8+ (prod) |
| Auth/Role | Spatie Laravel Permission |
| Auth SSO | whitearchive.id (PKCE OAuth2) |
| API eksternal | AniList GraphQL, RanobeDB REST |
| AI | Gemini/OpenAI/Claude, API key dikonfigurasi via UI admin |
| Email | Resend (dikonfigurasi via UI admin, bukan `.env`) |
| Multi-bahasa | react-i18next (id/en/ja) |
| Drag & drop | @dnd-kit |

Detail arsitektur lengkap: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

### Struktur Role

```
super_admin > admin > user
```

Akses dikontrol dua lapis: Spatie Role (resource level) + `CheckMenuAccess` middleware (route level).

---

## Setup Lokal (Development)

Prasyarat: **PHP 8.2+**, **Composer**, **Node.js 20+**, **npm**.

```bash
git clone <repo-url> malas
cd malas
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run dev
```

Jalankan server Laravel di terminal terpisah:

```bash
php artisan serve
```

Buka `http://localhost:8000`.

### Login SSO saat development

Login memakai SSO whitearchive.id — daftarkan aplikasi di `sso.whitearchive.id/dashboard/applications` untuk dapat `SSO_CLIENT_ID` dan `SSO_CLIENT_SECRET`, lalu isi di `.env`:

```env
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI=http://localhost:8000/auth/callback
```

Tidak mau setup SSO dulu? Pakai jalur darurat CLI (lihat [Troubleshooting](#troubleshooting)) untuk langsung masuk sebagai `super_admin`.

### Storage saat development

Default driver `local` langsung jalan tanpa konfigurasi tambahan. Untuk switch ke S3-compatible (Cloudflare R2, dll), buka `/admin/settings/storage` setelah login sebagai `super_admin` — tidak perlu edit `.env`.

---

## Deployment ke Production

Tiga metode tersedia:

1. **Otomatis, native (Ubuntu bare-metal/VPS)** — `bash deploy/deploy.sh`, install PHP/MySQL/Nginx/Node/Supervisor langsung di host.
2. **Manual step-by-step** — hasil sama dengan metode 1, kontrol penuh.
3. **Docker (berbasis Postgres)** — `bash deploy/deploy-docker.sh`, stack Docker Compose generik (app/nginx/Postgres/queue), jalan di Linux manapun yang ada Docker, termasuk LXC/VM Proxmox. Lihat **[`docs/DOCKER.md`](docs/DOCKER.md)**.

Panduan lengkap metode 1–2, termasuk setup AWS EC2, Azure VM, dan Local Server (VPS/bare metal): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)**.

Update kode di server yang sudah live:

```bash
bash deploy/update.sh        # deploy native
bash deploy/update-docker.sh # deploy Docker
```

Script ini melakukan `git pull`, rebuild dependencies/frontend hanya jika perlu, jalankan migration baru, dan rebuild cache — tanpa menyentuh data yang sudah ada.

---

## Testing

```bash
php artisan test
npx tsc --noEmit
```

---

## Troubleshooting

**Nggak bisa login sama sekali / whitearchive.id (SSO) down?**

```bash
php artisan sso:emergency-login super_admin
```

Terbitkan link login sekali-pakai langsung dari CLI — nggak perlu nunggu SSO pulih atau email masuk. Argumen boleh role (`super_admin`/`admin`/`user`) atau email/username spesifik (mis. `php artisan sso:emergency-login admin@domainmu.com`). Command bakal nanya konfirmasi dulu sebelum nerbitin link, dan kalau ada beberapa user dengan role yang sama, kasih pilihan interaktif.

Selain itu, user (bukan cuma admin) juga bisa minta magic link login sekali-pakai sendiri lewat email — klik "Login" di halaman utama → pilih "Login dengan Email". Ini butuh provider Email (Resend) sudah dikonfigurasi di `/admin/settings` tab Email.

Panduan troubleshooting lebih lengkap (Nginx 502, migration gagal, storage permission, dll): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#troubleshooting)**.

---

## Belum Selesai (Backlog)

Jujur soal apa yang belum ada, biar nggak ada yang kaget:

- **Advanced filter batch import AniList** — multi-select genre, filter tag (`tag_in`), filter status (`status_in`). Sudah diverifikasi jalan di API AniList, belum di-implementasikan di UI.
- **Activity feed di profil publik** (gaya Steam) — profil publik + follow sudah ada, feed aktivitasnya belum.
- **Badge/label "Selera Genre"** ("Genre Explorer" vs "Genre Loyalist") — ditunda, numpang di data yang sama dengan fitur word cloud.
- **Verifikasi visual manual modal `LoginMethodDialog`** — kode sudah `tsc`-clean dan direview, tapi belum pernah di-klik langsung di browser sungguhan (lihat `docs/PHASES.md` Phase 18 buat detail kenapa).

Lihat [`CLAUDE.md`](CLAUDE.md) bagian "Belum dikerjakan (backlog)" dan [`docs/PHASES.md`](docs/PHASES.md) untuk histori & konteks lengkap tiap item.

---

## Dokumentasi

| Dokumen | Isi |
|---------|-----|
| [`CLAUDE.md`](CLAUDE.md) | Aturan coding, struktur folder, konvensi wajib untuk kontribusi |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Skema database, folder structure, request lifecycle, authorization flow |
| [`docs/PHASES.md`](docs/PHASES.md) | Log fase pengembangan dari awal sampai sekarang |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Tutorial deploy & update — otomatis maupun manual |
| [`docs/prd.md`](docs/prd.md) | Product requirements — latar belakang, persona, spesifikasi fitur |
| [`docs/RANOBEDB_INTEGRATION.md`](docs/RANOBEDB_INTEGRATION.md) | Riset API + rencana development import light novel dari RanobeDB |
| [`CHANGELOG.md`](CHANGELOG.md) | Histori perubahan penting per tanggal |

## Lisensi

MIT

<div align="right"><a href="#malas">Kembali ke atas</a></div>
