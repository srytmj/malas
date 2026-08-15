# ARCHITECTURE — Malas

**Versi:** 3.5
**Diperbarui:** 2026-08-03

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
| Email | Resend (`resend/resend-php`) | v1 | Magic link login tanpa SSO — dikonfigurasi via UI admin (`mail_settings`), bukan `.env` |

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
| theme | string(10) | default `system` — `light`/`dark`/`system`, pola sync sama seperti `locale` (`PATCH /settings/theme`) |
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
| slug | string unique | auto-generated dari `title_romaji` (`Str::slug()` dengan dictionary kosong — simbol seperti `@`/`!`/koma/titik dua dibuang langsung, bukan dikonversi jadi kata; full judul dipakai tanpa dipotong walau panjang) — dipakai untuk URL katalog user (`/catalog/{slug}`) **dan** URL series admin (`/admin/series/{slug}`, termasuk link dari hasil AniList/RanobeDB/Search dan Command Palette), regenerate otomatis kalau `title_romaji` berubah, tambah suffix `-2`/`-3` kalau bentrok. Route model binding coba slug dulu, fallback ke id (link lama tetap jalan). |
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
| group_name | string(100) | nullable — label bebas per user (mis. "Rak Kamar"), bukan tabel terpisah |
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

### `mail_settings`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| provider | string | default `resend` — satu-satunya provider yang didukung saat ini |
| api_key | text | nullable — **ter-encrypt** via Eloquent cast |
| from_address | string | nullable — alamat pengirim, fallback ke placeholder kalau kosong |
| from_name | string | nullable — nama pengirim, fallback ke `config('app.name')` kalau kosong |
| created_at / updated_at | timestamp | |

Single-row table, dikelola dari `/admin/settings` tab Email. Dipakai `MailSettingsService::send()` yang set config `services.resend.key`/`mail.from.*` secara runtime sebelum kirim — pola sama dengan `StorageSettingsService::disk()`. Kalau `api_key` kosong, `isConfigured()` return false dan fitur yang bergantung ke email (login tanpa SSO) diam-diam skip pengiriman tanpa error ke user.

### `sso_fallback_tokens`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | uuid PK | |
| user_id | uuid FK | cascade delete |
| token_hash | string unique | SHA-256 hash dari token mentah — token asli cuma ada di link email, sama pola dengan `password_reset_tokens` bawaan Laravel |
| expires_at | timestamp | 15 menit dari saat diterbitkan (`SsoFallbackToken::issueFor()`) |
| used_at | timestamp | nullable — diisi begitu token dipakai, mencegah reuse |
| created_at / updated_at | timestamp | |

Dipakai fitur "Login dengan Email" — lihat §8.

---

## 3. Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── MenuController.php
│   │   │   ├── SeriesController.php        (termasuk bulkDestroy; edit()/update() kirim & terima
│   │   │   │                                genres/authors/illustrators/themes/demographics)
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
│   │   │   ├── GenreFunfactController.php  (kelola kuota generate-ulang funfact per user, /admin/funfact-quota)
│   │   │   └── MailSettingController.php   (konfigurasi provider Email: Resend + api_key/from_address/from_name)
│   │   ├── User/
│   │   │   ├── DashboardController.php     (rekomendasi genre + Surprise Me + funfact AI: regenerate/
│   │   │   │                                auto-save/report-error/genre-detail)
│   │   │   ├── SeriesController.php        (katalog, read-only + search endpoint; filter genre
│   │   │   │                                multi-select OR-match lewat whereJsonContains berantai)
│   │   │   ├── CollectionController.php    (destroyVolumes bulk, range parsing, toggle/mark-all
│   │   │   │                                read, update review/rating, format per-volume/bulk,
│   │   │   │                                advanceReadProgress stepper, quickAdjustCount per-format)
│   │   │   ├── TicketController.php        (user buat & lihat tiket)
│   │   │   ├── LoanController.php
│   │   │   ├── SearchController.php        (search Series + Collection untuk Global Search)
│   │   │   ├── ProfileController.php       (profil publik /u/{user} — guest-accessible, follow/unfollow,
│   │   │   │                                direktori pengguna)
│   │   │   └── WishlistController.php      (series yang ingin dibaca, belum dikoleksi)
│   │   └── Auth/
│   │       ├── SsoController.php           (PKCE OAuth2 redirect/callback/logout)
│   │       └── SsoFallbackController.php   (login tanpa SSO — magic link email sekali-pakai,
│   │                                        dipakai kalau whitearchive.id tidak bisa diakses)
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
│   ├── GenreFunfact.php     (DEFAULT_MANUAL_QUOTA = 5, quota_override per user)
│   ├── MailSetting.php      (encrypted api_key cast, single-row)
│   └── SsoFallbackToken.php (token_hash SHA-256, single-use, TTL 15 menit)
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
    ├── MailSettingsService.php    (set config Resend runtime dari mail_settings, satu pintu kirim email —
    │                                pola sama dengan StorageSettingsService::disk())
    ├── StorageSettingsService.php (satu pintu untuk semua operasi file storage)

