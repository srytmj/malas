# Malas — Claude Code Rules

## Meta Rules (BACA DULU SEBELUM APAPUN)

| Sinyal | Artinya |
|--------|---------|
| Pesan diakhiri **"gimana?"** | Ini diskusi. **Jangan sentuh kode.** Bahas rencana dulu. |
| User menulis **"lanjut" / "gas" / "oke"** | Baru boleh mulai implementasi. |
| Selesai 1 fase | Ingatkan user untuk buka QA chat (`lihat QA.md`). |

Untuk setiap rencana perubahan yang belum dapat konfirmasi → **tulis rencananya dulu, tunggu "gas".**

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| Frontend UI | React 19 + TypeScript 5 |
| Komponen UI | shadcn/ui (copy-paste via Base UI — gunakan `render` prop, bukan `asChild`) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database (dev) | SQLite |
| Database (prod) | MySQL 8+ |
| Auth/Role | Spatie Laravel Permission |
| File storage | Local (dev) atau S3-compatible/Cloudflare R2 (prod) — dikonfigurasi via UI admin, bukan `.env` |
| External API | AniList GraphQL (`https://graphql.anilist.co`) — untuk import metadata manga/manhwa/manhua |
| Auth SSO | whitearchive.id — PKCE-based OAuth2, semua user dikelola via SSO |

---

## Struktur Folder

```
app/
  Http/
    Controllers/
      Admin/
        AniListController.php         — search & import dari AniList GraphQL
        DatabaseBackupController.php  — download/import backup DB (super_admin)
        SeriesController.php
        VolumeController.php
        TicketController.php          — admin view & respond tiket dari user
        StorageSettingController.php  — konfigurasi storage driver via UI
        ActivityLogController.php     — viewer log aktivitas admin
        SiteSettingController.php     — pengaturan blur konten 18+
        SeriesMediaController.php     — galeri media tambahan per series
        CommandSearchController.php   — search Series/Users/Tickets untuk Command Palette (⌘K)
        [resource controllers lain]
      User/
        SeriesController.php          — katalog (read-only)
        CollectionController.php      — koleksi pribadi, bulk delete volumes, toggle/mark-all
                                         baca per volume, update review & rating
        TicketController.php          — user buat & lihat tiket
        LoanController.php
        SearchController.php          — search Series/Collection untuk Global Search (⌘K)
    Middleware/
      CheckMenuAccess.php   — cek maintenance mode & role_access per menu
      EnsureNotBanned.php
  Models/
    Series.php              — punya fields AniList: genres, authors, themes, demographics (json)
    Collection.php           — condition, personal_rating (-10..10), personal_review
    CollectionVolume.php     — read_at (datetime, null = belum dibaca)
    StorageSetting.php      — encrypted secret_access_key cast
    Ticket.php
    [model lain]
  Policies/
  Services/
    AniListService.php          — GraphQL client AniList (inject StorageSettingsService untuk download cover)
    StorageSettingsService.php  — satu pintu untuk semua operasi file storage (disk(), url(), store*, delete)

resources/js/
  Pages/
    Admin/
      Dashboard.tsx   — stat cards + chart (Recharts): Series per Status, Koleksi per Tipe,
                        Status Pinjaman
      AniList/
        Index.tsx     — search & import (card overlay, bukan Popover)
        Status.tsx    — ping AniList + recent imports
      Series/
        Index.tsx     — bulk delete, hover card preview, context menu (klik kanan)
        Edit.tsx      — termasuk "Sync AniList" Popover anchored ke tombol
      ActivityLog/
        Index.tsx     — viewer log aktivitas admin
      Settings/
        Index.tsx     — satu halaman bertab: Storage, Database, Konten (blur 18+)
      Tickets/
        Index.tsx
        Show.tsx      — admin bisa respond
    User/
      Dashboard.tsx   — stat cards, chart Koleksi per Status, Carousel rekomendasi + Surprise Me
      Catalog/
        Index.tsx
        Show.tsx      — detail lengkap: genre, author, theme, demographic, skor, avatar kolektor
      Collection/
        Index.tsx     — grid poster auto-fill (cover lebar), datatable progres baca, client-side search
        Show.tsx      — toggle baca per volume (icon mata), mode hapus (eye→checkbox),
                        review & rating pribadi, loan management
      Tickets/
        Index.tsx
        Create.tsx    — bisa diakses dari catalog (series pre-filled)
        Show.tsx
      Loans/
        Index.tsx
    Auth/
      Banned.tsx
    Landing.tsx
    Settings/
      Index.tsx       — read-only profil dari SSO
  Layouts/
    AdminLayout.tsx   — ScrollArea wrapping, mount <CommandPalette /> (⌘K)
    UserLayout.tsx    — search bar di header (desktop) + icon search (mobile), mount <GlobalSearch />
  Components/
    ui/               — shadcn/ui components (JANGAN MODIFIKASI). Termasuk empty.tsx, hover-card.tsx,
                        context-menu.tsx, command.tsx (cmdk), chart.tsx (Recharts), carousel.tsx
                        (embla-carousel-react)
    app/
      PageHeader.tsx  — responsive (stack kolom di mobile)
      Pagination.tsx  — responsive (flex-wrap di mobile); prop opsional routeName+filters
                        untuk selector per-page (5/10/25/50/100)
      VolumeGrid.tsx
      StatusBadge.tsx — SeriesStatusBadge, SeriesTypeBadge, VolumeTypeBadge,
                        TicketStatusBadge, TicketTypeBadge, VolumeFormatBadge
      EmptyState.tsx  — dipakai di halaman yang belum migrasi ke ui/empty.tsx
      AnnouncementBanner.tsx
      SeriesCard.tsx          — poster card katalog
      SeriesMediaGallery.tsx  — galeri media tambahan per series (admin)
      CommandPalette.tsx      — ⌘K admin, nav cepat + search
      GlobalSearch.tsx        — ⌘K user, nav cepat + search
  hooks/
    useFlash.ts       — sonner toast dari flash session; dukung tombol "Undo" via
                        flash.undo_url/undo_payload
  lib/
    utils.ts
    types.ts          — shared TypeScript types (TicketType, TicketStatus, dll)
```

