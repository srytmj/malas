# ARCHITECTURE — MALAS v2

**Versi:** 2.0  
**Tanggal:** 2026-06-26

---

## 1. Tech Stack

| Layer | Teknologi | Versi | Alasan |
|-------|-----------|-------|--------|
| Backend | Laravel | 12 | Mature, ekosistem besar, auth/policy built-in |
| Frontend bridge | Inertia.js | v2 | SPA feel tanpa API layer terpisah |
| Frontend | React | 19 | Ekosistem terbesar, futureproof |
| Language | TypeScript | 5 | Type safety, maintainable tanpa AI |
| UI Components | shadcn/ui | latest | Copy-paste, bukan dependency black-box |
| Styling | Tailwind CSS | v4 | Utility-first, konsisten dengan shadcn |
| Bundler | Vite | latest | Fast HMR, built-in dengan Laravel |
| Auth/Role | Spatie Permission | latest | Industry standard untuk Laravel RBAC |
| DB (dev) | SQLite | 3 | Zero config untuk development |
| DB (prod) | MySQL | 8+ | Proven untuk production |
| Storage | Cloudflare R2 | — | S3-compatible, murah, global CDN |
| HTTP Client | Axios (via Inertia) | — | Sudah bundled |

---

## 2. Database Schema

### `users`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp | nullable |
| password | string | hashed |
| role | enum | `super_admin`, `admin`, `user` |
| is_banned | boolean | default false |
| ban_reason | text | nullable |
| banned_at | timestamp | nullable |
| remember_token | string | nullable |
| deleted_at | timestamp | soft delete |
| created_at / updated_at | timestamp | |

### `series`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| mal_id | bigint unique | nullable |
| title_romaji | string | required |
| title_english | string | nullable |
| title_japanese | string | nullable |
| synopsis | text | nullable |
| cover_path | string | nullable, path di R2 |
| status | enum | `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published` |
| type | enum | `manga`, `manhwa`, `manhua`, `novel`, `one_shot`, `doujinshi` |
| published_from | date | nullable |
| published_to | date | nullable |
| total_volumes | int | nullable |
| score | decimal(4,2) | nullable |
| rank | int | nullable |
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

### `collection_volumes` *(pivot)*
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| collection_id | uuid FK | |
| volume_id | uuid FK | |
| is_owned | boolean | default true |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (collection_id, volume_id) | |

### `loans`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| collection_id | uuid FK | koleksi owner |
| volume_id | uuid FK | volume yang dipinjam |
| borrower_name | string | nama peminjam (bebas) |
| loaned_at | date | required |
| due_at | date | nullable |
| returned_at | date | nullable — jika diisi = dikembalikan |
| notes | text | nullable |
| created_at / updated_at | timestamp | |

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
│   │   │   ├── SeriesController.php
│   │   │   ├── VolumeController.php
│   │   │   ├── CollectionController.php
│   │   │   ├── LoanController.php
│   │   │   ├── UserController.php
│   │   │   ├── AnnouncementController.php
│   │   │   └── JikanController.php
│   │   ├── User/
│   │   │   ├── DashboardController.php
│   │   │   ├── SeriesController.php
│   │   │   ├── CollectionController.php
│   │   │   └── LoanController.php
│   │   └── Auth/          (dari Laravel Breeze)
│   ├── Middleware/
│   │   ├── CheckMenuAccess.php
│   │   └── EnsureNotBanned.php
│   └── Requests/
│       ├── Admin/
│       └── User/
├── Models/
│   ├── User.php
│   ├── Series.php
│   ├── Volume.php
│   ├── Collection.php
│   ├── Loan.php
│   ├── Menu.php
│   └── Announcement.php
├── Policies/
│   ├── SeriesPolicy.php
│   ├── VolumePolicy.php
│   ├── CollectionPolicy.php
│   ├── LoanPolicy.php
│   └── MenuPolicy.php
└── Services/
    └── JikanService.php

resources/js/
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.tsx
│   │   ├── Menus/          Index.tsx, Edit.tsx
│   │   ├── Series/         Index.tsx, Create.tsx, Edit.tsx, Show.tsx
│   │   ├── Volumes/        (nested dalam Show.tsx Series)
│   │   ├── Collections/    Index.tsx, Show.tsx
│   │   ├── Loans/          Index.tsx
│   │   ├── Users/          Index.tsx, Show.tsx
│   │   └── Announcements/  Index.tsx, Create.tsx, Edit.tsx
│   ├── User/
│   │   ├── Dashboard.tsx
│   │   ├── Catalog/        Index.tsx, Show.tsx
│   │   ├── Collection/     Index.tsx, Show.tsx
│   │   └── Loans/          Index.tsx
│   ├── Auth/               (dari Breeze)
│   └── Maintenance.tsx     (halaman maintenance mode)
├── Layouts/
│   ├── AdminLayout.tsx     (sidebar + topbar admin)
│   └── UserLayout.tsx      (sidebar + topbar user)
├── Components/
│   ├── ui/                 (shadcn/ui — JANGAN MODIFIKASI)
│   └── app/
│       ├── SeriesCard.tsx
│       ├── VolumeGrid.tsx
│       ├── CollectionCard.tsx
│       ├── AnnouncementBanner.tsx
│       └── ...
├── hooks/
│   ├── useAuth.ts
│   └── useMenus.ts
└── lib/
    ├── utils.ts
    └── types.ts            (shared TypeScript interfaces)
```

---

## 4. Request Lifecycle

```
Browser → Laravel Router
       → auth middleware       (cek login)
       → EnsureNotBanned       (cek is_banned)
       → CheckMenuAccess       (cek is_maintenance + role_access dari tabel menus)
       → Controller            (authorize via Policy)
       → Inertia::render()     (return React page + props)
       → React render di browser
```

---

## 5. Authorization Flow

```
1. Route middleware: auth + check.menu
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

---

## 6. Menu System

Tabel `menus` adalah source of truth untuk navigasi:

```
Setiap request → CheckMenuAccess middleware:
1. Ambil current route name
2. Cari di tabel menus WHERE route_name = current_route
3. Jika tidak ditemukan → skip (route tidak ada di menu system)
4. Jika is_maintenance = true AND user bukan admin → return Maintenance page
5. Jika user role tidak ada di role_access → abort(403)
6. Pass → lanjut ke controller
```

Frontend sidebar dibangun dari data menus yang dikirim via shared Inertia data (HandleInertiaRequests middleware).

---

## 7. File Storage

Semua file (cover series, cover volume) disimpan di Cloudflare R2:
- Disk name: `r2` (konfigurasi di `config/filesystems.php`)
- Path convention: `covers/series/{series_id}.webp`, `covers/volumes/{volume_id}.webp`
- Public URL via R2 custom domain
- Upload: konversi ke WebP, resize max 300×450px (untuk cover buku)
