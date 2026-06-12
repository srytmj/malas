# MALAS - Dokumentasi Teknis

**Manga Library System** - Platform pelacakan bacaan digital dan manajemen koleksi fisik manga/manhwa.

## Versi Dokumen

- Versi: v1.0.0
- Tanggal: 2026-06-13
- Status: Fase 1 (MVP Koleksi) - implementasi awal

## Daftar Isi

| File | Deskripsi |
|---|---|
| [01-erd.md](./01-erd.md) | Entity Relationship Diagram (ERD) seluruh tabel database, termasuk partial unique index |
| [02-arsitektur-sistem.md](./02-arsitektur-sistem.md) | Diagram arsitektur sistem: client, backend modular, queue, cache, storage, source eksternal |
| [03-user-flow.md](./03-user-flow.md) | 4 user flow diagram: tambah koleksi, peminjaman, admin restore, login/register |
| [04-ci-cd-pipeline.md](./04-ci-cd-pipeline.md) | Pipeline CI/CD: test, build, deploy staging/production, smoke test, rollback |
| [05-struktur-direktori.md](./05-struktur-direktori.md) | Struktur direktori modular `app/Modules/` dan `tests/` |
| [glossary.md](./glossary.md) | Daftar istilah teknis proyek |
| [deployment-checklist.md](./deployment-checklist.md) | Checklist deployment untuk administrator |

## Referensi Sumber

Dokumentasi ini disusun berdasarkan spesifikasi lengkap di `malas.md` dan hasil sesi klarifikasi Tahap 1-2.