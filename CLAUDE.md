# MALAS — Claude Code Rules

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
        [resource controllers lain]
      User/
        SeriesController.php          — katalog (read-only)
        CollectionController.php      — koleksi pribadi + bulk delete volumes
        TicketController.php          — user buat & lihat tiket
        LoanController.php
    Middleware/
      CheckMenuAccess.php   — cek maintenance mode & role_access per menu
      EnsureNotBanned.php
  Models/
    Series.php              — punya fields AniList: genres, authors, themes, demographics (json)
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
      AniList/
        Index.tsx     — search & import (card overlay, bukan Popover)
        Status.tsx    — ping AniList + recent imports
      Series/
        Edit.tsx      — termasuk "Sync AniList" Popover anchored ke tombol
      Settings/
        Storage.tsx   — konfigurasi Local vs S3
        Database.tsx  — download backup + import (super_admin)
      Tickets/
        Index.tsx
        Show.tsx      — admin bisa respond
    User/
      Catalog/
        Index.tsx
        Show.tsx      — detail lengkap: genre, author, theme, demographic, skor, dll
      Collection/
        Index.tsx     — dengan client-side search
        Show.tsx      — bulk delete volumes, loan management
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
    AdminLayout.tsx   — ScrollArea wrapping, ICON_MAP termasuk Database icon
    UserLayout.tsx
  Components/
    ui/               — shadcn/ui components (JANGAN MODIFIKASI)
    app/
      PageHeader.tsx  — responsive (stack kolom di mobile)
      Pagination.tsx  — responsive (flex-wrap di mobile)
      VolumeGrid.tsx
      StatusBadge.tsx — SeriesStatusBadge, SeriesTypeBadge, VolumeTypeBadge,
                        TicketStatusBadge, TicketTypeBadge, VolumeFormatBadge
      EmptyState.tsx
      AnnouncementBanner.tsx
  hooks/
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

---

## Fitur yang Sudah Ada

Jangan duplikasi atau rebuild ulang fitur-fitur ini:

| Fitur | Lokasi |
|-------|--------|
| Import manga dari AniList | `Admin/AniList/Index.tsx` + `AniListController` |
| Sync metadata AniList ke series yang ada | Edit Series page (Popover "Sync AniList") |
| Koleksi pribadi + volume tracking | `User/Collection/*` |
| Bulk delete volumes | `Collection/Show.tsx` + `CollectionController::destroyVolumes()` |
| Bulk delete series (admin) | `Admin/Series/Index.tsx` + `SeriesController::bulkDestroy()` |
| Input volume dengan range (`1-5,7,9-12`) | `CollectionController::storeVolumes()` |
| Sistem tiket user → admin | `User/Tickets/*`, `Admin/Tickets/*` |
| Peminjaman volume | `User/Loans/*` + `Admin/Loans` |
| Backup & import database | `Admin/Settings/Database.tsx` + `DatabaseBackupController` |
| Konfigurasi storage (Local/S3) | `Admin/Settings/Storage.tsx` + `StorageSettingController` |
| SSO login via whitearchive.id | `SsoController` (PKCE OAuth2) |

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
