# MALAS — Dokumentasi Teknis

**Manga Library System** — Platform manajemen koleksi fisik manga/manhwa dengan fitur bulk collection management, peminjaman, dan auto-scraping dari MyAnimeList via Jikan API.

## Status Proyek

- **Versi**: v0.2.0
- **Tanggal Update**: 2026-06-13
- **Stack**: Laravel 12, MySQL (MariaDB 10.4), Alpine.js 3.x, Tailwind CSS, Cloudflare R2, Queue (database)
- **Status**: Fase 1 selesai — panel admin lengkap. Fitur user-facing belum diimplementasi.

## Daftar Dokumen

### Core Documentation

| File | Deskripsi |
|---|---|
| [01-erd.md](./01-erd.md) | Entity Relationship Diagram — semua tabel aktual (series, volumes, user_libraries, user_collections, loans, loan_items, jikan_schedules, jikan_scrape_sessions, users, activity_logs) |
| [02-arsitektur-sistem.md](./02-arsitektur-sistem.md) | Arsitektur sistem: modular monolith, R2 storage, database queue, Alpine.js frontend, alur data penting |
| [03-user-flow.md](./03-user-flow.md) | 6 user flow: bulk collection add, tambah series, Jikan scraping, batch delete, login, peminjaman |
| [04-ci-cd-pipeline.md](./04-ci-cd-pipeline.md) | Setup lokal (XAMPP), workflow CI, deployment manual steps, checklist pre-deploy |
| [05-struktur-direktori.md](./05-struktur-direktori.md) | Struktur direktori modular `app/Modules/`, views, routes, config |

### Specifications

| File | Deskripsi |
|---|---|
| [prd.md](./prd.md) | Product Requirements — fitur yang ada, backlog, batasan saat ini |
| [srs.md](./srs.md) | Software Requirements Specification — persyaratan fungsional dan non-fungsional |
| [api-contract.md](./api-contract.md) | Kontrak API internal AJAX — DataTable endpoints, series search, bulk store, batch destroy |

### Developer Guides

| File | Deskripsi |
|---|---|
| [onboarding.md](./onboarding.md) | Panduan mulai cepat — setup lokal dalam 15 menit, orientasi codebase, FAQ |
| [env-setup.md](./env-setup.md) | Semua environment variables dengan penjelasan — DB, R2, Queue, App |
| [design.md](./design.md) | Design system — palet warna, tipografi, komponen HTML Tailwind yang digunakan |
| [architecture.md](./architecture.md) | Arsitektur detail — keputusan teknis, data flow diagrams, technical debt |
| [security.md](./security.md) | Security model — auth, CSRF, XSS, credential management, file upload |
| [test-plan.md](./test-plan.md) | Test plan — manual test cases, regression checklist, struktur test yang direkomendasikan |

### Operations

| File | Deskripsi |
|---|---|
| [deployment-checklist.md](./deployment-checklist.md) | Checklist deploy — setup baru, pre-deploy, steps, post-deploy verification, rollback |
| [runbook.md](./runbook.md) | Prosedur operasional — startup checklist, Jikan troubleshooting, queue management, R2 ops |
| [agent-debug.md](./agent-debug.md) | Panduan AI agent untuk debugging — stack context, pola kode, checklist debug per masalah |
| [agent-deploy.md](./agent-deploy.md) | Panduan AI agent untuk deployment — steps server baru, Nginx config, Supervisor, checklist |

### Reference

| File | Deskripsi |
|---|---|
| [glossary.md](./glossary.md) | Daftar istilah teknis — MALAS-specific terms dan teknologi yang digunakan |
| [changelog.md](./changelog.md) | Riwayat perubahan signifikan — fitur baru, perubahan schema, keputusan arsitektur |

## Quick Reference

### Jalankan Lokal

```bash
cd src
composer install && npm install
cp .env.example .env && php artisan key:generate
# Edit .env (DB + R2 credentials)
php artisan migrate
npm run build
php artisan serve
```

### Commands Penting

```bash
php artisan queue:work          # Jalankan queue worker (untuk Jikan)
php artisan schedule:run        # Test scheduler sekali
php artisan migrate:status      # Cek status migration
php artisan optimize:clear      # Bersihkan semua cache
```

### Struktur Modul

```
app/Modules/
├── Core/       → Series, User, ActivityLog, HasSoftDeletesWithActor
├── Collection/ → Volume, UserLibrary, UserCollection, Loan, LoanItem
├── Admin/      → Controllers + routes (custom admin panel)
└── Jikan/      → JikanSchedule, JikanScrapeSession, JikanService
```
