# PHASES — MALAS v2 Implementation Plan

**Versi:** 2.0  
**Tanggal:** 2026-06-26

> Setelah setiap fase selesai: buka QA chat baru dengan instruksi dari `QA.md`.
> Jangan mulai fase berikutnya sebelum QA pass.

---

## Phase 0 — Project Setup & Clean Slate

**Goal:** Fresh Laravel 12 install dengan full stack terintegrasi, hapus semua kode Filament lama.

### Tasks

1. **Hapus kode lama**
   - Hapus `app/Filament/` dan semua isinya
   - Hapus `app/Providers/Filament/`
   - Hapus semua migration yang ada (akan dibuat ulang)
   - Hapus `config/filament*`, `config/announcements.php`
   - Hapus `resources/views/filament/`
   - Remove Filament packages dari `composer.json`

2. **Install fresh dependencies**
   ```bash
   composer require inertiajs/inertia-laravel
   composer require tightenco/ziggy
   composer require spatie/laravel-permission
   composer require league/flysystem-aws-s3-v3

   npm install react react-dom @inertiajs/react
   npm install -D typescript @types/react @types/react-dom
   npm install -D tailwindcss @tailwindcss/vite
   npm install -D shadcn  # bukan npm package — setup via CLI
   ```

3. **Laravel Breeze**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install react --typescript --inertia
   ```
   Ini akan setup:
   - Inertia.js v2
   - React 19 + TypeScript
   - Tailwind CSS v4
   - Halaman auth (Login, Register, dll.)
   - `HandleInertiaRequests` middleware

4. **Setup shadcn/ui**
   ```bash
   npx shadcn@latest init
   ```
   Pilih: Default style, Zinc base color, CSS variables.

5. **Setup Spatie Permission**
   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

6. **Konfigurasi R2 di `config/filesystems.php`**
   ```php
   'r2' => [
       'driver' => 's3',
       'key'    => env('R2_ACCESS_KEY_ID'),
       'secret' => env('R2_SECRET_ACCESS_KEY'),
       'region' => 'auto',
       'bucket' => env('R2_BUCKET'),
       'url'    => env('R2_URL'),
       'endpoint' => env('R2_ENDPOINT'),
       'use_path_style_endpoint' => true,
   ],
   ```

7. **Setup `tsconfig.json`**
   - Path alias: `@/*` → `resources/js/*`
   - Strict mode on

8. **Setup `vite.config.ts`**
   - Pastikan Ziggy route helper ter-bundle

### Done Criteria
- [ ] `php artisan serve` + `npm run dev` jalan tanpa error
- [ ] `/login` bisa diakses, form muncul
- [ ] `npx tsc --noEmit` → 0 errors
- [ ] `php artisan migrate:fresh` sukses
- [ ] Tidak ada file Filament tersisa

---

## Phase 1 — Database & Models

**Goal:** Semua tabel dan model siap dengan relasi dan factory.

### Tasks

1. **Migrations** (urutan sesuai FK dependency)
   - `create_menus_table`
   - `create_series_table`
   - `create_volumes_table`
   - `create_collections_table`
   - `create_collection_volumes_table`
   - `create_loans_table`
   - `create_announcements_table`
   - `create_announcement_user_table`
   - Modify `users` table: tambah `role`, `is_banned`, `ban_reason`, `banned_at`, `deleted_at`

2. **Models** dengan relasi dan `$fillable`
   - `User` — `hasMany(Collection)`, `belongsToMany(Announcement)`, `hasRole()` helper
   - `Series` — `hasMany(Volume)`, `hasMany(Collection)`
   - `Volume` — `belongsTo(Series)`, `belongsToMany(Collection)`, `hasMany(Loan)`
   - `Collection` — `belongsTo(User)`, `belongsTo(Series)`, `belongsToMany(Volume)`, `hasMany(Loan)`
   - `Loan` — `belongsTo(Collection)`, `belongsTo(Volume)`
   - `Menu` — `belongsTo(Menu, 'parent_key', 'key')`, `hasMany(Menu, 'parent_key', 'key')`
   - `Announcement` — `belongsToMany(User)` (pivot: dismissed_at)

3. **Seeders**
   - `RoleSeeder` — buat roles: `super_admin`, `admin`, `user`
   - `UserSeeder` — buat 1 super_admin, 1 admin, 3 user
   - `MenuSeeder` — seed semua menu dengan route_name dan role_access
   - `SeriesSeeder` — 10 series dummy dengan volume
   - `DatabaseSeeder` — panggil semua seeder di urutan benar

4. **Factories** untuk testing
   - `UserFactory`, `SeriesFactory`, `VolumeFactory`, `CollectionFactory`

### Done Criteria
- [ ] `migrate:fresh --seed` sukses
- [ ] Semua model punya `$fillable` dan relasi yang benar
- [ ] `php artisan tinker` bisa query: `Series::with('volumes')->first()`
- [ ] Roles terbuat: `super_admin`, `admin`, `user`

---

## Phase 2 — Auth & Middleware

**Goal:** Login/register berfungsi, middleware stack aktif, role-based redirect.

### Tasks

1. **User model** tambah helper method:
   ```php
   public function isAdmin(): bool
   {
       return in_array($this->role, ['admin', 'super_admin']);
   }
   public function isSuperAdmin(): bool
   {
       return $this->role === 'super_admin';
   }
   ```

2. **Middleware `EnsureNotBanned`**
   - Cek `auth()->user()->is_banned`
   - Jika banned → `return Inertia::render('Auth/Banned', ['reason' => ...])`

3. **Middleware `CheckMenuAccess`**
   - Ambil `Route::currentRouteName()`
   - Query `Menu::where('route_name', $routeName)->first()`
   - Jika tidak ada → lanjut (skip)
   - Jika `is_maintenance` dan bukan admin → `Inertia::render('Maintenance', [...])`
   - Jika role tidak ada di `role_access` → `abort(403)`

4. **Register middleware** di `bootstrap/app.php`
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'banned'     => EnsureNotBanned::class,
           'check.menu' => CheckMenuAccess::class,
       ]);
   })
   ```

5. **Route-based redirect setelah login**
   - Override `AuthenticatedSessionController@store`
   - Admin → `/admin/dashboard`, user → `/dashboard`

6. **`HandleInertiaRequests` middleware**
   - Tambah shared data: `auth.user` (tanpa password), `menus` (filtered by role)
   ```php
   public function share(Request $request): array
   {
       return [
           ...parent::share($request),
           'auth' => ['user' => $request->user()?->only('id','name','email','role')],
           'menus' => $request->user()
               ? Menu::where('is_visible', true)
                     ->whereJsonContains('role_access', $request->user()->role)
                     ->orderBy('sort_order')
                     ->get()
               : [],
       ];
   }
   ```

7. **Halaman `Auth/Banned.tsx`** dan **`Maintenance.tsx`**

### Done Criteria
- [ ] Login berhasil, redirect ke route yang benar per role
- [ ] `EnsureNotBanned` blokir user yang di-ban
- [ ] `CheckMenuAccess` blokir route yang tidak sesuai role
- [ ] Maintenance mode render halaman maintenance
- [ ] Admin tetap bisa akses route maintenance

---

## Phase 3 — Layouts & Shared UI

**Goal:** AdminLayout dan UserLayout siap dengan sidebar dinamis dari shared `menus` data.

### Tasks

1. **`AdminLayout.tsx`**
   - Sidebar dengan menu dari `usePage().props.menus` (filter: role admin)
   - Topbar: nama user + logout
   - Active menu highlight berdasarkan current route
   - Responsive: collapsible sidebar di mobile

2. **`UserLayout.tsx`**
   - Sidebar lebih simpel (4-5 menu)
   - Sama sumber datanya dari Inertia shared props

3. **Komponen shared:**
   - `AnnouncementBanner.tsx` — dismissible banner, POST ke `/announcements/{id}/dismiss`
   - `PageHeader.tsx` — judul halaman + breadcrumb opsional
   - `EmptyState.tsx` — UI saat data kosong

4. **Dark/light mode toggle**
   - Simpan di `localStorage`
   - Pakai Tailwind `dark:` classes

5. **Install shadcn components yang dibutuhkan layout:**
   ```bash
   npx shadcn@latest add button badge separator avatar dropdown-menu tooltip
   ```

### Done Criteria
- [ ] Admin login → lihat sidebar admin dengan menu
- [ ] User login → lihat sidebar user dengan menu terbatas
- [ ] Active menu item ter-highlight
- [ ] Dark mode toggle berfungsi
- [ ] Sidebar collapsible di mobile

---

## Phase 4 — Admin: Series & Volume CRUD

**Goal:** Admin bisa kelola series dan volume. Termasuk upload cover.

### Tasks

1. **Install shadcn components:**
   ```bash
   npx shadcn@latest add table card dialog form input select textarea label toast sonner pagination
   ```

2. **SeriesPolicy** + register di `AuthServiceProvider`

3. **`Admin/SeriesController`** — index, create, store, show, edit, update, destroy
   - Upload cover → konversi WebP, simpan ke R2
   - Paginate 20 per halaman

4. **Pages:**
   - `Admin/Series/Index.tsx` — tabel + filter + pagination
   - `Admin/Series/Create.tsx` — form tambah series
   - `Admin/Series/Edit.tsx` — form edit series
   - `Admin/Series/Show.tsx` — detail series + tab Volume

5. **VolumePolicy** + `Admin/VolumeController` — CRUD nested di bawah series

6. **Routes:**
   ```php
   Route::middleware(['auth', 'banned', 'check.menu'])->prefix('admin')->name('admin.')->group(function () {
       Route::resource('series', Admin\SeriesController::class);
       Route::resource('series.volumes', Admin\VolumeController::class)->shallow();
   });
   ```

7. **TypeScript types** di `lib/types.ts`:
   ```typescript
   interface Series { id: string; title_romaji: string; status: SeriesStatus; ... }
   interface Volume { id: string; series_id: string; volume_number: number; ... }
   ```

### Done Criteria
- [ ] Admin bisa tambah, edit, hapus series
- [ ] Admin bisa tambah, edit, hapus volume di series
- [ ] Cover upload ke R2 berfungsi (atau skip R2, pakai local untuk dev)
- [ ] Pagination berfungsi
- [ ] Form validation error tampil inline
- [ ] User biasa tidak bisa akses `/admin/series` (harus 403)

---

## Phase 5 — User: Katalog & Koleksi

**Goal:** User bisa browse katalog dan kelola koleksi pribadi.

### Tasks

1. **`User/SeriesController`** — index, show (read-only, no policy needed beyond auth)

2. **Pages:**
   - `User/Catalog/Index.tsx` — grid card series, filter, search
   - `User/Catalog/Show.tsx` — detail series + daftar volume + tombol "Tambah ke Koleksi"

3. **CollectionPolicy** — user hanya bisa akses koleksi milik sendiri

4. **`User/CollectionController`** — index, store, show, update, destroy

5. **Pages:**
   - `User/Collection/Index.tsx` — daftar series di koleksi user
   - `User/Collection/Show.tsx` — detail koleksi: grid volume + checkbox is_owned

6. **Volume checklist** — toggle via AJAX (Inertia PUT `/my-collection/{id}/volumes/{volumeId}`)

7. **`SeriesCard.tsx`** dan **`VolumeGrid.tsx`** components

### Done Criteria
- [ ] User bisa browse katalog, tidak ada tombol edit/hapus
- [ ] User bisa tambah series ke koleksi dari halaman detail
- [ ] User bisa checklist volume yang dimiliki
- [ ] User tidak bisa lihat/edit koleksi user lain (harus 403)
- [ ] Admin bisa lihat semua koleksi di `/admin/collections`

---

## Phase 6 — Loans (Peminjaman)

**Goal:** User bisa catat dan tracking peminjaman volume dari koleksinya.

### Tasks

1. **LoanPolicy** — hanya bisa manage loan dari koleksi sendiri

2. **`User/LoanController`** — index, store, update (mark returned)

3. **`Admin/LoanController`** — index (lihat semua), show

4. **Pages:**
   - `User/Loans/Index.tsx` — tabel pinjaman aktif + history
   - `Admin/Loans/Index.tsx` — semua pinjaman dari semua user

5. **Modal "Pinjamkan"** di `User/Collection/Show.tsx`

6. **Status badge:** "Dipinjam" (kuning) / "Terlambat" (merah, jika past due_at) / "Dikembalikan" (hijau)

### Done Criteria
- [ ] User bisa catat pinjaman dari halaman koleksi detail
- [ ] User bisa tandai dikembalikan
- [ ] Status terlambat otomatis tampil jika past due_at
- [ ] Admin bisa lihat semua pinjaman

---

## Phase 7 — Jikan API Integration

**Goal:** Admin bisa search dan import data series dari MyAnimeList.

### Tasks

1. **`JikanService`** — wrapper dengan rate limiting (max 3 req/detik), retry exponential backoff

2. **`Admin/JikanController`** — search (GET), import (POST)

3. **Pages:**
   - `Admin/Jikan/Index.tsx` — search input + hasil grid card
   - Modal preview detail sebelum import

4. **Import logic:**
   - Cek `mal_id` existing → update jika ada, insert jika tidak
   - Download cover dari URL Jikan → upload ke R2 sebagai WebP

5. **Error handling:**
   - Jikan timeout → toast error + retry button
   - Rate limit → queue / wait

### Done Criteria
- [ ] Search Jikan menggunakan debounce 500ms
- [ ] Preview card muncul dengan data dari Jikan
- [ ] Import berhasil mengisi semua field series
- [ ] Duplicate MAL ID ter-handle (update, bukan error)
- [ ] Jika Jikan down, halaman tidak crash

---

## Phase 8 — Announcements & Dashboard

**Goal:** Pengumuman berfungsi end-to-end, dashboard admin dan user menampilkan data yang relevan.

### Tasks

1. **`Admin/AnnouncementController`** — CRUD
2. **`AnnouncementController@dismiss`** — POST `/announcements/{id}/dismiss` (untuk semua user)
3. **Pages:**
   - `Admin/Announcements/Index.tsx`
   - `Admin/Announcements/Create.tsx` + `Edit.tsx` — dengan markdown preview
4. **Dashboard:**
   - `Admin/Dashboard.tsx` — stats: total series, volume, koleksi, user; grafik series by status
   - `User/Dashboard.tsx` — stats koleksi sendiri: total series, volume dimiliki, sedang dipinjam
5. **`AnnouncementBanner.tsx`** — muncul di semua halaman, dismissible

### Done Criteria
- [ ] Admin bisa buat, edit, hapus pengumuman
- [ ] Banner muncul di dashboard setiap user
- [ ] User bisa dismiss, banner tidak muncul lagi
- [ ] Expired announcement tidak muncul
- [ ] Dashboard admin tampilkan stats yang benar
- [ ] Dashboard user tampilkan data koleksi milik sendiri

---

## Phase 9 — User Management & Menu Management

**Goal:** Admin bisa kelola user dan menu. Super admin bisa kelola role.

### Tasks

1. **`Admin/UserController`** — index, show, ban, unban, changeRole
2. **Pages:**
   - `Admin/Users/Index.tsx` — tabel user + filter
   - `Admin/Users/Show.tsx` — profil + aksi ban/unban/ganti role
3. **`Admin/MenuController`** — index, update (toggle is_maintenance, is_visible, edit role_access)
4. **Pages:**
   - `Admin/Menus/Index.tsx` — tabel dengan inline toggle untuk maintenance & visible
   - `Admin/Menus/Edit.tsx` — form edit detail menu
5. **Ban flow:** logout user yang di-ban saat admin klik ban
6. **Role restriction:** admin tidak bisa ubah role ke/dari `super_admin`

### Done Criteria
- [ ] Admin bisa ban/unban user
- [ ] User yang di-ban tidak bisa login
- [ ] Admin bisa toggle maintenance mode per menu
- [ ] Maintenance mode langsung aktif tanpa restart server
- [ ] Super admin bisa ganti role user
- [ ] Regular admin tidak bisa ubah role ke super_admin

---

## Phase 10 — Polish & Hardening

**Goal:** QA akhir sebelum production-ready. Semua edge case ter-handle.

### Tasks

1. **TypeScript strict pass** — `tsc --noEmit` harus 0 errors
2. **Form validation** — semua form punya Zod schema
3. **Loading states** — semua tombol request punya disabled + spinner
4. **Error pages** — 403, 404, 500 dengan tampilan yang proper
5. **Empty states** — semua tabel/grid punya empty state UI
6. **Mobile responsive** — test semua halaman di viewport 375px
7. **Performance** — pastikan query tidak N+1 (gunakan `->with(...)` eager load)
8. **Security audit:**
   - Semua route admin ada middleware `auth` + `check.menu`
   - Semua mutation ada `$this->authorize()`
   - Tidak ada data sensitif di Inertia props
   - File upload validasi MIME type

### Done Criteria
- [ ] `tsc --noEmit` → 0 errors
- [ ] `php artisan test` → semua pass (jika ada test)
- [ ] Tidak ada halaman kosong / blank di kondisi data kosong
- [ ] Semua halaman responsive di mobile
- [ ] Security checklist dari `QA.md` semua pass

---

## Summary Tabel

| Phase | Nama | Fitur Utama |
|-------|------|-------------|
| 0 | Setup | Fresh install, stack integration |
| 1 | Database | Migrations, models, seeders |
| 2 | Auth | Login, middleware, role redirect |
| 3 | Layouts | AdminLayout, UserLayout, sidebar |
| 4 | Series CRUD | Admin kelola series & volume |
| 5 | Katalog & Koleksi | User browse + kelola koleksi |
| 6 | Loans | Catat & tracking peminjaman |
| 7 | Jikan | Import dari MyAnimeList |
| 8 | Announcements & Dashboard | Banner + stats dashboard |
| 9 | User & Menu Management | Ban, role, maintenance toggle |
| 10 | Polish | QA akhir, hardening |
