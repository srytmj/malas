<div align="center">
  <img src="public/images/favicon/favicon-512.png" alt="Malas logo" width="96" height="96">

  # Malas
  ### Manga Library Admin System

  Aplikasi pengelolaan koleksi pribadi manga / manhwa / manhua / light novel.<br/>
  *A self-hosted library manager for your personal manga, manhwa, manhua & light novel collection.*

  [![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![React](https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black)](https://react.dev)
  [![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?style=flat-square&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
  [![Inertia.js](https://img.shields.io/badge/Inertia.js-v2-9553E9?style=flat-square)](https://inertiajs.com)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
  [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](#lisensi--license)

  **Bahasa:** [🇮🇩 Indonesia](#-indonesia) · [🇬🇧 English](#-english)
</div>

---

# 🇮🇩 Indonesia

> Dibuat untuk kolektor yang perlu melacak volume mana yang dimiliki, kondisinya, dan siapa yang sedang meminjam — tanpa spreadsheet manual, tanpa langganan bulanan, dan datanya tetap punya kamu sendiri.

## ✨ Fitur Utama

<table>
<tr><td width="50%" valign="top">

**Koleksi & Katalog**
- 📚 Katalog + koleksi pribadi, input volume dengan syntax range (`1,2,5-9,11,12`)
- 👁️ Tracking baca per volume — tandai sudah/belum, progres otomatis di datatable
- ⭐ Review & rating pribadi (-10 s/d +10, gaya MyAnimeList)
- 💌 Wishlist — series yang ingin dibaca tapi belum dikoleksi
- 🔁 Peminjaman volume — siapa pinjam, jatuh tempo, status terlambat otomatis

**Import & Data**
- 🔎 Import metadata dari [AniList](https://anilist.co) (GraphQL) — judul, sinopsis, genre, author, skor
- 📖 Import light novel dari RanobeDB — author/illustrator ter-split native
- ⚡ Batch import: filter genre + tahun + sort popularitas, multi-select sekaligus

</td><td width="50%" valign="top">

**Pengalaman Pengguna**
- 🎲 Rekomendasi & "Surprise Me" berbasis overlap genre koleksi
- ⌘K Global search / Command Palette — navigasi instan
- 📊 Dashboard dengan chart (Recharts), bukan cuma angka
- 🧵 Word cloud "Selera Genre" + funfact AI (gratis via Puter.js, atau BYO Gemini/OpenAI/Claude)
- 👥 Profil publik opt-in + follow + direktori pengguna
- ↩️ Undo di toast untuk aksi reversible
- 🌗 Tema Light/Dark/System, 🌐 tiga bahasa (id/en/ja)

**Admin & Infrastruktur**
- 🔐 Login SSO (PKCE OAuth2) *atau* magic link email — dua jalur setara
- 🗄️ Storage fleksibel (Local / S3-compatible) dari UI, tanpa sentuh `.env`
- 💾 Backup & restore database dari UI admin
- 🧑‍✈️ Role `super_admin` > `admin` > `user`, menu dinamis drag-and-drop

</td></tr>
</table>

Detail lengkap tiap fitur ada di [`CLAUDE.md`](CLAUDE.md) bagian "Fitur yang Sudah Ada".

---

## 🧱 Tech Stack

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
| AI (client-side) | Puter.js (default, gratis) — atau Gemini/OpenAI/Claude via UI admin |
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

## 🚀 Setup Lokal (Development)

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

Tidak mau setup SSO dulu? Pakai jalur darurat CLI (lihat [Troubleshooting](#-troubleshooting)) untuk langsung masuk sebagai `super_admin`.

### Storage saat development

Default driver `local` langsung jalan tanpa konfigurasi tambahan. Untuk switch ke S3-compatible (Cloudflare R2, dll), buka `/admin/settings/storage` setelah login sebagai `super_admin` — tidak perlu edit `.env`.

---

## 📦 Deployment ke Production

Dua metode tersedia — otomatis via `deploy.sh` atau manual step-by-step. Panduan lengkap termasuk setup AWS EC2, Azure VM, dan Local Server (VPS/bare metal): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)**.

Update kode di server yang sudah live:

```bash
bash update.sh
```

Script ini melakukan `git pull`, rebuild dependencies/frontend hanya jika perlu, jalankan migration baru, dan rebuild cache — tanpa menyentuh data yang sudah ada.

---

## 🧪 Testing

```bash
php artisan test
npx tsc --noEmit
```

---

## 🆘 Troubleshooting

**Nggak bisa login sama sekali / whitearchive.id (SSO) down?**

```bash
php artisan sso:emergency-login super_admin
```

Terbitkan link login sekali-pakai langsung dari CLI — nggak perlu nunggu SSO pulih atau email masuk. Argumen boleh role (`super_admin`/`admin`/`user`) atau email/username spesifik (mis. `php artisan sso:emergency-login admin@domainmu.com`). Command bakal nanya konfirmasi dulu sebelum nerbitin link, dan kalau ada beberapa user dengan role yang sama, kasih pilihan interaktif.

Selain itu, user (bukan cuma admin) juga bisa minta magic link login sekali-pakai sendiri lewat email — klik "Login" di halaman utama → pilih "Login dengan Email". Ini butuh provider Email (Resend) sudah dikonfigurasi di `/admin/settings` tab Email.

Panduan troubleshooting lebih lengkap (Nginx 502, migration gagal, storage permission, dll): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#troubleshooting)**.

---

## 🗺️ Belum Selesai (Backlog)

Jujur soal apa yang belum ada, biar nggak ada yang kaget:

- **Multi-account switching** (gaya X/Twitter, "Tambah Akun") — mekanisme sudah didesain (session-based linked accounts), belum diimplementasikan. Dua keputusan masih terbuka: scope tombol Logout dan penempatan UI switcher.
- **Advanced filter batch import AniList** — multi-select genre, filter tag (`tag_in`), filter status (`status_in`). Sudah diverifikasi jalan di API AniList, belum di-implementasikan di UI.
- **Activity feed di profil publik** (gaya Steam) — profil publik + follow sudah ada, feed aktivitasnya belum.
- **Badge/label "Selera Genre"** ("Genre Explorer" vs "Genre Loyalist") — ditunda, numpang di data yang sama dengan fitur word cloud.
- **Flash message controller belum multi-bahasa** — pesan sukses/error dari controller (`->with('success', ...)`) masih hardcode Bahasa Indonesia; validasi form & UI sendiri sudah full 3 bahasa.

Lihat [`CLAUDE.md`](CLAUDE.md) bagian "Belum dikerjakan (backlog)" dan [`docs/PHASES.md`](docs/PHASES.md) untuk histori & konteks lengkap tiap item.

---

## 📚 Dokumentasi

| Dokumen | Isi |
|---------|-----|
| [`CLAUDE.md`](CLAUDE.md) | Aturan coding, struktur folder, konvensi wajib untuk kontribusi |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Skema database, folder structure, request lifecycle, authorization flow |
| [`docs/PHASES.md`](docs/PHASES.md) | Log fase pengembangan dari awal sampai sekarang |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Tutorial deploy & update — otomatis maupun manual |
| [`docs/prd.md`](docs/prd.md) | Product requirements — latar belakang, persona, spesifikasi fitur |
| [`docs/RANOBEDB_INTEGRATION.md`](docs/RANOBEDB_INTEGRATION.md) | Riset API + rencana development import light novel dari RanobeDB |
| [`CHANGELOG.md`](CHANGELOG.md) | Histori perubahan penting per tanggal |

## 📄 Lisensi

MIT

<div align="right"><a href="#malas">⬆ kembali ke atas</a></div>

---

# 🇬🇧 English

> Built for collectors who need to track which volumes they own, their condition, and who's borrowing what — no manual spreadsheets, no monthly subscription, and your data stays yours.

## ✨ Key Features

<table>
<tr><td width="50%" valign="top">

**Collection & Catalog**
- 📚 Catalog + personal collection, add volumes via range syntax (`1,2,5-9,11,12`)
- 👁️ Per-volume read tracking — mark read/unread, auto progress in the collection table
- ⭐ Personal review & rating (-10 to +10, MyAnimeList-style)
- 💌 Wishlist — series you want to read but haven't collected yet
- 🔁 Volume lending — who borrowed it, due date, automatic overdue status

**Import & Data**
- 🔎 Import metadata from [AniList](https://anilist.co) (GraphQL) — title, synopsis, genres, authors, score
- 📖 Import light novels from RanobeDB — author/illustrator natively split
- ⚡ Batch import: filter by genre + year + sort by popularity, multi-select import

</td><td width="50%" valign="top">

**User Experience**
- 🎲 Recommendations & "Surprise Me" based on genre overlap with your collection
- ⌘K Global search / Command Palette — instant navigation
- 📊 Dashboard with charts (Recharts), not just raw numbers
- 🧵 "Genre Taste" word cloud + AI funfact (free via Puter.js, or bring your own Gemini/OpenAI/Claude)
- 👥 Opt-in public profile + follow + user directory
- ↩️ Undo button on toasts for reversible actions
- 🌗 Light/Dark/System theme, 🌐 three languages (id/en/ja)

**Admin & Infrastructure**
- 🔐 SSO login (PKCE OAuth2) *or* email magic link — two peer login methods
- 🗄️ Flexible storage (Local / S3-compatible) configured from the UI, no `.env` editing
- 💾 Database backup & restore from the admin UI
- 🧑‍✈️ `super_admin` > `admin` > `user` roles, database-driven drag-and-drop menus

</td></tr>
</table>

Full feature list in [`CLAUDE.md`](CLAUDE.md) under "Fitur yang Sudah Ada" (Indonesian, but code references are language-agnostic).

---

## 🧱 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| Frontend UI | React 19 + TypeScript 5 |
| UI components | shadcn/ui (Base UI-based) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database | SQLite (dev) / MySQL 8+ (prod) |
| Auth/Roles | Spatie Laravel Permission |
| SSO Auth | whitearchive.id (PKCE OAuth2) |
| External APIs | AniList GraphQL, RanobeDB REST |
| AI (client-side) | Puter.js (default, free) — or Gemini/OpenAI/Claude via admin UI |
| Email | Resend (configured via admin UI, not `.env`) |
| Localization | react-i18next (id/en/ja) |
| Drag & drop | @dnd-kit |

Full architecture details: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

### Role hierarchy

```
super_admin > admin > user
```

Access is gated on two layers: Spatie Role (resource level) + `CheckMenuAccess` middleware (route level).

---

## 🚀 Local Setup (Development)

Requirements: **PHP 8.2+**, **Composer**, **Node.js 20+**, **npm**.

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

Run the Laravel server in a separate terminal:

```bash
php artisan serve
```

Open `http://localhost:8000`.

### SSO login during development

Login goes through whitearchive.id SSO — register your application at `sso.whitearchive.id/dashboard/applications` to get `SSO_CLIENT_ID` and `SSO_CLIENT_SECRET`, then set them in `.env`:

```env
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI=http://localhost:8000/auth/callback
```

Don't want to set up SSO yet? Use the CLI emergency-access path (see [Troubleshooting](#-troubleshooting-1)) to log in directly as `super_admin`.

### Storage during development

The default `local` driver works out of the box with no extra configuration. To switch to S3-compatible storage (Cloudflare R2, etc.), open `/admin/settings/storage` after logging in as `super_admin` — no `.env` editing required.

---

## 📦 Production Deployment

Two methods available — automated via `deploy.sh` or manual step-by-step. Full guide including AWS EC2, Azure VM, and bare-metal/VPS setup: **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)**.

Update code on an already-live server:

```bash
bash update.sh
```

This script runs `git pull`, rebuilds dependencies/frontend only when needed, runs new migrations, and rebuilds caches — without touching existing data.

---

## 🧪 Testing

```bash
php artisan test
npx tsc --noEmit
```

---

## 🆘 Troubleshooting

**Can't log in at all / whitearchive.id (SSO) is down?**

```bash
php artisan sso:emergency-login super_admin
```

Issues a one-time login link straight from the CLI — no need to wait for SSO to recover or for an email to arrive. The argument can be a role (`super_admin`/`admin`/`user`) or a specific email/username (e.g. `php artisan sso:emergency-login admin@yourdomain.com`). The command asks for confirmation before issuing the link, and if multiple users share the same role, it gives you an interactive picker.

Regular users (not just admins) can also request their own one-time magic link via email — click "Login" on the landing page → choose "Login with Email". This requires an Email provider (Resend) already configured under `/admin/settings` → Email tab.

Fuller troubleshooting guide (Nginx 502s, failed migrations, storage permissions, etc.): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#troubleshooting)**.

---

## 🗺️ Known Gaps (Backlog)

Being upfront about what's *not* there yet, so nothing catches you off guard:

- **Multi-account switching** (X/Twitter-style "Add Account") — the mechanism is designed (session-based linked accounts) but not implemented yet. Two decisions remain open: Logout button scope, and where the account switcher lives in the UI.
- **Advanced AniList batch-import filters** — multi-select genre, tag filter (`tag_in`), status filter (`status_in`). Verified working against the live AniList API, not yet built into the UI.
- **Activity feed on public profiles** (Steam-style) — public profiles + follow already exist, the activity feed doesn't yet.
- **"Genre Taste" badges/labels** ("Genre Explorer" vs "Genre Loyalist") — deferred, would reuse the same data as the word-cloud feature.
- **Flash messages aren't localized yet** — success/error messages from controllers (`->with('success', ...)`) are still hardcoded in Indonesian; form validation and the rest of the UI are already fully trilingual.

See [`CLAUDE.md`](CLAUDE.md) under "Belum dikerjakan (backlog)" and [`docs/PHASES.md`](docs/PHASES.md) for full history and context per item.

---

## 📚 Documentation

| Document | Contents |
|---------|-----|
| [`CLAUDE.md`](CLAUDE.md) | Coding rules, folder structure, mandatory conventions for contributions |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Database schema, folder structure, request lifecycle, authorization flow |
| [`docs/PHASES.md`](docs/PHASES.md) | Development phase log from the start to now |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Deploy & update walkthrough — automated and manual |
| [`docs/prd.md`](docs/prd.md) | Product requirements — background, personas, feature specs |
| [`docs/RANOBEDB_INTEGRATION.md`](docs/RANOBEDB_INTEGRATION.md) | API research + development plan for RanobeDB light novel import |
| [`CHANGELOG.md`](CHANGELOG.md) | Notable changes, by date |

## 📄 License

MIT

<div align="right"><a href="#malas">⬆ back to top</a></div>
