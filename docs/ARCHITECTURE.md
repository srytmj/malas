# ARCHITECTURE — MALAS

**Versi:** 3.0
**Diperbarui:** 2026-07-23

> Dokumen ini menggambarkan arsitektur **aktual** aplikasi saat ini. Untuk histori perubahan, lihat [`CHANGELOG.md`](../CHANGELOG.md). Untuk log fase pengembangan, lihat [`PHASES.md`](PHASES.md).

---

## 1. Tech Stack

| Layer | Teknologi | Versi | Alasan |
|-------|-----------|-------|--------|
| Backend | Laravel | 12 | Mature, ekosistem besar, auth/policy built-in |
| Frontend bridge | Inertia.js | v2 | SPA feel tanpa API layer terpisah |
| Frontend | React | 19 | Ekosistem terbesar, futureproof |
| Language | TypeScript | 5 | Type safety, maintainable tanpa AI |
| UI Components | shadcn/ui (Base UI) | latest | Copy-paste, bukan dependency black-box; pakai `render` prop bukan `asChild` |
| Styling | Tailwind CSS | v4 | Utility-first, konsisten dengan shadcn |
| Bundler | Vite | latest | Fast HMR, built-in dengan Laravel |
| Auth/Role | Spatie Permission | latest | Industry standard untuk Laravel RBAC |
| Auth SSO | whitearchive.id | — | PKCE-based OAuth2; semua akun user dikelola via SSO, bukan register lokal |
| DB (dev) | SQLite | 3 | Zero config untuk development |
| DB (prod) | MySQL | 8+ | Proven untuk production |
| Storage | Local disk atau S3-compatible (Cloudflare R2, dll) | — | Dikonfigurasi via UI admin (`storage_settings` table), bukan `.env` |
| External API | AniList GraphQL | — | Import metadata manga/manhwa/manhua/novel; menggantikan Jikan/MAL |
| HTTP Client | Axios (via Inertia) | — | Sudah bundled |

---

## 2. Database Schema

### `users`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| sso_id | string unique | nullable — ID akun dari whitearchive.id |
| name | string | |
| username | string | nullable |
| email | string unique | |
| avatar | string | nullable |
| password | string | nullable (akun SSO tidak selalu punya password lokal) |
| role | enum | `super_admin`, `admin`, `user` |
| is_banned | boolean | default false |
| ban_reason | text | nullable |
| banned_at | timestamp | nullable |
| deleted_at | timestamp | soft delete |
| created_at / updated_at | timestamp | |

### `series`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| mal_id | bigint unique | nullable — legacy, sisa era Jikan |
| anilist_id | bigint unique | nullable |
| title_romaji | string | required |
| title_english | string | nullable |
| title_japanese | string | nullable |
| synopsis | text | nullable |
| cover_path | string | nullable — path relatif, di-resolve via `StorageSettingsService` |
| status | enum | `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published` |
| type | enum | `manga`, `manhwa`, `manhua`, `novel`, `one_shot`, `doujinshi` |
| published_from | date | nullable |
| published_to | date | nullable |
| total_volumes | int | nullable |
| score | decimal(4,2) | nullable |
| rank | int | nullable |
| genres | json | nullable — dari AniList |
| authors | json | nullable — dari AniList |
| themes | json | nullable — dari AniList |
| demographics | json | nullable — dari AniList |
| deleted_at | timestamp | soft delete |
| created_at / updated_at | timestamp | |

### `volumes`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| series_id | uuid FK | |
| volume_number | int | |
| cover_path | string | nullable |
| type | enum | `regular`, `digital`, `bind_up` |
| digital_source | string | nullable |
| isbn | string | nullable |
| published_at | date | nullable |
| deleted_at | timestamp | soft delete |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (series_id, volume_number) | |

