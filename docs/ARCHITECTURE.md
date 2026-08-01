# ARCHITECTURE — MALAS

**Versi:** 3.2
**Diperbarui:** 2026-08-01

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
| Charts | Recharts + `ui/chart.tsx` | latest | Dashboard admin & user |
| Carousel | embla-carousel-react + `ui/carousel.tsx` | latest | Rekomendasi dashboard user |
| Command menu | cmdk + `ui/command.tsx` | latest | Command Palette admin & Global Search user |
| Drag & drop | `@dnd-kit/core` + `@dnd-kit/sortable` + `@dnd-kit/utilities` | latest | Reorder menu sidebar admin (`SortableMenuList.tsx`) |
| Multi-bahasa | react-i18next | latest | UI id/en/ja, namespace JSON per halaman, lihat §9 |
| AI (client-side) | Puter.js | v2 | Provider default fitur Selera Genre — jalan di browser user, gratis, tanpa API key server |
| External API | RanobeDB REST API | — | Import metadata light novel, paralel dengan AniList; lihat [`RANOBEDB_INTEGRATION.md`](RANOBEDB_INTEGRATION.md) |

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
| is_profile_public | boolean | default false — opt-in tampilkan profil di `/u/{user}` (bisa diakses guest) |
| locale | string(5) | default `id` — preferensi bahasa UI (`id`/`en`/`ja`) |
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
| ranobedb_id | bigint unique | nullable — dari RanobeDB, khusus light novel |
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
| genres | json | nullable — dari AniList/RanobeDB |
| authors | json | nullable — dari AniList/RanobeDB |
| illustrators | json | nullable — dari RanobeDB (`staff[].role = illustrator`), AniList tidak split ini |
| themes | json | nullable — dari AniList/RanobeDB |
| demographics | json | nullable — dari AniList/RanobeDB |
| is_adult | boolean | default false — dipakai untuk blur konten 18+ |
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
| personal_rating | smallint | nullable, -10 s/d 10 (gaya MyAnimeList: negatif = tidak direkomendasikan) |
| personal_review | text | nullable — komentar pribadi user tentang series ini |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (user_id, series_id) | satu koleksi per series per user |

### `collection_volumes`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| collection_id | uuid FK | cascade delete |
| volume_number | int | nomor volume yang dimiliki user |
| format | enum | `physical`, `ebook`, `online`, `webtoon` |
| ebook_source | string | nullable — sumber ebook (mis. `mangaplus`, `k_manga`), relevan kalau format `ebook`/`online` |
| language | string | nullable — bahasa rilis volume yang dimiliki user |
| read_at | timestamp | nullable — diisi saat volume ditandai sudah dibaca, null = belum dibaca |
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

### `wishlist_items`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| user_id | uuid FK | cascade delete |
| series_id | uuid FK | cascade delete |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (user_id, series_id) | satu wishlist entry per series per user |

Series yang belum dikoleksi tapi ingin dibaca user. Terpisah dari `collections` — tidak melibatkan tracking volume/format.

### `follows`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| follower_id | uuid FK ke `users` | cascade delete — user yang follow |
| following_id | uuid FK ke `users` | cascade delete — user yang di-follow |
| created_at / updated_at | timestamp | |
| **UNIQUE** | (follower_id, following_id) | |

Dipakai untuk fitur follow di profil publik (`/u/{user}`) dan direktori pengguna (`/directory`, butuh login).

### `ai_settings`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| provider | string | default `gemini` — `puter` / `gemini` / `openai` / `claude` |
| api_key | text | nullable — **ter-encrypt** via Eloquent cast, tidak dipakai untuk provider `puter` (client-side, tanpa key) |
| created_at / updated_at | timestamp | |

Single-row table, dikelola dari `/admin/settings` tab AI. Kalau belum ada row sama sekali, aplikasi default ke provider `puter`.