---

## Aturan Coding

### TypeScript
- **Tidak boleh ada `any`** — gunakan `unknown` + type guard jika terpaksa
- Semua Inertia page props harus punya interface eksplisit
- Semua komponen harus punya typed props
- Gunakan `type` untuk object shapes, `interface` untuk yang bisa di-extend

```typescript
// BENAR
interface SeriesPageProps {
  series: Series[]
  filters: { status: string | null }
}

// SALAH
const handleData = (data: any) => { ... }
```

### React Components
- Semua komponen UI wajib pakai **shadcn/ui** — jangan buat custom dari nol
- shadcn/ui di project ini berbasis **Base UI** — gunakan `render` prop bukan `asChild`
- Form pakai **React Hook Form** + **Zod** untuk validasi
- Tidak ada inline style — semua pakai Tailwind utility class
- File komponen: `PascalCase.tsx`
- File hooks/utils: `camelCase.ts`
- Mobile-first: gunakan `flex-col sm:flex-row`, `flex-wrap` untuk action buttons, `min-h-0` pada flex children yang scroll
- **`DialogContent` (`ui/dialog.tsx`) base style-nya udah punya `sm:max-w-sm`** — override lebar WAJIB pakai prefix `sm:` juga (mis. `sm:max-w-3xl`), bukan `max-w-3xl` polos. Tanpa prefix `sm:`, class custom itu KALAH di cascade Tailwind begitu viewport ≥640px (media query `sm:max-w-sm` posisinya lebih akhir di stylesheet generated, menang lawan base utility non-prefixed) — dialog diam-diam tetap 384px padahal keliatan udah dikasih `max-w-*` yang lebih besar di kode. Bug nyata ketemu di `CollectionGroups/Show.tsx` (`Admin/Series/Edit.tsx` dan `User/Collection/Index.tsx` kemungkinan kena juga, belum dicek/di-fix — lihat `docs/PHASES.md` Phase 30).

```typescript
// BENAR — pakai shadcn/ui
import { Button } from "@/components/ui/button"
<Button variant="outline" onClick={handleSave}>Simpan</Button>

// SALAH — custom HTML
<button className="bg-blue-500 px-4 py-2">Simpan</button>
```

### Inertia Controller
- Setiap controller method return `Inertia::render()` atau `redirect()`
- Gunakan `Route::middleware(['auth', 'not_banned', 'check.menu'])` untuk semua route
- Data yang dikirim ke frontend harus minimal (jangan kirim seluruh model)