app/Mail/
└── SsoFallbackLoginMail.php   (magic link login tanpa SSO, view: resources/views/emails/sso-fallback-login.blade.php)

app/Console/Commands/
└── IssueEmergencyLoginLink.php (`sso:emergency-login` — terbitkan SsoFallbackToken dari CLI, buat admin
    yang butuh akses cepat lewat SSH tanpa nunggu email; lihat docs/DEPLOYMENT.md)

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
│   │   └── Settings/       Index.tsx (tab Storage/Database/Konten/AI/Email)
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
│   ├── Auth/               Banned.tsx, SsoFallback.tsx (login tanpa SSO — form email + status kirim)
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
│       ├── LanguageSwitcher.tsx    (Popover ganti bahasa id/en/ja, di sidebar footer + Settings;
│       │                            guest-safe — skip persist kalau belum login)
│       ├── ThemeSwitcher.tsx       (Popover Light/Dark/System, pola identik LanguageSwitcher;
│       │                            resolvedTheme via matchMedia, live-update saat OS ganti tema)
│       ├── TagListInput.tsx        (editor tag bebas — Enter/koma nambah, klik-X hapus; dipakai
│       │                            genres/authors/illustrators/themes/demographics di Series Edit)
│       ├── GenreMultiSelect.tsx    (filter genre searchable + multi-select, Popover+Command/cmdk,
│       │                            grouped manga/novel; dipakai di Catalog/Index.tsx)
│       └── LoginMethodDialog.tsx   (modal pilihan SSO/Email — dipasang di Landing page dan
│                                    PublicShell/CTA follow di Profile/Show.tsx buat guest)
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
    flash.php
    (~70 flash message dari seluruh controller, key nested per fitur — dipanggil lewat
    __('flash.xxx', [...]), lihat §9)
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
- **Batch import** (`AniListController::bulkImport()`): filter genre (`genre_in`) + tahun rilis + sort popularitas dilewatkan ke `AniListService::searchManga()`. **Penting**: `seasonYear` di skema AniList adalah konsep musim tayang anime dan selalu balikin array kosong untuk `type: MANGA` — diverifikasi langsung ke API sebelum dipakai. Filter tahun untuk manga harus lewat rentang `startDate_greater`/`startDate_lesser` (`FuzzyDateInt`, format `YYYYMMDD`: `gt = {tahun}0000`, `lt = {tahun+1}0000`). `AniListService::getMangaBatch()` ambil sampai 50 series sekaligus dalam satu request GraphQL (`media(id_in: [...])`) — bukan N request terpisah, penting buat menghemat kuota rate-limit AniList (~90 req/menit).

### SSO whitearchive.id
- PKCE-based OAuth2. Semua user (termasuk admin) login lewat SSO — tidak ada form register/login lokal.
- Flow: `/auth/redirect` → whitearchive.id → `/auth/callback` (`SsoController`) → user dibuat/diupdate dari klaim SSO (`sso_id`, `name`, `username`, `email`, `avatar`) → session dibuat.
- Halaman `Settings/Index.tsx` menampilkan profil secara read-only (data profil dikelola di sisi SSO, bukan di Malas).
- `SsoController::curlRequest()` pakai curl langsung (bukan `Http::` facade Laravel/Guzzle) — di environment ini `curl_multi` milik Guzzle intermiten hang di PHP-FPM, blocking `curl_exec` lebih reliable.