### `genre_funfacts`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| user_id | uuid FK unique | cascade delete — satu funfact per user |
| provider | string | nullable — provider yang dipakai saat generate terakhir |
| content | text | nullable — teks funfact hasil AI |
| generated_at | timestamp | nullable |
| collections_count_at_generation | int unsigned | nullable — snapshot jumlah koleksi saat generate, dipakai untuk trigger auto-regenerate saat koleksi bertambah signifikan |
| manual_regenerate_count | tinyint unsigned | default 0 — jumlah generate ulang manual dalam window berjalan |
| manual_regenerate_window_started_at | timestamp | nullable |
| quota_override | int unsigned | nullable — override batas kuota per user (default kuota: `GenreFunfact::DEFAULT_MANUAL_QUOTA` = 5/minggu), diatur admin di `/admin/funfact-quota` |
| created_at / updated_at | timestamp | |

Bagian dari fitur "Selera Genre" (word cloud + funfact AI) di dashboard user — lihat §9.

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
│   │   │   ├── DatabaseBackupController.php (download/import backup, super_admin)
│   │   │   ├── ActivityLogController.php   (viewer log aktivitas admin)
│   │   │   ├── SiteSettingController.php   (blur konten 18+)
│   │   │   ├── SeriesMediaController.php   (galeri media tambahan per series)
│   │   │   ├── CommandSearchController.php (search Series/Users/Tickets untuk Command Palette)
│   │   │   ├── ExternalSearchController.php (search gabungan AniList+RanobeDB untuk admin, /admin/search-external)
│   │   │   ├── RanobeDbController.php      (search & import metadata light novel dari RanobeDB)
│   │   │   ├── AiSettingController.php     (konfigurasi provider AI: puter/gemini/openai/claude + api_key)
│   │   │   └── GenreFunfactController.php  (kelola kuota generate-ulang funfact per user, /admin/funfact-quota)
│   │   ├── User/
│   │   │   ├── DashboardController.php     (rekomendasi genre + Surprise Me + funfact AI: regenerate/
│   │   │   │                                auto-save/report-error/genre-detail)
│   │   │   ├── SeriesController.php        (katalog, read-only + search endpoint)
│   │   │   ├── CollectionController.php    (destroyVolumes bulk, range parsing, toggle/mark-all
│   │   │   │                                read, update review/rating, format per-volume/bulk)
│   │   │   ├── TicketController.php        (user buat & lihat tiket)
│   │   │   ├── LoanController.php
│   │   │   ├── SearchController.php        (search Series + Collection untuk Global Search)
│   │   │   ├── ProfileController.php       (profil publik /u/{user} — guest-accessible, follow/unfollow,
│   │   │   │                                direktori pengguna)
│   │   │   └── WishlistController.php      (series yang ingin dibaca, belum dikoleksi)
│   │   └── Auth/
│   │       └── SsoController.php           (PKCE OAuth2 redirect/callback/logout)
│   ├── Middleware/
│   │   ├── CheckMenuAccess.php
│   │   ├── EnsureNotBanned.php
│   │   └── SetLocale.php        (set App::setLocale() dari users.locale, default 'id')
│   └── Requests/
│       ├── Admin/
│       └── User/
├── Models/
│   ├── User.php             (is_profile_public, locale; relasi followers/following, wishlistItems, genreFunfact;
│   │                          resolveRouteBinding by username lalu id)
│   ├── Series.php           (genres/authors/illustrators/themes/demographics json, ranobedb_id, is_adult)
│   ├── Volume.php
│   ├── Collection.php       (condition, personal_rating -10..10, personal_review)
│   ├── CollectionVolume.php (format, ebook_source, language, read_at datetime cast)
│   ├── Loan.php
│   ├── Menu.php
│   ├── Announcement.php
│   ├── Ticket.php
│   ├── StorageSetting.php   (encrypted secret_access_key cast)
│   ├── ActivityLog.php      (audit log aksi sensitif admin)
│   ├── SiteSetting.php      (blur_adult_content, single-row)
│   ├── WishlistItem.php
│   ├── Follow.php           (follower_id / following_id, keduanya FK ke users)
│   ├── AiSetting.php        (encrypted api_key cast, single-row)
│   └── GenreFunfact.php     (DEFAULT_MANUAL_QUOTA = 5, quota_override per user)
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
    ├── RanobeDbService.php        (REST client RanobeDB, khusus light novel, paralel dengan AniListService)
    ├── AiFunfactService.php       (bangun prompt dari data koleksi user, panggil provider Gemini/OpenAI/
    │                                Claude via HTTP; provider `puter` di-generate di browser, lihat lib/puter.ts)
    ├── AiRateLimitException.php   (dilempar AiFunfactService saat provider balas HTTP 429, ditangkap
    │                                DashboardController agar fallback ke teks default tanpa potong kuota manual)
    └── StorageSettingsService.php (satu pintu untuk semua operasi file storage)