```php
// BENAR
public function index(): Response
{
    return Inertia::render('Admin/Series/Index', [
        'series' => SeriesResource::collection(Series::paginate(20)),
        'filters' => request()->only(['status', 'search']),
    ]);
}
```

### Laravel Backend
- Semua query pakai **Eloquent** — raw SQL hanya jika tidak bisa dihindari, dengan komentar alasannya
- Semua model punya `$fillable` eksplisit (tidak pakai `$guarded = []`)
- Semua action yang mengubah data harus lewat **Policy** dulu
- Validasi di **FormRequest**, bukan di controller langsung
- **Semua operasi file storage** (upload, delete, URL) harus lewat `StorageSettingsService`, bukan `Storage::` facade langsung
- **Nambah kolom `slug` (atau kolom lain yang wajib diisi tapi ditambah `nullable()` biar migration nggak gagal) ke tabel yang sudah punya data WAJIB backfill di migration yang sama**, bukan cuma ngandalin model event (`creating`/`updating`) buat ngisi baris baru — baris lama tetap NULL selamanya kalau nggak di-backfill, dan begitu frontend mulai generate URL/link pakai kolom itu, baris lama jadi rusak (link `null`) sampai blank screen. Pola yang benar: tambah kolom nullable → backfill pakai `Model::whereNull('kolom')->each(fn ($m) => ...->saveQuietly())` → (opsional) baru tambah constraint `unique()`. Lihat `2026_08_02_090000_add_slug_to_series_table.php` (Series, dibikin bener dari awal) vs `2026_08_15_090000_backfill_collection_groups_slug.php` (CollectionGroup, migration awal Phase 28 lupa langkah ini — ketemu sebagai bug nyata "blank screen" pas user coba akses grup lama yang dibikin sebelum migration slug jalan)

---

## Sistem Storage

Storage dikonfigurasi **via UI admin** (`/admin/settings/storage`), bukan `.env`. Data tersimpan di tabel `storage_settings` dengan `secret_access_key` ter-encrypt.

```php
// BENAR — inject StorageSettingsService
public function __construct(private StorageSettingsService $storage) {}
$url = $this->storage->url($model->cover_path);
$path = $this->storage->storeUploadedFile($file, 'covers');
$this->storage->delete($model->cover_path);

// SALAH — akses Storage facade langsung
Storage::disk('public')->url($path);
Storage::put('covers/' . $filename, $content);
```

---

## Sistem Otorisasi

```
Role: super_admin > admin > user

Akses dikontrol oleh:
1. Spatie Role       — untuk resource-level access (via Policy)
2. MenuMiddleware    — untuk route-level access (is_maintenance, role_access)
```

**Jangan pernah hardcode role check di component React.** Kirim permission dari backend:

```typescript
// BENAR — kirim dari controller
'can' => [
    'create_series' => $request->user()->can('create', Series::class),
]

// SALAH — cek di frontend
if (user.role === 'admin') { ... }
```

Pengecualian: `super_admin`-only features (Storage Settings, Database Backup) boleh pakai `abort_unless(auth()->user()->hasRole('super_admin'), 403)` langsung di controller karena tidak punya model yang relevan untuk Policy.

---

## Menu Management

Menu disimpan di database (tabel `menus`). Setiap route navigation harus punya entri di tabel menu.

`CheckMenuAccess` middleware dijalankan di setiap request:
1. Ambil menu berdasarkan route name saat ini
2. Jika `is_maintenance = true` DAN user bukan admin/super_admin → return halaman maintenance
3. Jika role user tidak ada di `role_access` → abort 403

Setiap menu baru harus ditambahkan ke `MenuSeeder.php` dengan `updateOrCreate`.

---

## UX — Wajib

- Semua tombol yang trigger server request harus punya **loading state** (disable + spinner)
- Gunakan `router.visit()` dengan `onStart`/`onFinish` untuk kontrol loading state
- Form error harus ditampilkan inline di bawah field, bukan hanya toast
- Toast/notification untuk success action (pakai `sonner` dari shadcn)
- Skeleton loading untuk data yang di-fetch (jangan blank page)
- **Mobile-first**: semua halaman user harus responsive — test dengan lebar 375px
- **Setiap aksi yang bisa dibalik (delete, toggle status, dll) WAJIB punya opsi Undo** di toast-nya —
  kirim `undo_url` (+ `undo_payload` kalau perlu data tambahan) lewat flash session, `useFlash.ts`
  otomatis nampilin tombol "Undo" yang manggil `router.patch(undo_url, undo_payload)`. Lihat contoh
  di `SeriesController::destroy()`/`restore()` (admin, soft-delete) atau `CollectionController::destroy()`/
  `undoDestroy()` (hard-delete, di-recreate dari payload). **Pengecualian**: aksi yang langsung menghapus
  file dari storage (mis. `SeriesMediaController::destroy()`) tidak wajib punya undo karena filenya sudah
  hilang permanen — kalau ini kejadian, dokumentasikan alasannya di komentar kode.