### Login dengan Email (magic link)
- Awalnya dibangun sebagai fallback darurat kalau whitearchive.id tidak bisa diakses, sekarang dipromosikan jadi **opsi login setara SSO** — dipilih dari `LoginMethodDialog.tsx` (modal "Masuk ke Malas" yang muncul saat klik tombol Login di Landing page), bukan cuma link kecil tersembunyi. Mekanisme backend tidak berubah sama sekali dari versi fallback-nya.
- Flow: `LoginMethodDialog` (atau langsung `/auth/fallback`) → user isi email → `POST /auth/fallback` (`throttle:5,10`, dinaikkan dari `3,15` setelah dipromosikan jadi opsi harian) → kalau email cocok dengan user yang ada DAN `mail_settings` terkonfigurasi, terbitkan `SsoFallbackToken` (TTL 15 menit, single-use) dan kirim magic link lewat email (`SsoFallbackLoginMail`) → user klik link → `GET /auth/fallback/{token}` (`SsoFallbackController::consume`) → `Auth::login()` langsung, redirect sesuai role.
- **Trade-off yang disengaja**: profil (nama/avatar/username) cuma ikut ke-sync ulang dari SSO pas login lewat SSO — user yang selalu login lewat email tidak dapat update profil otomatis.
- **Anti email-enumeration**: response `POST /auth/fallback` SELALU pesan generik yang sama ("kalau email terdaftar, link sudah dikirim") baik email-nya valid/tidak/user banned — tidak pernah membocorkan status akun lewat response.
- Kegagalan kirim email (mis. Resend down, API key salah) ditangkap try/catch, tidak bikin request 500 — dicatat ke `ActivityLog` (action `auth.fallback_mail_error`) dan log Laravel biasa, user tetap dapat response generik yang sama.
- `ActivityLog::record()` di-fallback ke ID user subject kalau tidak ada user yang login (`auth()->id()` null) — dibutuhkan khusus buat flow ini karena request datang dari guest, bukan dari user/admin yang sudah authenticated seperti aksi lain yang dicatat log aktivitas.
- Token disimpan **ter-hash** (SHA-256) di `sso_fallback_tokens`, token mentah cuma ada di link email — sama pola dengan `password_reset_tokens` bawaan Laravel.
- **Jalur kedua (CLI)**: `php artisan sso:emergency-login {identifier=super_admin}` (`app/Console/Commands/IssueEmergencyLoginLink.php`) — reuse `SsoFallbackToken::issueFor()` yang sama, cuma diterbitkan dari terminal (butuh akses SSH ke server) bukan dari form. Berguna kalau mail service belum dikonfigurasi atau butuh akses instan tanpa nunggu email. Panduan pakai lengkap: [`docs/DEPLOYMENT.md`](DEPLOYMENT.md).

### Resend (Email)
- Provider email satu-satunya yang didukung saat ini, dikonfigurasi dari `/admin/settings` tab Email (`mail_settings` table, api_key ter-encrypt) — bukan `.env`.
- `MailSettingsService::send()` set config `services.resend.key`/`mail.from.*` secara runtime sebelum kirim (pola sama dengan `StorageSettingsService::disk()` yang bangun disk S3 ad-hoc dari kredensial DB), lalu `Mail::mailer('resend')->send(...)`.
- Paket `resend/resend-php` (native Laravel `resend` mail transport, `config/mail.php` sudah punya stub-nya bawaan Laravel 12).
- Kalau `api_key` belum diisi, `MailSettingsService::isConfigured()` return false — fitur yang bergantung ke email (login tanpa SSO) diam-diam skip pengiriman, tidak error ke user.

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
- **Flash message:** `lang/{id,en,ja}/flash.php` — semua `->with('success'/'error'/'info', ...)` di seluruh controller (~70 pemanggilan, 24 file) manggil `__('flash.namespace.key', ['param' => $value])`, bukan hardcode string. Placeholder Laravel `:key` (`:count`, `:name`, `:number`, dst) — dikonfirmasi `:count` aman dipakai sebagai placeholder biasa di `__()` (bukan `trans_choice()`, jadi tidak memicu logic pluralisasi). Pengecualian yang sengaja tidak diterjemahkan: pesan exception mentah (`$e->getMessage()`, umumnya dari API eksternal) dan teks di `ActivityLog::record()` (log aktivitas admin, tetap Indonesia — bukan flash toast per-locale).
- **Label menu sidebar** (dari tabel `menus`, teks Indonesia mentah di DB) di-map ke translation key lewat `menuTranslationKey()` (`resources/js/lib/menu.ts`), fallback ke label DB kalau key tidak dikenal (mis. admin rename manual).
- **Filter tipe series** (Segmented Control yang berulang di banyak halaman) pakai `useTypeFilterOptions()` (`resources/js/lib/typeFilters.ts`) supaya terjemahan tidak terduplikasi.
- **Cakupan saat ini:** lihat [`CLAUDE.md`](../CLAUDE.md) bagian "Sistem Multi-Bahasa" untuk daftar halaman yang sudah/belum diterjemahkan — daftar ini di-update tiap kali ada halaman baru yang selesai diterjemahkan.