resources/js/
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.tsx   (stat cards + chart Series per Status, Koleksi per Tipe, Status Pinjaman)
│   │   ├── Menus/          Index.tsx (drag-drop reorder), Edit.tsx, UserSidebar.tsx (preview sidebar user)
│   │   ├── Series/         Index.tsx (bulk delete, hover card, context menu), Create.tsx, Edit.tsx,
│   │   │                   Show.tsx, EditVolume.tsx
│   │   ├── Collections/    Index.tsx, Show.tsx
│   │   ├── Loans/          Index.tsx
│   │   ├── Users/          Index.tsx, Show.tsx
│   │   ├── Announcements/  Index.tsx, Create.tsx, Edit.tsx
│   │   ├── AniList/        Index.tsx (search & import — card overlay), Status.tsx
│   │   ├── RanobeDb/       Index.tsx (search & import light novel dari RanobeDB — card overlay,
│   │   │                   paralel dengan AniList/Index.tsx)
│   │   ├── Search/         Index.tsx (search gabungan AniList+RanobeDB, /admin/search-external)
│   │   ├── GenreFunfacts/  Index.tsx (kuota generate-ulang funfact per user — lihat/reset/override)
│   │   ├── Tickets/        Index.tsx, Show.tsx
│   │   ├── ActivityLog/    Index.tsx
│   │   └── Settings/       Index.tsx (tab Storage/Database/Konten/AI)
│   ├── User/
│   │   ├── Dashboard.tsx   (stat cards, chart Koleksi per Status, Carousel rekomendasi + Surprise Me,
│   │   │                   card Selera Genre — word cloud + funfact AI)
│   │   ├── Catalog/        Index.tsx, Show.tsx (avatar kolektor)
│   │   ├── Collection/     Index.tsx (grid poster auto-fill, datatable progres baca),
│   │   │                   Show.tsx (toggle baca, mode hapus, review & rating, format per-volume/bulk)
│   │   ├── Wishlist/       Index.tsx (series yang ingin dibaca, belum dikoleksi)
│   │   ├── Profile/        Show.tsx (profil publik /u/{user} — guest-accessible via PublicShell,
│   │   │                   follow/unfollow, koleksi publik non-klik untuk guest)
│   │   ├── Directory/      Index.tsx (direktori pengguna, butuh login, untuk follow)
│   │   ├── Tickets/        Index.tsx, Create.tsx, Show.tsx
│   │   └── Loans/          Index.tsx
│   ├── Auth/               Banned.tsx
│   ├── Settings/           Index.tsx (read-only profil dari SSO + kartu Bahasa + toggle profil publik)
│   ├── Error.tsx           (handle 403, 404, 500, 503)
│   ├── Maintenance.tsx
│   └── Landing.tsx
├── Layouts/
│   ├── AdminLayout.tsx     (sidebar + topbar admin, ScrollArea wrapping, mount CommandPalette,
│   │                         LanguageSwitcher di footer)
│   └── UserLayout.tsx      (sidebar + topbar search bar user, mount GlobalSearch,
│                             LanguageSwitcher di footer)
├── Components/
│   ├── ui/                 (shadcn/ui — JANGAN MODIFIKASI. Termasuk empty.tsx, hover-card.tsx,
│   │                        context-menu.tsx, command.tsx, chart.tsx, carousel.tsx)
│   └── app/
│       ├── VolumeGrid.tsx          (pure display, no toggle)
│       ├── AnnouncementBanner.tsx
│       ├── StatusBadge.tsx         (SeriesStatusBadge, SeriesTypeBadge, VolumeTypeBadge,
│       │                            TicketStatusBadge, TicketTypeBadge, VolumeFormatBadge — semua
│       │                            di-translate via t('badge.*'), fallback ke raw value)
│       ├── PageHeader.tsx          (responsive — stack kolom di mobile)
│       ├── EmptyState.tsx          (dipakai di halaman yang belum migrasi ke ui/empty.tsx)
│       ├── Pagination.tsx          (responsive; opsional per-page selector via prop routeName+filters)
│       ├── SeriesCard.tsx          (poster card katalog — cover, judul, badge)
│       ├── SeriesMediaGallery.tsx  (galeri media tambahan per series, admin)
│       ├── CommandPalette.tsx      (⌘K admin — nav cepat + search Series/Users/Tiket)
│       ├── GlobalSearch.tsx        (⌘K user — nav cepat + search Katalog/Koleksiku)
│       ├── SortableMenuList.tsx    (drag-drop reorder menu, @dnd-kit, dipakai Admin/Menus/Index.tsx)
│       └── LanguageSwitcher.tsx    (Popover ganti bahasa id/en/ja, di sidebar footer + Settings)
├── hooks/
│   └── useFlash.ts         (sonner toast dari flash session, dukung aksi "Undo" via
│                             flash.undo_url/undo_payload)
└── lib/
    ├── utils.ts
    ├── types.ts            (shared TypeScript interfaces, termasuk TicketType/TicketStatus)
    ├── i18n.ts             (init react-i18next — import statis semua namespace JSON id/en/ja,
    │                         lng dari shared Inertia prop `locale`)
    ├── menu.ts             (menuTranslationKey() — map key menu DB ke translation key `menu.*`)
    ├── typeFilters.ts      (useTypeFilterOptions() — opsi filter tipe series terjemahan, dipakai
    │                         di semua halaman list series/koleksi)
    └── puter.ts            (wrapper window.puter.ai.chat() — client-side AI generate untuk provider Puter)