### `collections`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| user_id | uuid FK | |
| series_id | uuid FK | |
| condition | enum | `mint`, `good`, `fair`, `poor` |
| acquired_at | date | nullable |
| notes | text | nullable |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (user_id, series_id) | satu koleksi per series per user |

### `collection_volumes`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| collection_id | uuid FK | cascade delete |
| volume_number | int | nomor volume yang dimiliki user |
| format | enum | `physical`, `ebook`, `online`, `webtoon` |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (collection_id, volume_number) | |

Catatan: tidak terikat ke tabel `volumes` (admin-defined). User input sendiri nomor volume yang mereka punya — mendukung syntax range (`1-5,7,9-12`) yang di-parse di `CollectionController::storeVolumes()`.

### `loans`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| collection_id | uuid FK | koleksi owner |
| collection_volume_id | uuid FK | volume user yang dipinjam (cascade delete) |
| borrower_name | string | nama peminjam (bebas) |
| loaned_at | date | required |
| due_at | date | nullable |
| returned_at | date | nullable — jika diisi = dikembalikan |
| notes | text | nullable |
| created_at / updated_at | timestamp | |

### `tickets`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| user_id | uuid FK | cascade delete |
| series_id | uuid FK | nullable, null on delete — series terkait (opsional, bisa pre-filled dari katalog) |
| subject | string | |
| type | enum | `catalog_request`, `title_revision`, `other` |
| message | text | |
| status | enum | `open`, `in_progress`, `resolved`, `closed` |
| admin_response | text | nullable |
| responded_by | uuid FK | nullable, null on delete — admin yang merespon |
| responded_at | timestamp | nullable |
| created_at / updated_at | timestamp | |

### `storage_settings`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| driver | enum | `local`, `s3` |
| access_key_id | string | nullable |
| secret_access_key | text | nullable — **ter-encrypt** via Eloquent cast |
| bucket | string | nullable |
| endpoint | string | nullable — untuk S3-compatible non-AWS (R2, dll) |
| region | string | nullable |
| url | string | nullable — custom public URL/CDN |
| created_at / updated_at | timestamp | |

Single-row table (satu konfigurasi aktif). Dibaca oleh `StorageSettingsService`, tidak pernah diakses langsung via `Storage::` facade di controller lain.

### `menus`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| key | string unique | slug, e.g. `series.index` |
| label | string | tampil di sidebar |
| icon | string | nama icon Lucide, nullable |
| route_name | string | Laravel route name, nullable |
| parent_key | string | FK ke `menus.key`, nullable (submenu) |
| sort_order | int | urutan tampil |
| is_visible | boolean | default true |
| is_maintenance | boolean | default false |
| maintenance_message | text | nullable, ada default |
| role_access | json | array role, e.g. `["admin","user"]` |
| created_at / updated_at | timestamp | |

### `announcements`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| title | string | |
| body | text | markdown |
| type | enum | `info`, `warning`, `danger`, `success` |
| is_active | boolean | default true |
| starts_at | datetime | nullable |
| expires_at | datetime | nullable |
| created_at / updated_at | timestamp | |

### `announcement_user` *(pivot)*
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| announcement_id | uuid FK | |
| user_id | uuid FK | |
| dismissed_at | timestamp | |

---