- **Setiap halaman yang menampilkan daftar/grid series atau koleksi (lebih dari satu item) WAJIB
  punya filter tipe** — pakai `ToggleGroup`/`ToggleGroupItem` (Segmented Control, bukan `Select`
  dropdown), urutan tombolnya selalu: **Semua Tipe, Manga, Light Novel, One Shot, Doujinshi, Manhwa,
  Manhua**. Base UI `Toggle` tidak menerima value `""` dengan bersih (fallback ke id auto-generate) —
  pakai sentinel `'all'`, bukan string kosong. Lihat contoh di `Admin/Series/Index.tsx`,
  `User/Catalog/Index.tsx`, `User/Collection/Index.tsx`, `User/Wishlist/Index.tsx`,
  `User/Profile/Show.tsx`, `Admin/Collections/Show.tsx`. **Pengecualian**: halaman yang isinya
  cuma satu tipe secara inheren (mis. `Admin/RanobeDb/Index.tsx` — RanobeDB cuma punya light novel)
  atau halaman detail satu series/volume (bukan daftar) tidak perlu filter ini.
- **Multi-bahasa WAJIB, tanpa terkecuali** — app ini mendukung id/en/ja (lihat bagian
  "Sistem Multi-Bahasa" di bawah). Setiap fitur atau kode baru yang menampilkan teks ke user
  **wajib** langsung disiapkan terjemahannya, tidak boleh hardcode string dan ditunda "nanti aja".
  Ini berlaku untuk SEMUA string user-facing: label, tombol, placeholder, toast, pesan error,
  empty state, judul halaman — tidak ada pengecualian. Pastikan tidak ada string yang
  terlewat sebelum menganggap sebuah fitur selesai.

---

## Sistem Multi-Bahasa

Didukung: **id** (default), **en**, **ja**. Preferensi bahasa disimpan per-user di kolom
`users.locale`, diubah lewat halaman Settings (`Select` di kartu "Bahasa"/"Language"/"言語").

**Frontend** (`react-i18next`):
- File terjemahan: `resources/js/lang/{id,en,ja}/{namespace}.json` — dipecah per namespace
  (`common.json` untuk Layouts/SidebarNav/CommandPalette/GlobalSearch/menu label, `dashboard.json`
  untuk halaman Dashboard, dst). Tambah namespace baru untuk halaman baru, jangan numpuk semua di
  `common.json`.
- Registrasi resource JSON baru di `resources/js/lib/i18n.ts` (import statis + tambah ke object
  `resources` dan array `ns`).
- Pakai `const { t } = useTranslation('namespace')` lalu `t('key')` — bukan string literal.
  Interpolasi pakai `{{variable}}` di JSON, lalu `t('key', { variable: value })`.
- Locale ikut ke browser otomatis lewat shared Inertia prop `locale` (di-set `i18n.changeLanguage()`
  di `useEffect` pada `AdminLayout`/`UserLayout` — halaman lain otomatis ikut karena semua
  dibungkus salah satu Layout ini).
- **Label menu sidebar** (dari tabel `menus`, hasil seed) **jangan** dirender langsung dari
  `item.label` (itu cuma bahasa Indonesia mentah di DB) — map dulu lewat `menuTranslationKey(item.key)`
  di `resources/js/lib/menu.ts` ke translation key `menu.*`, fallback ke `item.label` kalau key-nya
  nggak dikenal (mis. admin rename manual). Setiap menu baru yang di-seed WAJIB ditambahkan ke
  `MENU_KEY_TRANSLATIONS` + key `menu.*` di ketiga `common.json`.

**Backend**:
- Middleware `App\Http\Middleware\SetLocale` set `App::setLocale()` dari `user->locale` tiap
  request (default `id` untuk guest/user tanpa preferensi).
