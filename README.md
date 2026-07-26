# MALAS — Manga Library Admin System

Aplikasi pengelolaan koleksi pribadi manga/manhwa/manhua/light novel. Dibuat untuk kolektor yang perlu melacak volume mana yang dimiliki, kondisinya, dan siapa yang sedang meminjam — tanpa spreadsheet manual.

---

## Fitur Utama

- **Katalog & koleksi pribadi** — browse katalog series, tambah ke koleksi, input volume yang dimiliki (mendukung syntax range: `1,2,5-9,11,12`)
- **Tracking baca per volume** — tandai volume sudah/belum dibaca, lihat progres baca di datatable koleksi, tandai semua sekaligus
- **Review & rating pribadi** — rating -10 s/d +10 (gaya MyAnimeList) + komentar per series di koleksi
- **Rekomendasi & Surprise Me** — rekomendasi series di dashboard berdasarkan overlap genre dengan koleksi, plus tombol pilih random
- **Global search** — cari judul manga di katalog/koleksi atau navigasi cepat lewat ⌘K, dari search bar di header maupun Command Palette admin
- **Dashboard dengan chart** — statistik series/koleksi/pinjaman divisualisasikan (Recharts), bukan cuma angka
- **Import metadata dari AniList** — search & import judul, sinopsis, genre, author, theme, demographic, skor langsung dari [AniList GraphQL](https://anilist.co)
- **Peminjaman volume** — catat siapa yang meminjam, tanggal jatuh tempo, status terlambat otomatis
- **Sistem tiket** — user bisa request judul baru masuk katalog, admin merespon lewat tiket
- **Undo pada aksi reversible** — toast notifikasi punya tombol "Undo" untuk aksi seperti tandai baca
- **Login SSO** — autentikasi PKCE OAuth2 via whitearchive.id, tidak ada akun lokal terpisah
- **Storage fleksibel** — konfigurasi Local disk atau S3-compatible (Cloudflare R2, dll) langsung dari UI admin, tanpa edit `.env`
- **Backup & restore database** — download/import dump SQL dari UI admin (super_admin only)
- **Role-based access** — `super_admin` > `admin` > `user`, dikontrol lewat Spatie Permission + menu management berbasis database
- **Mobile-first** — semua halaman sisi user responsive dari layar 375px ke atas

Detail lengkap tiap fitur ada di [`CLAUDE.md`](CLAUDE.md) bagian "Fitur yang Sudah Ada".

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| Frontend UI | React 19 + TypeScript 5 |
| Komponen UI | shadcn/ui (Base UI) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database | SQLite (dev) / MySQL 8+ (prod) |
| Auth/Role | Spatie Laravel Permission |
| Auth SSO | whitearchive.id (PKCE OAuth2) |
| External API | AniList GraphQL |

Detail arsitektur lengkap: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

## Struktur Role

```
super_admin > admin > user
```

Akses dikontrol dua lapis: Spatie Role (resource level) + `CheckMenuAccess` middleware (route level).

---

## Setup Lokal (Development)

Prasyarat: PHP 8.2+, Composer, Node.js 20+, npm.

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

### Storage saat development

Default driver `local` langsung jalan tanpa konfigurasi tambahan. Untuk switch ke S3-compatible (Cloudflare R2, dll), buka `/admin/settings/storage` setelah login sebagai `super_admin` — tidak perlu edit `.env`.

---

## Deployment ke Production

Dua metode tersedia — otomatis via `deploy.sh` atau manual step-by-step. Panduan lengkap termasuk setup AWS EC2, Azure VM, dan Local Server (VPS/bare metal): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)**.

Update kode di server yang sudah live:

```bash
bash update.sh
```

Script ini melakukan `git pull`, rebuild dependencies/frontend hanya jika perlu, jalankan migration baru, dan rebuild cache — tanpa menyentuh data yang sudah ada.

---

## Testing

```bash
php artisan test
npx tsc --noEmit
```

---

## Dokumentasi

| Dokumen | Isi |
|---------|-----|
| [`CLAUDE.md`](CLAUDE.md) | Aturan coding, struktur folder, konvensi wajib untuk kontribusi |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Skema database, folder structure, request lifecycle, authorization flow |
| [`docs/PHASES.md`](docs/PHASES.md) | Log fase pengembangan dari awal sampai sekarang |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Tutorial deploy & update — otomatis maupun manual |
| [`docs/prd.md`](docs/prd.md) | Product requirements — latar belakang, persona, spesifikasi fitur |
| [`CHANGELOG.md`](CHANGELOG.md) | Histori perubahan penting per tanggal |

## Lisensi

MIT