## 3. Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── MenuController.php
│   │   │   ├── SeriesController.php        (termasuk bulkDestroy)
│   │   │   ├── VolumeController.php
│   │   │   ├── CollectionController.php
│   │   │   ├── LoanController.php
│   │   │   ├── UserController.php
│   │   │   ├── AnnouncementController.php
│   │   │   ├── AniListController.php       (search & import dari AniList GraphQL)
│   │   │   ├── ImageSearchController.php
│   │   │   ├── TicketController.php        (admin view & respond)
│   │   │   ├── StorageSettingController.php
│   │   │   └── DatabaseBackupController.php (download/import backup, super_admin)
│   │   ├── User/
│   │   │   ├── DashboardController.php
│   │   │   ├── SeriesController.php        (katalog, read-only + search endpoint)
│   │   │   ├── CollectionController.php    (termasuk destroyVolumes bulk + range parsing)
│   │   │   ├── TicketController.php        (user buat & lihat tiket)
│   │   │   └── LoanController.php
│   │   └── Auth/
│   │       └── SsoController.php           (PKCE OAuth2 redirect/callback/logout)
│   ├── Middleware/
│   │   ├── CheckMenuAccess.php
│   │   └── EnsureNotBanned.php
│   └── Requests/
│       ├── Admin/
│       └── User/
├── Models/
│   ├── User.php
│   ├── Series.php          (genres/authors/themes/demographics json)
│   ├── Volume.php
│   ├── Collection.php
│   ├── CollectionVolume.php
│   ├── Loan.php
│   ├── Menu.php
│   ├── Announcement.php
│   ├── Ticket.php
│   └── StorageSetting.php  (encrypted secret_access_key cast)
├── Policies/
│   ├── SeriesPolicy.php
│   ├── VolumePolicy.php
│   ├── CollectionPolicy.php
│   ├── LoanPolicy.php
│   ├── MenuPolicy.php
│   ├── UserPolicy.php
│   ├── AnnouncementPolicy.php
│   ├── TicketPolicy.php
│   └── StorageSettingPolicy.php
└── Services/
    ├── AniListService.php         (GraphQL client, inject StorageSettingsService untuk cover)
    └── StorageSettingsService.php (satu pintu untuk semua operasi file storage)

resources/js/
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.tsx
│   │   ├── Menus/          Index.tsx, Edit.tsx
│   │   ├── Series/         Index.tsx (bulk delete), Create.tsx, Edit.tsx, Show.tsx, EditVolume.tsx
│   │   ├── Collections/    Index.tsx
│   │   ├── Loans/          Index.tsx
│   │   ├── Users/          Index.tsx, Show.tsx
│   │   ├── Announcements/  Index.tsx, Create.tsx, Edit.tsx
│   │   ├── AniList/        Index.tsx (search & import — card overlay), Status.tsx
│   │   ├── Tickets/        Index.tsx, Show.tsx
│   │   └── Settings/       Storage.tsx, Database.tsx
│   ├── User/
│   │   ├── Dashboard.tsx
│   │   ├── Catalog/        Index.tsx, Show.tsx
│   │   ├── Collection/     Index.tsx, Show.tsx (bulk delete volumes)
│   │   ├── Tickets/        Index.tsx, Create.tsx, Show.tsx
│   │   └── Loans/          Index.tsx
│   ├── Auth/               Banned.tsx
│   ├── Settings/           Index.tsx (read-only profil dari SSO)
│   ├── Error.tsx           (handle 403, 404, 500, 503)
│   ├── Maintenance.tsx
│   └── Landing.tsx
├── Layouts/
│   ├── AdminLayout.tsx     (sidebar + topbar admin, ScrollArea wrapping)
│   └── UserLayout.tsx      (sidebar + topbar user)
├── Components/
│   ├── ui/                 (shadcn/ui — JANGAN MODIFIKASI)
│   └── app/
│       ├── VolumeGrid.tsx          (pure display, no toggle)
│       ├── AnnouncementBanner.tsx
│       ├── StatusBadge.tsx         (SeriesStatusBadge, SeriesTypeBadge, VolumeTypeBadge,
│       │                            TicketStatusBadge, TicketTypeBadge, VolumeFormatBadge)
│       ├── PageHeader.tsx          (responsive — stack kolom di mobile)
│       ├── EmptyState.tsx
│       └── Pagination.tsx          (responsive — flex-wrap di mobile)
├── hooks/
│   └── useFlash.ts         (sonner toast dari flash session)
└── lib/
    ├── utils.ts
    └── types.ts            (shared TypeScript interfaces, termasuk TicketType/TicketStatus)