resources/js/lang/{id,en,ja}/
    common.json, dashboard.json, user.json, catalog.json, collection.json, admin.json
    (resource JSON per-namespace untuk react-i18next, lihat §9)

lang/{id,en,ja}/
    validation.php, pagination.php
    (translation Laravel bawaan — pesan validasi & paginator otomatis ikut App::getLocale())
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

### RanobeDB REST API
- Endpoint: lihat [`docs/RANOBEDB_INTEGRATION.md`](RANOBEDB_INTEGRATION.md) untuk detail lengkap (tanpa auth, model tiga level Series → Books → Releases).
- Dipakai khusus untuk metadata **light novel** — paralel dengan AniList (yang tetap dipakai untuk manga/manhwa/manhua), diakses dari `Admin/RanobeDb/Index.tsx` + `RanobeDbController` + `RanobeDbService`.
- Staff di-split native jadi `authors` dan `illustrators` (kolom terpisah di `series`), tidak seperti AniList yang menggabungkan semua staff jadi satu daftar.
- Sync ulang metadata ke series yang sudah ada tersedia dari Popover "Sync RanobeDB" di halaman Edit Series (sama pola dengan "Sync AniList").
- Menangani sentinel tanggal `99999999` dari RanobeDB (artinya "ongoing/belum ditentukan") secara eksplisit di `RanobeDbService`, tidak di-parse sebagai tanggal literal.