- Pesan validasi Laravel sudah terjemah otomatis lewat `lang/{id,en,ja}/validation.php` — nggak
  perlu custom message manual di FormRequest kecuali butuh teks di luar rule bawaan Laravel.
- Flash message controller sudah multi-bahasa lewat `lang/{id,en,ja}/flash.php` — controller manggil
  `__('flash.namespace.key', ['param' => $value])`, bukan hardcode string. Semua ~70 pemanggilan
  `->with('success'/'error'/'info', ...)` di 24 controller sudah dikonversi. **Wajib pakai pola ini
  buat flash message baru** — jangan hardcode string lagi. Placeholder pakai `:key` (mis. `:count`,
  `:name`, `:number`) sesuai konvensi Laravel `__()`. Pengecualian yang SENGAJA tidak diterjemahkan:
  pesan exception mentah (`$e->getMessage()`, umumnya dari API eksternal/AWS SDK) dan teks di dalam
  `ActivityLog::record()` (log aktivitas admin, bukan flash toast — tetap Indonesia karena dibaca
  admin, bukan user-facing per-locale).

**Sudah diterjemahkan penuh** (semua namespace: `common.json`, `dashboard.json`, `user.json`,
`catalog.json`, `collection.json`, `admin.json` — lihat `resources/js/lang/{id,en,ja}/`):

- Layouts & komponen shared: `AdminLayout.tsx`, `UserLayout.tsx`, `SidebarNav.tsx`,
  `CommandPalette.tsx`, `GlobalSearch.tsx`, `StatusBadge.tsx`, `Pagination.tsx`, `LanguageSwitcher.tsx`.
- Semua halaman `User/**`: `Dashboard.tsx`, `Catalog/Index.tsx`, `Catalog/Show.tsx`,
  `Collection/Index.tsx`, `Collection/Show.tsx` (file terbesar di app, ~1200 baris — full),
  `Wishlist/Index.tsx`, `Tickets/Index.tsx`, `Tickets/Create.tsx`, `Tickets/Show.tsx`,
  `Loans/Index.tsx`, `Directory/Index.tsx`, `Profile/Show.tsx`.
- `Settings/Index.tsx` — full (kartu Bahasa, Profil, Profil Publik).
- Semua halaman `Admin/**`: `Dashboard.tsx`, `ActivityLog/Index.tsx`, `Series/Edit.tsx`,
  `Series/Show.tsx`, `Series/Create.tsx`, `Series/Index.tsx`, `Series/EditVolume.tsx`,
  `Settings/Index.tsx`, `Users/Index.tsx`, `Users/Show.tsx`, `Announcements/Create.tsx`,
  `Announcements/Edit.tsx`, `Announcements/Index.tsx`, `Tickets/Index.tsx`, `Tickets/Show.tsx`,
  `Loans/Index.tsx`, `Menus/Edit.tsx`, `Menus/Index.tsx`, `Menus/UserSidebar.tsx`,
  `GenreFunfacts/Index.tsx`, `AniList/Index.tsx`, `AniList/Status.tsx`, `Collections/Index.tsx`,
  `Collections/Show.tsx`, `RanobeDb/Index.tsx`, `Search/Index.tsx`.
- Halaman root `Pages/`: `Landing.tsx`, `Error.tsx`, `Auth/Banned.tsx`, `Maintenance.tsx`.
- `Components/app/**` (di luar `ui/`): `SeriesMediaGallery.tsx`, `SortableMenuList.tsx`,
  `AdultBlurOverlay.tsx`, `AnnouncementBanner.tsx`, `SeriesCard.tsx`, `VolumeGrid.tsx`.

**Belum lengkap (backlog, jangan anggap selesai — update daftar ini tiap kali menerjemahkan
sebuah file, dan JANGAN hapus baris kalau baru diterjemahkan sebagian)**:

- `User/Dashboard.tsx` — 1 label chart tersisa (`statusChartConfig.total.label`), prioritas
  rendah karena kata "Series" sama di ID/EN.
- `Pages/Dashboard.tsx` (root, di luar `User/` dan `Admin/`) — dicek, tidak direferensikan oleh
  controller manapun (`grep Inertia::render('Dashboard'` nihil hasil), jadi ini file mati.
  Sengaja **tidak** diterjemahkan — pertimbangkan untuk dihapus di kesempatan lain, bukan
  diterjemahkan.

