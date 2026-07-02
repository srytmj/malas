# PHASES — MALAS v2 Implementation Plan

**Versi:** 2.0  
**Tanggal:** 2026-06-26  
**Status:** ✅ Semua fase selesai (QA pass 2026-07-03)

> Setelah setiap fase selesai: buka QA chat baru dengan instruksi dari `QA.md`.
> Jangan mulai fase berikutnya sebelum QA pass.

---

## Phase 0 — Project Setup & Clean Slate ✅

**Goal:** Fresh Laravel 12 install dengan full stack terintegrasi.

Stack yang diinstall:
- Laravel 12 + Laravel Breeze (React + TypeScript + Inertia)
- Inertia.js v2, React 19, TypeScript 5, Tailwind CSS v4
- shadcn/ui (init dengan Zinc + CSS variables)
- Spatie Laravel Permission
- league/flysystem-aws-s3-v3 (Cloudflare R2)
- Ziggy untuk route helper di frontend

### Done Criteria
- [x] `php artisan serve` + `npm run dev` jalan tanpa error
- [x] `/login` bisa diakses, form muncul
- [x] `npx tsc --noEmit` → 0 errors
- [x] `php artisan migrate:fresh` sukses

---

## Phase 1 — Database & Models ✅

**Goal:** Semua tabel dan model siap dengan relasi dan factory.

Models dan relasi aktual:
- `User` — `hasMany(Collection)`, `belongsToMany(Announcement)`
- `Series` — `hasMany(Volume)`, `hasMany(Collection)`
- `Volume` — `belongsTo(Series)` (tidak ada relasi ke collections/loans — FK sudah dipindah)
- `Collection` — `belongsTo(User)`, `belongsTo(Series)`, `hasMany(CollectionVolume)`, `hasMany(Loan)`
- `CollectionVolume` — `belongsTo(Collection)`, `hasMany(Loan)` (uuid PK, HasUuids)
- `Loan` — `belongsTo(Collection)`, `belongsTo(CollectionVolume, 'collection_volume_id')`
- `Menu`, `Announcement` — sesuai PRD

### Done Criteria
- [x] `migrate:fresh --seed` sukses
- [x] Semua model punya `$fillable` dan relasi yang benar
- [x] Roles terbuat: `super_admin`, `admin`, `user`

---

## Phase 2 — Auth & Middleware ✅

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
- [x] Login berhasil, redirect ke route yang benar per role
- [x] `EnsureNotBanned` blokir user yang di-ban
- [x] `CheckMenuAccess` blokir route yang tidak sesuai role
- [x] Maintenance mode render halaman maintenance
- [x] Admin tetap bisa akses route maintenance

---

## Phase 3 — Layouts & Shared UI ✅

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
- [x] Admin login → lihat sidebar admin dengan menu
- [x] User login → lihat sidebar user dengan menu terbatas
- [x] Active menu item ter-highlight
- [x] Dark mode toggle berfungsi
- [x] Sidebar collapsible di mobile

---

## Phase 4 — Admin: Series & Volume CRUD ✅

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
- [x] Admin bisa tambah, edit, hapus series
- [x] Admin bisa tambah, edit, hapus volume di series
- [x] Cover upload berfungsi (local disk dev, R2 untuk prod)
- [x] Pagination berfungsi
- [x] Form validation error tampil inline
- [x] User biasa tidak bisa akses `/admin/series` (harus 403)

---

## Phase 5 — User: Katalog & Koleksi ✅

**Goal:** User bisa browse katalog dan kelola koleksi pribadi dengan volume tracking per-user.

Implementasi aktual berbeda dari rencana awal — volume tracking tidak pakai toggle checklist dari daftar volume admin, tapi user input sendiri:

- `User/CollectionController` — index, store (multi-series), show, destroy, storeVolumes, destroyVolume
- Volume input: CSV nomor volume + format per batch → disimpan ke `collection_volumes`
- Tambah series via dialog search + multi-select di `/my-collection`
- `VolumeGrid` menjadi pure display component (tidak ada toggle)

### Done Criteria
- [x] User bisa browse katalog, tidak ada tombol edit/hapus
- [x] User bisa tambah series ke koleksi (multi-select dari dialog)
- [x] User bisa input volume yang dimiliki (CSV + format)
- [x] User tidak bisa lihat/edit koleksi user lain (harus 403)
- [x] Admin bisa lihat semua koleksi di `/admin/collections`

---

## Phase 6 — Loans (Peminjaman) ✅

**Goal:** User bisa catat dan tracking peminjaman volume dari koleksinya.

Loan mereferensikan `collection_volume_id` (bukan `volume_id`).

### Done Criteria
- [x] User bisa catat pinjaman dari halaman koleksi detail
- [x] User bisa tandai dikembalikan
- [x] Status terlambat otomatis tampil jika past due_at
- [x] Admin bisa lihat semua pinjaman

---

## Phase 7 — Jikan API Integration ✅

**Goal:** Admin bisa search dan import data series dari MyAnimeList.

### Done Criteria
- [x] Search Jikan menggunakan debounce 500ms
- [x] Preview card muncul dengan data dari Jikan
- [x] Import berhasil mengisi semua field series
- [x] Duplicate MAL ID ter-handle (update, bukan error)
- [x] Jika Jikan down, halaman tidak crash

---

## Phase 8 — Announcements & Dashboard ✅

**Goal:** Pengumuman berfungsi end-to-end, dashboard admin dan user menampilkan data yang relevan.

### Done Criteria
- [x] Admin bisa buat, edit, hapus pengumuman
- [x] Banner muncul di dashboard setiap user
- [x] User bisa dismiss, banner tidak muncul lagi
- [x] Expired announcement tidak muncul
- [x] Dashboard admin tampilkan stats yang benar
- [x] Dashboard user tampilkan data koleksi milik sendiri

---

## Phase 9 — User Management & Menu Management ✅

**Goal:** Admin bisa kelola user dan menu. Super admin bisa kelola role.

### Done Criteria
- [x] Admin bisa ban/unban user
- [x] User yang di-ban logout otomatis + tidak bisa login
- [x] Admin bisa toggle maintenance mode per menu
- [x] Maintenance mode langsung aktif tanpa restart server
- [x] Super admin bisa ganti role user
- [x] Regular admin tidak bisa ubah role ke super_admin

---

## Phase 10 — Polish & Hardening ✅

**Goal:** QA akhir sebelum production-ready.

### Done Criteria
- [x] `tsc --noEmit` → 0 errors
- [x] Tidak ada halaman kosong / blank di kondisi data kosong
- [x] Loading state semua tombol yang trigger request
- [x] Error pages 403/404/500/503 via `Error.tsx`
- [x] Semua mutation ada `$this->authorize()`
- [x] Semua route ada `auth` + `check.menu` middleware
- [x] File upload validasi `mimes:jpeg,jpg,png,webp`
- [x] Tidak ada `dd()` / `var_dump()` / `any` TypeScript tersisa

---

## Summary Tabel

| Phase | Nama | Status |
|-------|------|--------|
| 0 | Setup | ✅ |
| 1 | Database & Models | ✅ |
| 2 | Auth & Middleware | ✅ |
| 3 | Layouts & Shared UI | ✅ |
| 4 | Admin: Series & Volume CRUD | ✅ |
| 5 | User: Katalog & Koleksi | ✅ |
| 6 | Loans | ✅ |
| 7 | Jikan API Integration | ✅ |
| 8 | Announcements & Dashboard | ✅ |
| 9 | User & Menu Management | ✅ |
| 10 | Polish & Hardening | ✅ |

**QA pass: 2026-07-03**
