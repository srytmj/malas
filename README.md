# MALAS — Manajemen Koleksi Manga & Light Novel

Aplikasi web untuk mengelola koleksi manga dan light novel pribadi, lengkap dengan fitur pencarian via MyAnimeList (Jikan API), sistem peminjaman, dan manajemen pengguna.

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| UI | React 19 + TypeScript 5 |
| Komponen | shadcn/ui + Tailwind CSS v4 |
| Database (dev) | SQLite |
| Database (prod) | MySQL 8+ |
| Auth/Role | Spatie Laravel Permission |
| Storage | Cloudflare R2 |

## Fitur Utama

- **Katalog Series** — browse dan cari manga/manhwa/manhua/novel; import metadata dari MyAnimeList via Jikan API
- **Koleksi Pribadi** — tambah series ke koleksi, tandai volume yang sudah dimiliki
- **Peminjaman** — catat volume yang dipinjamkan ke orang lain beserta tanggal jatuh tempo
- **Pengumuman** — admin dapat menerbitkan pengumuman yang tampil di halaman pengguna
- **Pengaturan Akun** — ganti nama (rate-limited 1× per 2 jam) dan ganti password
- **Manajemen Admin** — kelola series, volume, pengguna, menu, dan pinjaman dari panel admin

## Struktur Role

```
super_admin > admin > user
```

Akses dikontrol dua lapis: Spatie Role (resource level) + `CheckMenuAccess` middleware (route level).

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env, lalu:
php artisan migrate --seed

npm run dev
# atau untuk production:
npm run build
```

## Dokumentasi

- [Arsitektur & Struktur Proyek](docs/ARCHITECTURE.md)
- [Product Requirements Document](docs/PRD.md)
- [User Flows & Wireflow](docs/FLOWS.md)

## Lisensi

MIT