### Puter.js (AI client-side)
- Provider default untuk fitur "Selera Genre" (funfact AI di dashboard user) — script `https://js.puter.com/v2/` di-load global lewat `resources/views/app.blade.php`, dipanggil dari `resources/js/lib/puter.ts` (`window.puter.ai.chat()`).
- Jalan sepenuhnya di browser user, gratis, tanpa API key server-side — beda dari provider Gemini/OpenAI/Claude yang butuh `api_key` tersimpan di tabel `ai_settings` dan generate terjadi server-side lewat `AiFunfactService`.
- Kalau tabel `ai_settings` belum punya row sama sekali, aplikasi default ke provider `puter`. Admin bisa ganti provider dari `/admin/settings` tab AI.
- Auto-generate (saat koleksi user bertambah signifikan) dan generate manual (tombol di dashboard, dibatasi kuota `GenreFunfact::DEFAULT_MANUAL_QUOTA` = 5/minggu, bisa di-override admin per user di `/admin/funfact-quota`) sama-sama didukung untuk provider Puter — hasil generate di browser dikirim balik ke server via `dashboard.funfact.auto-save`/`dashboard.funfact.regenerate` untuk disimpan ke `genre_funfacts`.
- Provider server-side (Gemini/OpenAI/Claude) yang membalas HTTP 429 (rate limit) dilempar sebagai `AiRateLimitException`, ditangkap khusus di `DashboardController` supaya funfact jatuh ke fallback text tanpa memotong kuota generate-ulang manual user.

---

## 9. Sistem Multi-Bahasa (id/en/ja)

- **Frontend:** `react-i18next`, resource JSON per-namespace di `resources/js/lang/{id,en,ja}/` (`common`, `dashboard`, `user`, `catalog`, `collection`, `admin`). Diregistrasi di `resources/js/lib/i18n.ts`. Dipakai lewat `useTranslation(namespace)` → `t('key')`, atau cross-namespace lewat `t('namespace:key')`.
- **Locale disimpan per-user:** kolom `users.locale` (default `id`), diubah dari kartu "Bahasa" di halaman Settings atau langsung dari `LanguageSwitcher.tsx` di sidebar footer (Admin & User Layout) — tidak perlu buka Settings.
- **Propagasi ke frontend:** middleware `SetLocale` set `App::setLocale()` server-side tiap request, `HandleInertiaRequests` share sebagai prop `locale`; `resources/js/app.tsx` baca prop ini sebelum render pertama (hindari flash bahasa salah), `AdminLayout`/`UserLayout` masing-masing punya `useEffect` yang watch perubahan `locale` untuk navigasi berikutnya.
- **Backend:** `lang/{id,en,ja}/validation.php` dan `lang/{id,en,ja}/pagination.php` — publish manual dari Laravel stock, supaya pesan validasi/paginator ikut bahasa aktif (sebelumnya selalu Inggris karena `lang/` belum pernah di-publish). `config/app.php` locale/fallback_locale default `id`.
- **Label menu sidebar** (dari tabel `menus`, teks Indonesia mentah di DB) di-map ke translation key lewat `menuTranslationKey()` (`resources/js/lib/menu.ts`), fallback ke label DB kalau key tidak dikenal (mis. admin rename manual).
- **Filter tipe series** (Segmented Control yang berulang di banyak halaman) pakai `useTypeFilterOptions()` (`resources/js/lib/typeFilters.ts`) supaya terjemahan tidak terduplikasi.
- **Cakupan saat ini:** lihat [`CLAUDE.md`](../CLAUDE.md) bagian "Sistem Multi-Bahasa" untuk daftar halaman yang sudah/belum diterjemahkan — daftar ini di-update tiap kali ada halaman baru yang selesai diterjemahkan.