**Pola yang sudah mapan, ikuti kalau nerusin backlog di atas**:
- String tipe/status/format series yang berulang di banyak file → pakai key `common.json`
  (`badge.status.*`, `badge.type.*`, `badge.format.*`, dst), jangan bikin key baru per halaman.
- Filter tipe segmented control yang berulang (lihat aturan wajib di atas) → pakai hook
  `useTypeFilterOptions()` dari `resources/js/lib/typeFilters.ts`, jangan duplikasi array literal.
- Cross-namespace lookup pakai syntax `t('namespace:key')`, mis. `t('common:badge.status.finished')`
  dari komponen yang default namespace-nya bukan `common`.

---

## Fitur yang Sudah Ada

Jangan duplikasi atau rebuild ulang fitur-fitur ini:

| Fitur | Lokasi |
|-------|--------|
| Import manga dari AniList | `Admin/AniList/Index.tsx` + `AniListController` |
| Batch import AniList (filter genre/tahun/popularitas, multi-select) | `Admin/AniList/Index.tsx` + `AniListController::bulkImport()` + `AniListService::getMangaBatch()` (satu request GraphQL `id_in`, bukan N request terpisah) |
| Buka series di tab baru dari context menu (klik kanan) | `Admin/Series/Index.tsx` — `window.open()`, terpisah dari navigasi SPA Inertia biasa |
| Sync metadata AniList ke series yang ada | Edit Series page (Popover "Sync AniList") |
| Koleksi pribadi + volume tracking | `User/Collection/*` |
| Bulk delete volumes | `Collection/Show.tsx` + `CollectionController::destroyVolumes()` |
| Bulk delete series (admin) | `Admin/Series/Index.tsx` + `SeriesController::bulkDestroy()` |
| Input volume dengan range (`1-5,7,9-12`) | `CollectionController::storeVolumes()` |
| Toggle baca per volume + tandai semua sekaligus | `Collection/Show.tsx` + `CollectionController::toggleVolumeRead()`/`markAllVolumesRead()` |
| Stepper progres baca (+/-) — geser batas volume terbaca tanpa klik ikon mata satu-satu | `Collection/Show.tsx` + `CollectionController::advanceReadProgress()` |
| Quick-edit jumlah volume dimiliki per format (+/-) | `Collection/Show.tsx` + `CollectionController::quickAdjustCount()` — skip volume yang lagi dipinjamkan (disable + tooltip di frontend, ditolak juga di server) |
| Mode hapus volume (icon mata → checkbox) | `Collection/Show.tsx` |
| Review & rating pribadi (-10..10) | `Collection/Show.tsx` + `CollectionController::updateReview()` |
| Rekomendasi genre + Surprise Me | `User/Dashboard.tsx` + `DashboardController::genreRecommendations()`/`surpriseMe()` |
| Dashboard charts (Recharts) | `Admin/Dashboard.tsx`, `User/Dashboard.tsx` + `ui/chart.tsx` |
| Command Palette admin (⌘K) | `CommandPalette.tsx` + `Admin/CommandSearchController` |
| Global Search user (⌘K) | `GlobalSearch.tsx` + `User/SearchController` |
| Selector jumlah data per halaman (5/10/25/50/100) | `Pagination.tsx` + `Controller::perPage()` |
| Undo pada toast (aksi reversible) | `useFlash.ts` (flash `undo_url`/`undo_payload`) |
| Sistem tiket user → admin | `User/Tickets/*`, `Admin/Tickets/*` |
| Peminjaman volume | `User/Loans/*` + `Admin/Loans` |
| Backup & import database | `Admin/Settings/Index.tsx` (tab Database) + `DatabaseBackupController` |
| Konfigurasi storage (Local/S3) | `Admin/Settings/Index.tsx` (tab Storage) + `StorageSettingController` |
| Blur konten 18+ | `Admin/Settings/Index.tsx` (tab Konten) + `SiteSettingController` |
| Log aktivitas admin | `Admin/ActivityLog/Index.tsx` + `ActivityLogController` |
| Galeri media tambahan per series | `SeriesMediaGallery.tsx` + `Admin/SeriesMediaController` |
| SSO login via whitearchive.id | `SsoController` (PKCE OAuth2) |
| Import metadata light novel dari RanobeDB | `Admin/RanobeDb/Index.tsx` + `RanobeDbController` + `RanobeDbService`, lihat [`docs/RANOBEDB_INTEGRATION.md`](docs/RANOBEDB_INTEGRATION.md) |
| Sync metadata RanobeDB ke series yang ada | Edit Series page (Popover "Sync RanobeDB") |
| Selera Genre — word cloud + funfact AI (Gemini/OpenAI/Claude, admin wajib isi API key) | `User/Dashboard.tsx` (`GenreFunfactCard`) + `DashboardController::regenerateFunfact()` + `AiFunfactService`, config provider di `Admin/Settings/Index.tsx` (tab AI). Kalau API key belum diisi, `AiFunfactService::fallbackText()` otomatis dipakai (teks statis dari data genre, bukan AI beneran) — **bukan** lagi Puter.js (dihapus, lihat `docs/PHASES.md` Phase 31) |
| Kuota generate-ulang Selera Genre (admin) | `Admin/GenreFunfacts/Index.tsx` + `GenreFunfactController` |
| Wishlist (series belum dikoleksi) | `User/Wishlist/Index.tsx` + `WishlistController` |
| Profil publik (opt-in, bisa diakses tanpa login) + follow | `User/Profile/Show.tsx` (`PublicShell` untuk guest) + `ProfileController`, toggle di `Settings/Index.tsx` |
| Direktori pengguna | `User/Directory/Index.tsx` + `ProfileController::directory()` |
| Search gabungan AniList + RanobeDB (admin) | `Admin/Search/Index.tsx` + `ExternalSearchController` |
| Reorder menu sidebar (drag & drop) | `Admin/Menus/Index.tsx` (`SortableMenuList.tsx`, `@dnd-kit`) + `AdminMenuController::reorder()`, preview di `Admin/Menus/UserSidebar.tsx` |
| URL katalog berbasis judul (slug, bukan UUID) | `Series::generateUniqueSlug()`/`resolveRouteBinding()` — fallback ke `id` untuk link lama |
| Modal pilihan login (SSO / Email) | `LoginMethodDialog.tsx` — dipakai dari tombol Login di Landing page; login lewat email tidak sync ulang profil (cuma SSO yang sync) |
| Login dengan Email (magic link, peer method — bukan cuma fallback) | `Auth/SsoFallback.tsx` + `Auth\SsoFallbackController` — magic link sekali-pakai lewat email, `throttle:5,10`, generic response (anti email-enumeration) |
| Login darurat via CLI (tanpa nunggu email) | `php artisan sso:emergency-login {identifier=super_admin}` (`IssueEmergencyLoginLink.php`) — reuse `SsoFallbackToken`, butuh akses SSH ke server, lihat `docs/DEPLOYMENT.md` |
| Konfigurasi Email (Resend) | `Admin/Settings/Index.tsx` tab Email + `Admin\MailSettingController` + `MailSettingsService` — API key ter-encrypt, sama pola dengan Storage/AI |
| Editor genre/authors/illustrators/themes/demographics di Admin Series Edit | `Admin/Series/Edit.tsx` (`TagListInput.tsx`) — sync AniList/RanobeDB ikut ngisi tag, sentinel string kosong buat hapus semua tag lewat `SeriesController::update()` |
| URL admin series berbasis judul (slug, bukan UUID) | Sama mekanisme dengan katalog user — semua `route('admin.series.show'/'edit', ...)` (Index, Show, EditVolume, AniList/RanobeDB/Search results, Tickets, Command Palette) pakai `slug` |
| Filter genre searchable + multi-select di Katalog user | `User/Catalog/Index.tsx` (`GenreMultiSelect.tsx`, Popover+Command/cmdk) + `User\SeriesController::index()` — OR-match (`orWhereJsonContains`), genre dikirim sebagai array (`genre[]=...`) |
| Multi-account switching (session-based, kepake semua user) | `AccountSwitcher.tsx` (dropdown avatar di sidebar footer Admin/User) + `AccountLinkService`/`Auth\AccountController` — daftar akun ke-link cuma hidup di session (`linked_account_ids`), **tidak** ada link permanen di DB (switch tetap selalu butuh bukti login beneran ke akun target sekali). "Tambah Akun" reuse `LoginMethodDialog` (`mode="link"`, cuma ganti copy teks). Keputusan link-atau-replace di `AccountLinkService::loginAs()` **berdasarkan status login SAAT link dikonsumsi** (`auth()->check()`), bukan flag dari UI — supaya magic link yang diterbitkan lewat CLI (`sso:emergency-login`) atau diklik dari email (yang nggak pernah lewat modal "Tambah Akun" sama sekali) tetap nambah ke daftar ke-link, bukan override akun yang lagi aktif. Logout `POST /accounts/logout-current` cuma keluarin akun aktif (auto-switch ke akun ke-link lain kalau ada; kalau ini akun terakhir, delegasi ke `SsoController::logout()`) — `POST /logout` tetap logout semua akun sekaligus. |
| Grup koleksi custom ala MDList MangaDex (mis. "RomCom") | `CollectionGroup`/`CollectionGroupController` — many-to-many (`collection_group_items` pivot, satu manga bisa masuk beberapa grup), halaman terpisah `User/CollectionGroups/Index.tsx` (daftar grup + cover collage) dan `Show.tsx` (isi grup + dialog tambah manga dari koleksi, lebar `sm:max-w-7xl`, infinite scroll berbasis `onScroll` — bukan `<Pagination>` — buat browsing koleksi besar, plus filter tipe). URL grup pakai slug (`{username}-{nama-grup}`, lihat `CollectionGroup::generateUniqueSlug()`), bukan UUID. Grup bisa di-toggle publik/privat (`Switch` di halaman detail) — grup publik muncul di profil publik pemilik dan bisa diakses guest lewat `PublicShell` (komponen bersama, juga dipakai `Profile/Show.tsx`). **Bukan** kolom string tunggal per koleksi (desain awal salah, diganti total) |
| Stepper progres baca dari halaman Koleksiku (tanpa buka detail koleksi) | `Collection/Index.tsx` (`ReadStepper`) — reuse endpoint `collection.volumes.readProgress` yang sama dengan halaman detail, per-row loading state biar nggak saling blocking |
| Flash message multi-bahasa | `lang/{id,en,ja}/flash.php` — semua `->with('success'/'error'/'info', ...)` di seluruh controller (~70 pemanggilan, 24 file) pakai `__('flash.xxx', [...])`, bukan hardcode string |