```

---

## 4. Request Lifecycle

```
Browser → Laravel Router
       → auth middleware       (cek login via SSO session)
       → not_banned            (cek is_banned)
       → check.menu            (cek is_maintenance + role_access dari tabel menus)
       → Controller            (authorize via Policy, atau abort_unless(hasRole) untuk super_admin-only)
       → Inertia::render()     (return React page + props minimal)
       → React render di browser
```

---

## 5. Authorization Flow

```
1. Route middleware: auth + not_banned + check.menu
2. Controller: $this->authorize('action', $model)  ← via Laravel Policy
3. Policy: $user->role === 'admin' || $user->isOwner($resource)
```

Contoh policy:
```php
// CollectionPolicy.php
public function update(User $user, Collection $collection): bool
{
    return $user->isAdmin() || $collection->user_id === $user->id;
}
```

Pengecualian: fitur yang murni `super_admin`-only dan tanpa model relevan (Storage Settings, Database Backup) memakai `abort_unless(auth()->user()->hasRole('super_admin'), 403)` langsung di controller, bukan Policy.

---

## 6. Menu System

Tabel `menus` adalah source of truth untuk navigasi:

```
Setiap request → CheckMenuAccess middleware:
1. Ambil current route name
2. Cari di tabel menus WHERE route_name = current_route
3. Jika tidak ditemukan → skip (route tidak ada di menu system)
4. Jika is_maintenance = true AND user bukan admin/super_admin → return Maintenance page
5. Jika user role tidak ada di role_access → abort(403)
6. Pass → lanjut ke controller
```

Frontend sidebar dibangun dari data menus yang dikirim via shared Inertia data (`HandleInertiaRequests` middleware). Setiap menu baru wajib ditambahkan ke `MenuSeeder.php` dengan `updateOrCreate`.

---

## 7. File Storage

Semua file (cover series, cover volume) diakses lewat **`StorageSettingsService`** — tidak ada kode lain yang boleh memanggil `Storage::` facade langsung.

- Konfigurasi (driver `local` atau `s3`, credentials, bucket, endpoint, region, custom URL) disimpan di tabel `storage_settings`, dikelola via `/admin/settings/storage` — **bukan** `.env`.
- `secret_access_key` di-encrypt otomatis lewat Eloquent cast di model `StorageSetting`.
- Driver `s3` mendukung endpoint kustom, jadi kompatibel dengan AWS S3 asli maupun layanan S3-compatible (Cloudflare R2, Backblaze B2, MinIO, dll).
- `StorageSettingsService` menyediakan method: `disk()`, `url($path)`, `storeUploadedFile($file, $dir)`, `storeContents($dir, $filename, $contents)`, `delete($path)`.
- Path convention: `covers/{uuid}.{ext}` untuk cover series/volume yang diupload manual; `covers/url_{uniqid}.{ext}` untuk cover yang di-fetch dari URL AniList.

---

## 8. Integrasi Eksternal

### AniList GraphQL
- Endpoint: `https://graphql.anilist.co`
- Dipakai untuk search & import metadata series (judul, sinopsis, genre, author, theme, demographic, skor, cover) dari `Admin/AniList/Index.tsx`.
- Sync ulang metadata ke series yang sudah ada tersedia dari Popover "Sync AniList" di halaman Edit Series.
- Menggantikan integrasi Jikan/MyAnimeList generasi sebelumnya (`JikanService` sudah dihapus total).

### SSO whitearchive.id
- PKCE-based OAuth2. Semua user (termasuk admin) login lewat SSO — tidak ada form register/login lokal.
- Flow: `/auth/redirect` → whitearchive.id → `/auth/callback` (`SsoController`) → user dibuat/diupdate dari klaim SSO (`sso_id`, `name`, `username`, `email`, `avatar`) → session dibuat.
- Halaman `Settings/Index.tsx` menampilkan profil secara read-only (data profil dikelola di sisi SSO, bukan di MALAS).