**Belum dikerjakan (backlog):**
- Activity feed di profil publik (community hub, gaya Steam) — profil publik + follow sudah ada, activity feed-nya masih ditunda.
- Badge/label selera genre ("Genre Explorer" vs "Genre Loyalist") berdasar distribusi genre koleksi — ditunda, numpang di data yang sama dengan fitur Selera Genre (word cloud + funfact AI).
- Advanced filter batch import AniList: multi-select genre (sekarang masih single-select), filter tag (`tag_in` — AniList punya ~470 tag, butuh combobox searchable, bukan dropdown statis), dan filter status (`status_in`, publishing/finished/hiatus/dll). Sudah didiskusikan dan diverifikasi lewat API langsung (`tag_in`/`status_in` keduanya jalan buat manga), tapi belum di-"gas".
- Verifikasi visual manual buat modal `LoginMethodDialog` — kode sudah `tsc`-clean dan direview, tapi belum di-klik langsung di browser (lihat PHASES.md Phase 18 buat detail kenapa).

---

## Dilarang

- `any` di TypeScript
- Inline CSS style (`style={{ color: 'red' }}`)
- Custom HTML komponen jika sudah ada di shadcn/ui
- Hardcode role/permission di frontend
- Kirim data sensitif yang tidak perlu ke Inertia props
- Commit `.env` atau file yang mengandung secrets
- Skip middleware atau bypass policy
- `dd()` atau `var_dump()` di production code
- Akses `Storage::` facade langsung di luar `StorageSettingsService`
- Import dari `JikanService` (sudah dihapus — ganti dengan `AniListService`)
- Render halaman yang bisa diakses guest (route di luar middleware `auth`, mis. profil publik atau grup koleksi publik) langsung pakai `UserLayout`/`AdminLayout` — keduanya asumsi ada sesi login (`AccountSwitcher.tsx` akses `auth.user!`, non-null assertion) dan **crash total jadi blank screen** kalau `auth.user` null. Kirim flag `is_guest`/`is_owner` dari controller, lalu `const Layout = is_guest ? PublicShell : UserLayout` (lihat `Profile/Show.tsx`, `CollectionGroups/Show.tsx`) — ditemukan sebagai bug nyata di Phase 29 karena verifikasi sebelumnya cuma ngecek response JSON Inertia lewat curl, bukan render React beneran di browser.
