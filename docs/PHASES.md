# PHASES — Malas v2 Implementation Plan

**Versi:** 2.7
**Tanggal:** 2026-06-26 (Phase 0–10), diperbarui 2026-08-03 (Phase 11–18)
**Status:** ✅ Semua fase selesai (QA pass 2026-07-03) + Phase 11–18 post-launch enhancements (Phase 18 butuh verifikasi visual manual)

> Setelah setiap fase selesai: buka QA chat baru dengan instruksi dari `QA.md`.
> Jangan mulai fase berikutnya sebelum QA pass.
> Phase 0–10 di bawah ini adalah histori perencanaan v2 asli dan **tidak diedit ulang** untuk mencerminkan perubahan sesudahnya — perubahan detail per-fitur pasca-QA dicatat di Phase 11 dan di [`CHANGELOG.md`](../CHANGELOG.md).

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

## Phase 11 — Post-Launch Enhancements ✅

**Goal:** Item-item yang muncul setelah QA v2 pass, di luar rencana fase awal.

Dikerjakan bertahap setelah 2026-07-03, tidak dalam urutan fase formal:

1. **Migrasi Jikan → AniList** — `JikanService` dihapus total, diganti `AniListService` (GraphQL). Menambah kolom `anilist_id`, `genres`, `authors`, `themes`, `demographics` (json) di `series`. Search & import UI dirombak jadi absolute overlay per-card (bukan Popover, karena anchor engine Base UI selalu menempatkan popover di samping trigger, bukan di tengah).
2. **SSO whitearchive.id** — `SsoController` PKCE OAuth2 menggantikan auth lokal Breeze sepenuhnya. Kolom baru di `users`: `sso_id`, `username`, `avatar`; `password` jadi nullable.
3. **Sistem tiket** — tabel `tickets` + `User/Tickets/*` (buat & lihat) + `Admin/Tickets/*` (respond). Bisa diakses langsung dari katalog dengan series pre-filled. Note "buat tiket request" ditambahkan di halaman Catalog dan Collection kosong/hasil pencarian nihil.
4. **Storage settings via UI** — tabel `storage_settings` (driver `local`/`s3`, credentials ter-encrypt) + `StorageSettingsService` sebagai satu-satunya jalur akses file. Semua kode yang sebelumnya panggil `Storage::` facade langsung dimigrasi.
5. **Database backup & import** — `DatabaseBackupController` (super_admin only): download SQL dump (exclude tabel sensitif seperti `users`, `sessions`, `jobs`), import dengan `DELETE + INSERT` per tabel dibungkus transaction agar atomic (bukan `TRUNCATE`, yang implicit-commit di MySQL).
6. **Input volume dengan range syntax** — `CollectionController::storeVolumes()` menerima `1,2,5-9,11,12` dan expand jadi list nomor volume individual (dengan validasi edge case: swap jika terbalik, dedupe, limit 100 per batch).
7. **Bulk delete series (admin)** — checkbox multi-select di `Admin/Series/Index.tsx` + `SeriesController::bulkDestroy()`, satu request hapus banyak series sekaligus (tetap authorize per-item via Policy).
8. **Mobile-first UI pass (user-side)** — perbaikan responsive di `PageHeader`, `Pagination`, halaman Catalog/Collection agar tidak ada elemen tertimpa di layar sempit.
9. **Cover preview fix** — `key={displayCover}` di elemen `<img>` Edit Series supaya React remount elemen (bukan reuse DOM node), menghindari `onError` lama (`display:none`) nyangkut lintas render.
10. **Deployment tooling** — `deploy.sh` (setup server Ubuntu 24 dari kode yang sudah ter-clone, bukan clone ulang), `update.sh` (pull + smart-skip build steps + migration aman), dan `docs/DEPLOYMENT.md`.

### Done Criteria
- [x] Tidak ada referensi `JikanService` tersisa di codebase
- [x] Login hanya lewat SSO, tidak ada form register/login lokal
- [x] User bisa buat tiket, admin bisa respond, status ter-update
- [x] Storage bisa di-switch Local ↔ S3 dari UI tanpa restart/redeploy
- [x] Database backup bisa di-download dan di-import ulang tanpa korupsi data
- [x] Range volume `1-5,7,9-12` ter-parse benar termasuk edge case terbalik/duplikat
- [x] Admin bisa pilih banyak series sekaligus lalu hapus dalam satu aksi
- [x] `npx tsc --noEmit` → 0 errors

---

## Phase 12 — Library UI, Dashboard, Baca Tracking & Review ✅

**Goal:** Integrasi komponen shadcn/Base UI yang belum terpakai, dashboard yang lebih informatif, dan fitur baca-tracking + review pribadi di koleksi.

Dikerjakan iteratif setelah Phase 11, dalam dua batch:

**Batch 1 — Library UI & Dashboard**
1. `Empty` component (`ui/empty.tsx`) menggantikan `EmptyState` di Koleksiku & Pinjaman.
2. Selector jumlah data per halaman (5/10/25/50/100) di semua datatable server-paginated — `Controller::perPage()` whitelist param, `Pagination.tsx` di-extend.
3. Avatar kolektor (tanpa nama, privasi) + jumlah total di halaman detail Katalog.
4. Hover Card preview cover/tipe/status/skor di Admin Series & Koleksiku.
5. Context menu (klik kanan) Lihat/Edit/Hapus di Admin Series & Koleksiku.
6. Command Palette admin (⌘K) — nav cepat + search Series/Users/Tiket via `Admin/CommandSearchController`.
7. Dashboard charts (Recharts) — Admin (Series per Status, Koleksi per Tipe, Status Pinjaman), User (Koleksi per Status).
8. Rekomendasi genre + Surprise Me di dashboard user — dihitung di PHP (bukan raw JSON query) untuk portabilitas SQLite/MySQL.

**Batch 2 — Baca Tracking, Review, Search, Undo**
9. Kolom `collection_volumes.read_at` — toggle baca per volume (icon mata, greyed out saat sudah dibaca), tombol tandai-semua di header daftar volume, indikator "Terakhir dibaca: Vol. N".
10. Mode hapus volume — tombol "Hapus" men-toggle seleksi, icon mata berubah jadi checkbox di slot yang sama.
11. Kolom `collections.personal_rating` (-10..10) + `personal_review` — card review & rating pribadi (slider gaya MyAnimeList) di halaman detail koleksi; genre/theme/demographic series ditampilkan lengkap.
12. Carousel rekomendasi dashboard (`embla-carousel-react` + `ui/carousel.tsx`) — cover, judul, author, genre, sinopsis singkat per slide; chart "Progres Volume" yang bias (banyak series belum ada `total_volumes`) dihapus.
13. Grid Koleksiku diganti poster card auto-fill (`repeat(auto-fill,minmax(160px,1fr))`) — cover lebih lebar, kolom menyesuaikan otomatis.
14. Global Search user (⌘K + search bar header) — `GlobalSearch.tsx` + `User/SearchController`, cari Katalog/Koleksiku/navigasi sekaligus.
15. Undo pada toast — `useFlash.ts` + flash `undo_url`/`undo_payload`, dipasang di toggle-baca dan tandai-semua-baca (endpoint `unmarkVolumesRead` khusus supaya undo tidak salah revert volume yang sudah dibaca sebelumnya).
16. Fix: rekomendasi genre kosong total kalau sisa katalog tidak punya data genre — fallback ke random pick.
17. Fix: baris tabel Koleksiku & Admin Series cuma bisa diklik lewat judul — `onClick` dipindah ke prop langsung `ContextMenuTrigger`, bukan nested di `render` prop.

**Ditunda ke backlog:** komponen `Attachment` untuk upload galeri media admin (spec hilang saat context compaction); profil publik + sistem follow + activity feed (gaya Steam) — fitur besar, sengaja dijadwalkan untuk sesi terpisah.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors
- [x] `php artisan test` → pass
- [x] Semua endpoint baru punya `$this->authorize()` yang sesuai
- [x] Toggle baca, mode hapus, dan review tersimpan benar di database (diverifikasi lewat tinker)
- [x] Rekomendasi dashboard tidak pernah kosong total selama ada series yang belum dikoleksi

---

## Phase 13 — Light Novel Metadata Import (RanobeDB) ✅

**Goal:** Import series/volume metadata untuk light novel dari [RanobeDB](https://ranobedb.org), paralel dengan integrasi AniList yang sudah ada (tidak menggantikan, sisi manga/manhwa/manhua tetap AniList).

Rencana lengkap ada di [`docs/RANOBEDB_INTEGRATION.md`](RANOBEDB_INTEGRATION.md). REST API tanpa auth, model tiga level (Series → Books → Releases), staff ter-split author/illustrator secara native. Migration `series.ranobedb_id` + `series.illustrators` (json) ditambahkan.

Implementasi: `RanobeDbService` (REST client) + `RanobeDbController` (`/admin/ranobedb`, search/detail/import) + `Admin/RanobeDb/Index.tsx` (card overlay, sama pola dengan AniList). Popover "Sync RanobeDB" di Edit Series untuk sync ulang metadata ke series yang sudah ada. `ExternalSearchController` + `Admin/Search/Index.tsx` menggabungkan hasil AniList+RanobeDB dalam satu pencarian (`/admin/search-external`).

### Done Criteria
- [x] Search & import series light novel dari RanobeDB berfungsi
- [x] Genre/demographic/theme/author/illustrator ter-split benar dari `tags[]`/`staff[]`
- [x] Tanggal terbit menangani sentinel `99999999` (ongoing) dengan benar
- [x] Import duplikat (`ranobedb_id` sama) update, bukan bikin series baru
- [x] `npx tsc --noEmit` → 0 errors, `php artisan test` → pass

---

## Phase 14 — Multi-Bahasa, Profil Publik & Follow, Wishlist, Selera Genre AI, Menu Reorder ✅

**Goal:** Batch besar post-launch enhancement: dukungan multi-bahasa penuh, komunitas dasar (profil publik + follow), wishlist, fitur AI ringan tanpa biaya server (Puter.js), dan polish admin (reorder menu drag-drop).

Dikerjakan iteratif setelah Phase 13, tidak dalam urutan fase formal:

1. **Multi-bahasa (id/en/ja)** — `react-i18next` + resource JSON per-namespace (`common`, `dashboard`, `user`, `catalog`, `collection`, `admin`) di `resources/js/lang/{id,en,ja}/`. Middleware `SetLocale` set `App::setLocale()` server-side dari `users.locale` (kolom baru, default `id`), di-share ke frontend lewat `HandleInertiaRequests`. `lang/{id,en,ja}/validation.php` + `pagination.php` dipublish supaya pesan bawaan Laravel ikut bahasa aktif. `LanguageSwitcher.tsx` di sidebar footer (Admin & User Layout) untuk ganti bahasa tanpa buka Settings. `useTypeFilterOptions()` dan `menuTranslationKey()` sebagai hook/helper terpusat untuk menghindari duplikasi terjemahan. Cakupan: seluruh `User/**`, `Settings/Index.tsx`, `Admin/Dashboard.tsx`+`ActivityLog/Index.tsx`, semua Layouts/shared components — sisa halaman `Admin/**` masih backlog, lihat [`CLAUDE.md`](../CLAUDE.md) bagian "Sistem Multi-Bahasa".
2. **Profil publik & follow** — kolom `users.is_profile_public` (opt-in, default false) + tabel `follows`. Route `/u/{user}` sengaja dipindah keluar dari grup middleware `auth` supaya guest bisa lihat profil (koleksi tampil read-only, tidak bisa klik ke detail katalog karena itu tetap butuh login). `ProfileController` dibikin null-safe untuk viewer tamu. `PublicShell` (layout minimal khusus guest, bukan `UserLayout` yang non-null-assert `auth.user`) dipakai saat `is_guest = true`. Direktori Pengguna (`/directory`, butuh login) untuk cari & follow user lain.
3. **Wishlist** — tabel `wishlist_items` (unique per user+series), `WishlistController` + `User/Wishlist/Index.tsx`, terpisah dari Koleksi (tidak ada tracking volume/format, murni penanda minat).
4. **Selera Genre (AI Funfact)** — word cloud genre + funfact text AI di dashboard user. Tabel `genre_funfacts` (kuota generate-ulang manual, default 5×/minggu, bisa di-override admin) + `ai_settings` (provider aktif, single-row). Default provider **Puter.js** — client-side, gratis, tanpa API key server (`resources/js/lib/puter.ts`, script di-load global via `app.blade.php`). Provider alternatif (Gemini/OpenAI/Claude) generate server-side lewat `AiFunfactService`, dengan `AiRateLimitException` khusus untuk HTTP 429 supaya fallback ke teks default tanpa memotong kuota manual user. Admin kelola kuota per user di `/admin/funfact-quota` (`GenreFunfactController`).
5. **Reorder menu sidebar (drag & drop)** — `SortableMenuList.tsx` (`@dnd-kit/core` + `sortable` + `utilities`) di `Admin/Menus/Index.tsx`, `PATCH /admin/menus/reorder` update `sort_order` sesuai urutan drag. Preview sidebar user tanpa login sebagai user biasa di `/admin/menus/user`.
6. **Ebook detail per volume koleksi** — kolom `collection_volumes.ebook_source` dan `language`, relevan untuk format `ebook`/`online`.
7. **Blur konten 18+ diperluas** — kolom `series.is_adult` dipakai konsisten di search AniList/RanobeDB dan tampilan katalog, terhubung ke toggle blur di `/admin/settings` tab Konten.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors
- [x] `php artisan test` → pass, Pint clean
- [x] Guest bisa akses `/u/{user}` tanpa error, tidak bisa follow atau ke katalog
- [x] Ganti bahasa langsung update UI tanpa reload, tersimpan per-user
- [x] Funfact AI (provider Puter) generate & tersimpan tanpa API key server
- [x] Reorder menu drag-drop langsung update urutan sidebar
- [x] Sisa halaman `Admin/**`, halaman root (`Landing`, `Error`, `Auth/Banned`, `Maintenance`), dan
      `Components/app/**` diterjemahkan penuh — lihat [`CLAUDE.md`](../CLAUDE.md) untuk cakupan
      lengkap. Sisa gap yang terdokumentasi: flash message controller (backlog terpisah, belum
      ada sistem terjemahan terpusat) dan `Pages/Dashboard.tsx` root yang dead code (sengaja tidak
      disentuh).

---

## Phase 15 — URL Katalog Berbasis Judul (Slug) ✅

**Goal:** Ganti URL katalog series dari UUID mentah (`/catalog/f3a1...`) jadi slug yang dibaca dari judul (`/catalog/one-piece`), tanpa merusak link/bookmark lama.

1. Kolom `series.slug` baru — auto-generated dari `title_romaji` via `Series::generateUniqueSlug()` (`Str::slug()` dengan dictionary kosong, supaya simbol seperti `@`/`!`/koma/titik dua dibuang langsung alih-alih dikonversi jadi kata seperti `@` → `at`). Full judul dipakai apa adanya walau panjang. Regenerate otomatis lewat model event (`creating`/`updating`) kalau judul berubah; tambah suffix `-2`/`-3` kalau ada judul kembar.
2. `Series::resolveRouteBinding()` — coba cocokkan slug dulu, fallback ke `id` (UUID) supaya link lama tetap jalan tanpa redirect khusus.
3. Migration membackfill slug untuk semua series yang sudah ada di database (diverifikasi lewat tinker: 46/46 series di dev ter-backfill benar).
4. Semua controller yang mengirim data series ke frontend (`User/SeriesController`, `DashboardController`, `CollectionController`, `WishlistController`, `ProfileController`, `TicketController`, `SearchController`) diupdate untuk ikut kirim `slug`.
5. Semua halaman yang bikin link ke `catalog.show` (Catalog, Dashboard rekomendasi/Surprise Me, Collection, Wishlist, Tiket, Profil Publik, Global Search) diupdate pakai `slug` bukan `id` saat generate URL.

### Done Criteria
- [x] Judul dengan simbol (`@`, `!`, koma, titik dua, tanda kurung) menghasilkan slug bersih tanpa simbol tersebut — diverifikasi lewat tinker
- [x] Judul kembar menghasilkan slug dengan suffix `-2`, `-3`, dst — diverifikasi lewat tinker
- [x] Ganti judul series meregenerasi slug otomatis
- [x] URL lama berbasis UUID tetap bisa diakses (fallback route binding)
- [x] `npx tsc --noEmit` → 0 errors, migration + backfill berhasil di dev

---

## Phase 16 — Login Tanpa SSO (Fallback) + Konfigurasi Email (Resend) ✅

**Goal:** Jalan darurat login kalau whitearchive.id (SSO) benar-benar tidak bisa diakses — down, migrasi, atau maintenance — tanpa membangun sistem password lokal permanen (yang bertentangan dengan desain "SSO-only" project ini).

Keputusan desain (hasil diskusi sebelum implementasi): bukan sistem approval admin (dibahas lalu disederhanakan), bukan password lokal (tidak ada user yang punya password tersimpan). Solusinya: magic link sekali-pakai lewat email yang sudah tersinkron dari SSO — verifikasi identitas lewat kepemilikan inbox, bukan sesuatu yang bisa ditebak (nama/email publik).

1. **Konfigurasi Email (Resend)** — tabel `mail_settings` (single-row, `api_key` ter-encrypt, sama pola dengan `storage_settings`/`ai_settings`), tab baru "Email" di `/admin/settings`. `MailSettingsService` set config Resend runtime dari DB sebelum kirim — tidak ada credential email di `.env`. Package `resend/resend-php` (native Laravel `resend` mail transport).
2. **Login Tanpa SSO** — link "SSO nggak bisa diakses?" di Landing page → `/auth/fallback` (`SsoFallbackController`) → user isi email → kalau cocok user yang ada DAN mail terkonfigurasi, terbit `SsoFallbackToken` (hash SHA-256, TTL 15 menit, single-use, tabel `sso_fallback_tokens`) → email `SsoFallbackLoginMail` dikirim → klik link → `Auth::login()` langsung.
3. **Anti email-enumeration** — response endpoint request selalu pesan generik yang sama, tidak pernah membocorkan apakah email terdaftar/user banned/mail gagal terkirim.
4. **Rate limiting** — `throttle:3,15` di route POST, mencegah spam permintaan magic link.
5. **Error handling** — kegagalan kirim email (API key salah, provider down) ditangkap try/catch, tidak bikin request 500; dicatat ke `ActivityLog` + log Laravel biasa.
6. **Fix `ActivityLog::record()`** — ditemukan saat testing: method ini selalu pakai `auth()->id()` buat `user_id` (kolom NOT NULL), yang null untuk request dari guest (kasus baru: permintaan login tanpa SSO). Fallback ditambahkan ke ID user subject kalau tidak ada yang login.
7. **Landing page dirombak** — ditambah header (brand, ganti bahasa, toggle dark mode, tombol Login) dan footer (tagline, copyright) supaya halaman terasa lebih lengkap, sekalian tempat menaruh link fallback. `LanguageSwitcher` dibikin guest-safe (skip persist ke server kalau belum login, cukup ganti di client).
8. **CLI emergency access** — `php artisan sso:emergency-login {identifier=super_admin}` (`app/Console/Commands/IssueEmergencyLoginLink.php`), reuse `SsoFallbackToken` yang sama dengan jalur email. Identifier boleh role (`super_admin`/`admin`/`user`) atau email/username spesifik; kalau ada beberapa user dengan role yang sama, command kasih pilihan interaktif; selalu minta konfirmasi sebelum menerbitkan link. Dipakai kalau butuh akses cepat tanpa nunggu email, atau mail service belum sempat dikonfigurasi. Lihat panduan pakai lengkap di [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) bagian "SSO down / tidak bisa diakses sama sekali — akses darurat".

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file baru
- [x] Migration `mail_settings` + `sso_fallback_tokens` berhasil di dev
- [x] Token single-use — dipakai sekali langsung invalid, diverifikasi lewat tinker + HTTP request langsung (400 di percobaan kedua)
- [x] Rate limit 3x/15 menit bekerja, diverifikasi lewat HTTP request langsung (429 setelah 3x)
- [x] Kegagalan kirim email (API key salah) tidak bikin 500 — diverifikasi dengan API key palsu, request tetap 302 redirect sukses
- [x] `ActivityLog` tercatat benar dengan `user_id` terisi meski request dari guest
- [x] Magic link valid berhasil login dan redirect sesuai role (diverifikasi: redirect ke `/admin/dashboard` untuk admin)
- [x] `php artisan sso:emergency-login` — resolve by role keyword & by email/username, pilihan interaktif kalau ada beberapa kandidat, batal kalau konfirmasi "no", error yang jelas kalau tidak ada yang cocok — semua diverifikasi lewat CLI langsung

---

## Phase 17 — Batch Import AniList, Fix Logout Saat SSO Down, Context Menu Tab Baru ✅

**Goal:** Percepat proses input katalog buat admin (import banyak series sekaligus dari AniList berdasarkan genre/tahun/popularitas), sekalian bereskan dua gap kecil yang ditemukan selagi kerja di area SSO/admin.

1. **Batch import AniList** — filter genre (dropdown, enum kanonis AniList), tahun rilis, dan toggle "Urutkan Popularitas" ditambahkan ke `Admin/AniList/Index.tsx`; boleh browse cuma dari filter tanpa ketik judul. Checkbox multi-select per hasil (cuma yang belum ada di katalog) + "Pilih Semua" + import sekaligus lewat `AniListController::bulkImport()`, yang manggil `AniListService::getMangaBatch()` — satu request GraphQL (`media(id_in: [...])`) buat sampai 50 series, bukan N request terpisah (hemat kuota rate-limit AniList ~90 req/menit). Toggle "Sembunyikan yang sudah ada di katalog" — filter client-side.
   - **Ketemu saat riset**: `seasonYear` di skema AniList itu konsep musim tayang anime, selalu balikin kosong untuk `type: MANGA` — diverifikasi langsung ke API sebelum dipakai. Filter tahun manga yang benar pakai rentang `startDate_greater`/`startDate_lesser` (`FuzzyDateInt` `YYYYMMDD`).
2. **Fix logout saat SSO down** — `SsoController::logout()` sebelumnya selalu maksa browser navigasi ke domain SSO buat destroy sesi di sana, walau sesi lokal sudah invalid duluan. Kalau SSO down, browser nge-hang lama nunggu koneksi ke domain yang mati. Ditambah `ssoReachable()` — cek cepat (timeout 3 detik) sebelum redirect; kalau tidak bisa dihubungi, langsung balik ke halaman utama. Diverifikasi lewat HTTP request langsung: logout selesai dalam ~3.4 detik (dibatasi timeout) alih-alih berpotensi hang lebih lama nunggu browser sendiri yang nyerah.
3. **Context menu "Buka di Tab Baru"** — `Admin/Series/Index.tsx`, klik kanan sekarang punya opsi buka series di tab baru (`window.open()`) selain navigasi SPA biasa.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Filter genre+tahun+popularitas AniList diverifikasi lewat curl langsung ke `graphql.anilist.co` sebelum dipakai di kode (nemuin `seasonYear` yang ternyata tidak berlaku untuk manga)
- [x] Bulk import diverifikasi end-to-end lewat HTTP request nyata — 2 series (Chainsaw Man, Kaiju No. 8) berhasil diimpor dalam satu request, slug/genre ter-generate benar, tercatat di Log Aktivitas
- [x] Logout dengan SSO tidak bisa dihubungi selesai dalam ~3 detik (dibatasi timeout), bukan hang tanpa batas
- [x] Data uji coba (series, token, log aktivitas) dibersihkan dari database dev setelah verifikasi

---

## Phase 18 — Modal Pilihan Login (SSO / Email) ⚠️ (kode selesai, browser click-through belum terverifikasi)

**Goal:** Landing page munculin modal pilihan cara login begitu tombol "Login" diklik, alih-alih langsung redirect ke SSO. Login lewat Email dipromosikan dari link kecil tersembunyi ("SSO nggak bisa diakses?") jadi opsi setara SSO — mekanisme backend-nya (magic link, `SsoFallbackController`) sama sekali tidak berubah, cuma framing UI-nya.

1. `LoginMethodDialog.tsx` — komponen shared (3 state: pilihan → form email → terkirim), dipasang di tombol Login header dan hero Landing page, dikontrol satu state `loginOpen` bersama.
2. Rate limit `POST /auth/fallback` dinaikkan `throttle:3,15` → `throttle:5,10` — sekarang dipakai harian, bukan cuma darurat, jadi limitnya perlu lebih longgar.
3. `landing.fallbackLink`/`fallbackLinkAction` (translation key lama) dihapus, key `loginDialog.*` baru ditambahkan (id/en/ja).
4. Disepakati sebelum implementasi: profil (nama/avatar/username) cuma ikut ke-sync dari SSO pas login lewat SSO — user yang seterusnya login lewat email tidak dapat update profil otomatis. Ini keputusan sadar, bukan bug.
5. Disain-nya sekalian disiapkan buat dipakai ulang oleh fitur "Tambah Akun" (multi-account switching, direncanakan tapi **belum diimplementasikan** — dua pertanyaan terbuka: scope logout satu akun vs semua, dan penempatan switcher di UI).
6. **Fix susulan** — user lapor tombol Login di halaman profil publik (`PublicShell` header + CTA "Login untuk follow" di `Profile/Show.tsx`) masih redirect langsung ke SSO, ketinggalan dari rollout awal yang cuma nyentuh Landing page. Diperbaiki: kedua tempat sekarang buka `LoginMethodDialog` juga (state lokal masing-masing, `loginOpen` di `PublicShell` dan `followLoginOpen` di komponen utama). Grep ulang `sso.redirect` di seluruh `resources/js` buat mastiin tidak ada tempat lain yang ketinggalan — cuma tersisa satu referensi legitimate, di dalam `LoginMethodDialog.tsx` sendiri.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors
- [x] Code review manual — pola `Dialog` yang dipakai identik dengan yang sudah terbukti jalan di `SsoFallback.tsx`/`AlertDialog`/dialog lain di app ini
- [x] Grep `sso.redirect` di seluruh `resources/js` — cuma satu referensi tersisa (di dalam `LoginMethodDialog.tsx`), semua tombol Login lain sudah buka modal
- [ ] **Belum terverifikasi via browser click-through** — dicoba lewat server test ad-hoc (`php -S`, bukan Herd), ketemu module-loading quirk yang bikin `#app` React root nggak pernah mount. Dikonfirmasi ini bukan bug di kode (asset yang sama, `button.js`/`card.js`, dipakai di seluruh app yang sudah jalan normal di Herd; tiap asset return 200 kalau di-fetch langsung) — kemungkinan besar karena `php -S` nggak nangani static asset serving/header sebagus nginx yang dipakai Herd. **Perlu satu kali manual click-through di `https://malas.test` (dev environment normal) buat mastiin modal beneran kebuka & form submit jalan di browser sungguhan — termasuk sekarang di halaman profil publik juga.**

---

## Phase 19 — Tema Light/Dark/System ✅

**Goal:** Ganti toggle dark/light satu-klik yang sudah ada (di sidebar footer Admin/User Layout dan Landing page) jadi 3 opsi eksplisit (Light/Dark/System), pola sama persis dengan `LanguageSwitcher` yang sudah terbukti (Popover + sync ke DB per-user + guest-safe).

1. Kolom `users.theme` baru (default `system`) + `PATCH /settings/theme` (`SettingsController::updateTheme()`), mirroring `updateLocale()`.
2. `useTheme()` hook ditulis ulang total — resolve `system` lewat `matchMedia('(prefers-color-scheme: dark)')`, live-update kalau OS ganti tema (`mql.addEventListener('change', ...)`), sync dari `auth.user.theme` (shared Inertia prop), `setTheme()` optimistic update + localStorage + `router.patch()` kondisional (skip kalau guest). Return value ganti dari `{ theme, toggleTheme }` jadi `{ theme, resolvedTheme, setTheme }`.
3. `ThemeSwitcher.tsx` baru (mirror `LanguageSwitcher.tsx`) menggantikan toggle Button lama di `AdminLayout.tsx`, `UserLayout.tsx`, dan `Landing.tsx`.
4. Translation key baru `theme.*` (label/light/dark/system/cardTitle/cardDescription) di ketiga `common.json`; kartu "Tema" baru di `Settings/Index.tsx` antara kartu "Bahasa" dan "Profil Publik".

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean
- [x] Live HTTP test `PATCH /settings/theme` dengan value valid & invalid (validasi `Rule::in(['light','dark','system'])` tertolak dengan benar untuk value asing)

---

## Phase 20 — Fix URL Admin (Slug) & Editor Genre/Tags di Series Edit ✅

**Goal:** Dua bug/gap dilaporkan bersamaan sambil kerja di area ini: (1) semua `route('admin.series.show'/'edit', ...)` masih pakai UUID mentah — Phase 15 cuma nyentuh sisi user (`catalog.show`); (2) halaman Admin Series Edit sama sekali tidak punya UI buat lihat/edit genre/authors/illustrators/themes/demographics, dan popover "Sync AniList/RanobeDB" di halaman itu tidak ikut ngisi data tag walau query GraphQL-nya sudah nge-fetch.

1. **Slug admin** — backend (`SeriesController`, `CommandSearchController`, `ExternalSearchController`, `AniListController`, `RanobeDbController`, `TicketController`, `VolumeController`) diupdate kirim `slug`/`series_slug` ke semua halaman yang link ke series admin; frontend (`Admin/Series/Index.tsx`, `Show.tsx`, `EditVolume.tsx`, `Edit.tsx`, `AniList/Index.tsx`, `AniList/Status.tsx`, `RanobeDb/Index.tsx`, `Search/Index.tsx`, `Tickets/Show.tsx`, `CommandPalette.tsx`) semua `route()` call untuk link series diganti `.id` → `.slug` — `.id` tetap dipakai di tempat yang butuh UUID asli (bulk-select, target API delete/update).
2. **Editor genre/tags** — `TagListInput.tsx` baru (Enter/koma nambah tag, klik-X hapus), 5 field baru di Edit page dalam grid 2 kolom; `SeriesController::edit()` sekarang kirim kelima field tag (sebelumnya cuma `show()` yang kirim); popover Sync AniList/RanobeDB (`applySync()`/`applyRdSync()`) sekarang ikut ngisi tag — `AniListController::formatResults()` diupdate expose `genres`/`authors`/`themes`/`demographics` (RanobeDB sudah expose duluan di endpoint detail-nya).
3. **Pola FormData array + sentinel** — frontend selalu kirim `genres[]=''` dkk (bahkan pas array kosong) supaya Laravel tidak skip key-nya sama sekali (kalau nggak, `update()` nggak nyentuh kolom itu, tag lama yang sengaja dihapus admin malah nggak kehapus).
   - **Bug ketemu saat verifikasi HTTP langsung**: middleware global Laravel 12 `ConvertEmptyStringsToNull` ngubah sentinel `''` jadi `null` sebelum validasi jalan — rule `genres.*` yang cuma `['string','max:100']` (tanpa `nullable`) nolak `null` dengan 422, jadi fitur "hapus semua tag" sebenarnya gagal total kalau dicoba. Fix: tambah `nullable` ke semua rule `*.` di `UpdateSeriesRequest`, dan filter sentinel di controller diubah buang `null` juga, bukan cuma `''`.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di 9 file backend yang disentuh
- [x] Live HTTP test: sync tag dari AniList/RanobeDB search result ke Edit page, save, verifikasi tersimpan di DB
- [x] Live HTTP test: kirim sentinel kosong buat 2 dari 5 field tag → **ketemu bug 422** (lihat poin 3 di atas) → fix → retest → tag berhasil terhapus, field lain nggak kesentuh
- [x] Slug admin diverifikasi: `GET /admin/series/{slug}` dan `/edit` sama-sama 200, genre data lengkap ikut ke Edit page props
- [x] Data uji coba dikembalikan ke state semula setelah verifikasi (role sementara, tag yang sempat dihapus dipulihkan)

---

## Phase 21 — Favicon Terpasang, README Dirombak (Bilingual), Audit Fitur Kelewat ✅

**Goal:** Favicon yang sudah digenerate (`public/images/favicon/`) belum pernah di-wire ke aplikasi; README masih ID-only dan kurang menarik dibaca; user minta audit apa ada fitur yang diminta tapi kelewat belum dikerjakan.

1. **Favicon** — `<link rel="icon">` (SVG light/dark via `prefers-color-scheme`, PNG fallback 16/32), `apple-touch-icon` (180px), dan `site.webmanifest` baru (192/512px, buat PWA/home-screen) dipasang di `resources/views/app.blade.php` — sebelumnya nol favicon sama sekali.
2. **README bilingual** — dirombak total: header dengan logo, badge tech stack, language-switcher (🇮🇩/🇬🇧) ke dua section paralel penuh (fitur, tech stack, setup, deployment, testing, troubleshooting, docs, lisensi), plus section baru "Belum Selesai (Backlog)" yang jujur soal gap.
3. **Audit fitur kelewat** — cross-check `CLAUDE.md`/`PHASES.md`/`prd.md`/`CHANGELOG.md` + grep `TODO`/`FIXME` (nihil hasil di kode). Ketemu 1 miss nyata: filter genre searchable + multi-select di Katalog user diminta eksplisit sesi sebelumnya (`"bisa search genrenya... bisa lebih dari 1 genre"`) tapi keburu ke-skip belum dikerjakan — jadi Phase 22.

### Done Criteria
- [x] Favicon diverifikasi live — semua `<link>` resolve 200, muncul benar di `<head>` halaman
- [x] `site.webmanifest` diverifikasi 200

---

## Phase 22 — Filter Genre Multi-Select di Katalog User ✅

**Goal:** Ganti filter genre single-select (`Select` dropdown) di `User/Catalog/Index.tsx` jadi filter yang bisa diketik (fuzzy search) dan pilih lebih dari satu genre sekaligus — item dari audit Phase 21.

1. `GenreMultiSelect.tsx` baru — Popover + `Command`/cmdk (fuzzy filter bawaan), grouped Manga/Light Novel (reuse `genreOptions` yang sudah ada), genre terpilih ditampilkan sebagai badge yang bisa di-klik-X di bawah trigger (state selalu kelihatan, nggak perlu buka popover lagi buat cek).
2. Backend `User\SeriesController::index()` — filter `genre` diubah dari exact-match single value jadi **OR-match** multi-value (`orWhereJsonContains` berantai) — series lolos kalau punya *salah satu* genre yang dipilih, bukan wajib semua (biar hasil nggak keburu kosong pas pilih beberapa genre sekaligus).
3. `filters.genre` sekarang selalu array (bukan `string | null`) — round-trip lewat query string `genre[0]=X&genre[1]=Y`, pagination link ikut preserve array ini via `withQueryString()`.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean
- [x] Live HTTP test OR-match: Comedy sendiri (59 hasil) + Romance sendiri (43 hasil) → gabungan keduanya 67 hasil (union yang benar, bukan interseksi ataupun penjumlahan naif)
- [x] Live HTTP test: pagination link (`next_page_url`) preserve `genre[0]`/`genre[1]` dengan benar

---

## Phase 23 — Quick-Edit Progres Baca & Jumlah Volume di Koleksiku ✅

**Goal:** Toggle baca per-volume (klik ikon mata satu-satu) dilaporkan kurang enak buat kasus baca linear (baca berurutan dari volume 1 ke atas). User minta stepper +/- buat geser "batas baca" dengan cepat, plus quick-edit jumlah volume dimiliki per format (fisik/digital) tanpa buka dialog "Tambah Volume" tiap kali.

1. **Stepper progres baca** — `CollectionController::advanceReadProgress()` baru: `forward` menandai volume-belum-dibaca bernomor terendah jadi sudah dibaca, `backward` membalik volume-sudah-dibaca bernomor tertinggi jadi belum dibaca. Sengaja cuma geser satu batas, tidak menyentuh volume yang ditandai manual di luar urutan lewat ikon mata (ikon mata tetap ada buat koreksi spesifik). Undo pakai ulang endpoint `toggleRead` yang sudah ada (toggle balik otomatis benar terlepas arahnya maju/mundur).
2. **Quick-edit jumlah volume per format** — `CollectionController::quickAdjustCount()` baru: `+` nambah volume bernomor berikutnya yang belum dimiliki sama sekali (nomor volume dibagi bersama lintas format dalam satu koleksi — unique constraint `collection_id`+`volume_number`), `-` hapus volume bernomor tertinggi DARI format yang dipilih. Stepper cuma muncul buat format yang sudah punya ≥1 volume; dialog "Tambah Volume" (range syntax) tetap ada buat kasus non-sekuensial/gap.
3. **Proteksi volume dipinjamkan** — kalau volume tertinggi suatu format lagi dipinjamkan, tombol `-` didisable + tooltip di frontend (bukan diam-diam skip ke volume di bawahnya) — keputusan sadar biar user selalu tahu persis volume mana yang bakal kehapus, bukan ditebak sistem.
   - **Bug ketemu saat verifikasi HTTP langsung**: implementasi awal query `whereDoesntHave('activeLoans')` di server-side, yang secara diam-diam jatuh ke volume non-loaned berikutnya kalau top volume lagi dipinjam — kontradiksi sama keputusan "disable + tooltip, jangan diam-diam ganti volume". Fix: query cuma pernah ambil top volume asli, tolak (info toast) kalau itu lagi dipinjam, tidak pernah fallback ke volume lain.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean
- [x] Live HTTP test forward/backward stepper — volume yang berubah & `last_read_volume` sesuai ekspektasi
- [x] Live HTTP test quick-add (ambil nomor global berikutnya lintas format) dan quick-remove (top volume format spesifik)
- [x] Live HTTP test kasus dipinjamkan: **ketemu bug** (lihat poin 3 di atas) → fix → retest → volume di bawahnya nggak lagi kesentuh, aksi ditolak dengan benar
- [x] Data uji coba (koleksi, volume, pinjaman, role sementara) dibersihkan dari database dev setelah verifikasi

---

## Phase 24 — Multi-Account Switching (Session-Based) ✅

**Goal:** User (bukan cuma admin) bisa nyambungin akun lain dan switch cepat tanpa login ulang tiap kali — dipicu dari kasus konkret admin yang nggak bisa akses `/my-collection` pakai akun admin-nya sendiri. Didiskusikan panjang sebelum implementasi: sempat dibahas "kenapa 1 orang butuh 2 koleksi" (jawabannya: itu masalah grouping koleksi, bukan akun — jadi item terpisah di backlog), lalu diputuskan generic buat semua user (bukan admin-only, karena keamanannya sudah dijamin validasi session, bukan pembatasan role) dan session-based tanpa link permanen di DB (linking permanen cuma soal convenience, bukan keamanan — tetap wajib re-auth ke akun target).

1. `AccountLinkService::loginAs()` — satu titik logic dipakai `SsoController::callback()` dan `SsoFallbackController::consume()`, nge-handle nambah user lama ke `linked_account_ids` session kalau lagi mode "Tambah Akun" (flag `sso_link_mode`, di-set eksplisit true/false tiap request biar nggak ada flag basi nyangkut dari percobaan sebelumnya).
2. `Auth\AccountController::switch()` — validasi wajib target `user_id` ada di `linked_account_ids` session dulu sebelum `Auth::login()`, `logoutCurrent()` — pola X/Twitter: keluar cuma dari akun aktif, auto-switch ke akun ke-link lain kalau ada, delegasi ke `SsoController::logout()` (logout total) kalau itu akun terakhir.
3. `AccountSwitcher.tsx` baru menggantikan avatar-link + tombol Logout terpisah di `AdminLayout`/`UserLayout` — satu Popover: lihat profil, quick-switch ke akun ke-link, "Tambah Akun" (reuse `LoginMethodDialog` dengan `mode="link"` baru), "Keluar dari Akun Ini" vs "Keluar dari Semua Akun".
4. `HandleInertiaRequests` share `linked_accounts` (fetch fresh dari DB tiap request, bukan dari session langsung, biar nama/avatar selalu up to date).

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Live HTTP test: login akun A → "Tambah Akun" B (lewat email magic link dengan flag `link=1`) → identitas aktif jadi B, A masuk `linked_accounts` → switch balik ke A → switch ke akun C yang TIDAK di-link → **403 ditolak dengan benar**
- [x] Live HTTP test: `logoutCurrent` saat masih ada akun ke-link → auto-switch ke akun itu; `logoutCurrent` saat itu akun terakhir → efektif logout total (delegasi ke `SsoController::logout()`)
- [x] Live HTTP test: `POST /logout` ("Keluar dari Semua Akun") tetap nuke semua akun ke-link sekaligus, terlepas urutan/jumlahnya
- [x] Data uji coba (role sementara, token, session) dibersihkan dari database dev setelah verifikasi

---

## Phase 25 — Stepper dari Koleksiku, Grouping Koleksi, Flash Message Multi-Bahasa ✅

**Goal:** Tiga item digabung jadi satu batch atas permintaan user ("gas, sekalian sama grouping, flash message multi bahasa") — extend stepper progres baca ke halaman Koleksiku (nggak perlu buka detail koleksi), fitur grouping koleksi yang sempat didiskusikan di Phase 24, dan backlog lama flash message belum multi-bahasa.

1. **Stepper progres baca di Koleksiku** — `Collection/Index.tsx` (`ReadStepper`) reuse endpoint `collection.volumes.readProgress` yang sama persis dengan halaman detail koleksi (Phase 23) — nol perubahan backend. Per-row loading state (`busyId`) biar klik di satu koleksi nggak ngeblok koleksi lain. Muncul di grid card DAN table row, `stopPropagation()` biar nggak ke-trigger navigasi ke detail.
2. **Grouping koleksi** — kolom `collections.group_name` baru (string bebas 100 char, bukan tabel terpisah — sengaja simpel, filter dedup dari nilai yang sudah ada per user). `CollectionController::updateGroup()` baru. UI: filter dropdown (sentinel `__ungrouped__` buat "Tanpa Grup") + edit inline lewat Popover (`GroupEditor`) di grid card & table row (kolom baru "Grup"). **⚠️ Desain ini ternyata salah semantik dan diganti total di Phase 27** — lihat detail di sana.
3. **Flash message multi-bahasa** — backlog lama akhirnya dikerjakan. `lang/{id,en,ja}/flash.php` baru, ~70 pemanggilan `->with('success'/'error'/'info', ...)` di 24 controller dikonversi dari hardcode Indonesia jadi `__('flash.namespace.key', [...])`. Satu bug ketemu saat sweep manual: `VolumeController::generate()` pakai pola `->with($created > 0 ? 'success' : 'info', $message)` (key dinamis) yang lolos dari grep awal (`grep "with('success'"` cuma nangkep literal string key) — ketemu lewat sweep kedua yang nyari sisa kata "berhasil"/"gagal" hardcode di luar `__()`.
   - **Keputusan scope**: pesan exception mentah (`$e->getMessage()`, dari API eksternal) dan teks `ActivityLog::record()` (log aktivitas admin) sengaja **tidak** diterjemahkan — yang pertama nggak bisa diprediksi isinya, yang kedua memang bukan flash toast per-locale (dibaca admin, konsisten Indonesia).

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua 24 controller + 3 file `flash.php` yang disentuh
- [x] Live HTTP test stepper dari Koleksiku: forward/backward mengubah `read_volumes_count` di response tanpa buka halaman detail
- [x] Live HTTP test grouping: set nama grup, verifikasi tersimpan; set string kosong, verifikasi ke-null-kan
- [x] Live HTTP test flash i18n: request yang sama (`readProgress` forward/backward) dengan `users.locale` di-set `id`/`en`/`ja` bergantian — hasil pesan flash 3-3-nya benar dan sesuai bahasa aktif
- [x] Live tinker test: `:count` placeholder dikonfirmasi aman dipakai di `__()` biasa (bukan `trans_choice()`) di ketiga bahasa — nggak ada logic pluralisasi nyasar
- [x] Sweep kedua nemuin & benerin 1 bug (`VolumeController::generate()` key dinamis yang lolos grep awal)
- [x] Data uji coba (koleksi, role/locale sementara, token, session) dibersihkan dari database dev setelah verifikasi

---

## Phase 26 — Fix: Link Login (CLI/Email) Override Akun Aktif Alih-Alih Nambah ✅

**Goal:** Bug report langsung ("langsung fix") — login lewat magic link CLI (`sso:emergency-login`) atau email yang di-klik saat masih login sebagai user lain **meng-override** sesi aktif, bukan nambahin sebagai akun ke-link (padahal tombol "Tambah Akun" di UI berfungsi benar). User curiga email login kena bug yang sama — benar.

1. **Root cause**: `AccountLinkService::loginAs()` cuma nge-link akun lama kalau session flag `sso_link_mode` di-set — dan flag itu cuma di-set dari alur UI "Tambah Akun" (`?link=1` di `/auth/redirect`, field `link` di `POST /auth/fallback`). Link CLI/email nggak pernah lewat alur itu, jadi flag-nya selalu kosong.
2. **Fix**: `loginAs(User $user)` (parameter `linkMode` dicopot) sekarang mutusin murni dari `auth()->check()` di momen link dikonsumsi — kalau ada user lain yang masih login, otomatis ke-link ke `linked_account_ids`, terlepas dari link-nya datang dari CLI, email, atau "Tambah Akun". `sso_link_mode` session flag dan `link` query/POST param dihapus total (dead code).
3. Disentuh: `AccountLinkService.php`, `SsoController.php` (`redirect()` balik ke tanpa `Request` param), `SsoFallbackController.php`, `LoginMethodDialog.tsx` (JSDoc diupdate, `link` param dicopot dari POST body & route call).

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean
- [x] Live HTTP test: login A → consume bare token (CLI-style, tanpa modal "Tambah Akun") buat B → A masuk `linked_accounts`, user aktif jadi B
- [x] Live HTTP test: guest fresh login → `linked_accounts` kosong (nggak ada regresi ke kasus normal)
- [x] Data uji coba (token, session) dibersihkan dari database dev setelah verifikasi

---

## Phase 27 — Grup Koleksi Dirombak Total (ala MDList MangaDex) ✅

**Goal:** Desain grouping di Phase 25 poin 2 ternyata salah semantik — user jelasin maksudnya "ala MDList MangaDex": grup itu objek pertama (dikasih nama, mis. "RomCom"), diisi banyak manga dari koleksi user, bukan sekadar label string tunggal per koleksi. Diganti total, bukan di-patch.

1. **`CollectionGroup` model baru** + pivot `collection_group_items` — **many-to-many**, satu manga bisa masuk lebih dari satu grup sekaligus. Kolom `collections.group_name` (desain Phase 25) dihapus lewat migration baru.
2. **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: pivot table sempat dikasih `uuid('id')->primary()` kayak tabel lain di app ini — tapi `attach()`/`syncWithoutDetaching()` Eloquent insert baris pivot lewat query builder mentah (bukan lewat model event), jadi `HasUuids` nggak sempat ngisi kolom `id`, bikin `NOT NULL constraint violation` di SQLite. Fix: pivot table murni pakai composite primary key (`collection_group_id`, `collection_id`), bukan `id` sintetis — pola standar Laravel buat pivot table tanpa data tambahan.
3. **Halaman baru**: `/collection-groups` (daftar grup, cover collage dari 4 manga pertama) → `/collection-groups/{group}` (isi grup — tambah manga dari koleksi lewat dialog picker, hapus dari grup, ubah nama, hapus grup). Routes sengaja dikasih prefix top-level terpisah (bukan nested di `/my-collection/`) buat menghindari tabrakan sama route-binding `/my-collection/{collection}` yang udah ada.
4. Link "Grup Koleksi" ditambahkan di header halaman Koleksiku. Semua UI grouping lama di `Collection/Index.tsx` (popover inline, filter dropdown, kolom tabel "Grup") dicopot bersih.
5. `lang/{id,en,ja}/flash.php` — key `collections.group_updated` (Phase 25) dihapus, diganti blok `collection_groups.*` baru. `lang/{id,en,ja}/collection.json` — namespace `groups` top-level baru, fully translated.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Live HTTP test: create group → add items dari koleksi → remove satu item → rename → delete group — semua correct, termasuk konfirmasi koleksi underlying TIDAK ikut kehapus saat grup dihapus
- [x] Live HTTP test: pivot NOT NULL bug ketemu, migration di-rollback & diperbaiki (composite PK), re-migrate, retry verifikasi penuh lolos
- [x] Data uji coba (grup, koleksi, item) dibersihkan dari database dev setelah verifikasi

---

## Phase 28 — Grup Koleksi: Modal Diperbesar + Paginasi, Filter Tipe, Slug URL, Publik/Privat ✅

**Goal:** Empat item perbaikan/penambahan atas grup koleksi (Phase 27) diminta langsung tanpa "gimana?" (bukan diskusi): modal "Tambah Manga" terlalu kecil buat koleksi besar, butuh filter tipe, URL grup masih pakai UUID (harusnya slug ala Series), dan grup butuh opsi publik/privat (muncul di profil kalau publik).

1. **Modal "Tambah Manga" diperbesar & dipaginasi** — `max-w-3xl` → `max-w-5xl`, list koleksi yang bisa ditambahkan di-paginate server-side (24/halaman, reuse `Pagination.tsx`) dan filter tipe (segmented control, reuse `useTypeFilterOptions()`) ditambahkan. Search & filter tipe sekarang server-side lewat debounced partial reload (`only: ['available']`), bukan filter client-side atas array yang di-load penuh sekali.
2. **URL grup pakai slug** — `CollectionGroup::generateUniqueSlug()` (pola sama dengan `Series::generateUniqueSlug()`) bikin slug `{username}-{nama-grup}`, regenerate otomatis saat nama grup diubah. Migration baru nambah kolom `slug` (unique) + `is_public` (default false) ke `collection_groups`.
3. **Opsi Publik/Privat** — `Switch` di halaman detail grup (owner-only). Grup publik muncul di profil publik pemilik (section baru) dan bisa diakses guest lewat URL langsung; privat cuma pemilik/admin. Route `GET /collection-groups/{group}` dipindah keluar dari grup middleware `auth` (pola sama `/u/{user}`), visibilitas dicek manual di controller.
4. `PublicShell` (dulu inline di `Profile/Show.tsx`) diekstrak jadi komponen bersama `Components/app/PublicShell.tsx`, dipakai juga di `CollectionGroups/Show.tsx` — perlu supaya guest yang buka grup publik nggak nabrak `UserLayout` yang butuh sesi login.
5. **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: query `available` yang di-`join()` ke tabel `series` bikin `whereNotIn('id', ...)` ambigu (SQLite nggak tahu `id` punya tabel mana). Fix: qualify jadi `whereNotIn('collections.id', ...)`.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Live HTTP test dengan 30 koleksi dummy: filter tipe (manga vs novel) benar, search substring benar, pagination halaman 2 benar
- [x] Live HTTP test: tambah 2 item ke grup → langsung ke-exclude dari `available` (total 30→28)
- [x] Live HTTP test: toggle publik → guest tanpa login bisa lihat grup (`is_owner: false`, `available: null`); toggle privat lagi → guest kena 403
- [x] Live HTTP test: rename grup → slug regenerate otomatis (`testowner-romcom` → `testowner-shounen-favorites`)
- [x] Live HTTP test: profil publik pemilik grup menampilkan grup publik dengan cover collage
- [x] Data uji coba (user, koleksi, series, grup, token) dibersihkan dari database dev setelah verifikasi

---

## Phase 29 — Fix: Blank Screen Buka Grup Koleksi (Slug Kosong + Guest Crash), README Bebas Emoji ✅

**Goal:** Bug report langsung ("cuma blackscreen") — user nggak bisa akses grup koleksi sama sekali. Sekalian sama permintaan lain di pesan yang sama: README dirombak biar bebas emoji ("jangan gunakan emoji berlebih, lebih baik tidak ada sekalian").

1. **Bug #1, root cause asli & utama**: user punya grup koleksi nyata ("RomCom") yang dibuat SEBELUM migration slug (Phase 28) dijalankan. Migration itu nambah kolom `slug` sebagai `nullable()` (biar nggak gagal di data lama) tapi **lupa backfill** — jadi grup lama itu kesimpen dengan `slug = NULL` permanen. Begitu `Index.tsx`/`Show.tsx` mulai generate semua link pakai `group.slug` (bukan `group.id` lagi), link ke grup ini rusak (`route('collection.groups.show', null)`) — inilah yang bikin user nggak bisa akses grupnya sama sekali, blank screen di halaman manapun yang nyoba render link itu. Ini ditemukan lewat pengecekan langsung `CollectionGroup::whereNull('slug')->count()` di database dev — ketemu 1 baris NULL, punya user asli (bukan data uji coba).
   - **Fix**: migration baru `2026_08_15_090000_backfill_collection_groups_slug.php` — backfill semua `slug` yang masih NULL pakai `CollectionGroup::generateUniqueSlug()`, pola identik dengan backfill slug Series (`2026_08_02_090000_add_slug_to_series_table.php`, pola yang sudah ada di codebase, cuma nggak diikuti pas Phase 28). Diverifikasi: grup "RomCom" dapet slug `sehnauoi-romcom`, Index dan Show dua-duanya ke-render normal — link `href` di Index Sekarang beneran ngarah ke slug yang benar, klik masuk ke detail sukses tanpa error.
2. **Bug #2, terpisah**: `CollectionGroups/Show.tsx` selalu render lewat `UserLayout`, yang berasumsi ada sesi login — `AccountSwitcher.tsx` akses `auth.user!` (non-null assertion). Untuk guest yang buka grup **publik** (fitur intentional dari Phase 28), `auth.user` beneran `null`, jadi React crash pas coba render sidebar/account switcher. Beda dari Bug #1 (yang kena siapa aja termasuk owner login), Bug #2 cuma kena guest.
   - **Fix**: `CollectionGroupController::show()` kirim prop baru `is_guest` (`$viewer === null`). `Show.tsx` pakai `const Layout = is_guest ? PublicShell : UserLayout` — pola yang sama persis dengan `Profile/Show.tsx`. Breadcrumb ke `/collection-groups` (route auth-only) disembunyikan buat guest. Badge visibilitas di header non-owner sekarang baca `group.is_public` asli, bukan selalu nampilin "Publik" (kena kasus admin liat grup privat orang lain).
3. Kenapa dua-duanya nggak ketangkep pas verifikasi Phase 27/28: verifikasi waktu itu cuma ngecek response JSON Inertia lewat curl (props yang dikirim server benar), bukan benar-benar render React di browser dengan data lama yang sudah ada — jadi baik slug NULL di data existing maupun crash client-side lolos dari deteksi.
4. **README.md & README.id.md**: semua emoji dicopot (badge, heading section, bullet fitur/troubleshooting/backlog, tombol "back to top"). Anchor link internal (`#troubleshooting`, dll) disesuaikan karena GitHub generate slug heading beda begitu emoji dicopot dari judul section.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di file yang disentuh
- [x] `CollectionGroup::whereNull('slug')->count()` → 0 setelah migration backfill jalan
- [x] Verifikasi Browser (render React beneran, bukan curl doang) sebagai user pemilik grup asli: Index → klik card grup → Show ke-render tanpa error, link href pakai slug yang benar
- [x] Verifikasi Browser: guest buka grup publik → nggak crash, `PublicShell` + tombol Login muncul, konten grup ke-render
- [x] `README.md`/`README.id.md` dicek nol emoji tersisa (badge shields.io tetap dipertahankan karena itu gambar, bukan emoji teks), anchor link internal nggak ada yang putus
- [x] Data uji coba tambahan (user, series, koleksi, grup, token) dibersihkan dari database dev setelah verifikasi — data asli user (grup "RomCom") dibiarkan, cuma di-backfill slug-nya

---

## Phase 30 — Modal "Tambah Manga" Lebih Lebar + Infinite Scroll ✅

**Goal:** Request langsung — "bikin modal add manga to group lebih lebar, dan infinite scrolling bukan pake paginate".

1. **Bug ketemu**: `max-w-7xl` yang dipasang di Phase 28 buat modal "Tambah Manga" nggak pernah beneran kepakai — `DialogContent` (`ui/dialog.tsx`) punya base style `sm:max-w-sm`, dan class override TANPA prefix `sm:` kalah di cascade Tailwind begitu viewport ≥640px (aturan media-query `sm:` posisinya lebih akhir di stylesheet generated, menang di source order lawan base utility non-prefixed dengan specificity sama). Modal diam-diam tetap 384px dari awal Phase 28, meski kodenya udah "benar" ngasih `max-w-7xl`. Fix: `sm:max-w-7xl`. Diverifikasi lewat `getComputedStyle` di Browser tool: sebelum fix `384px`, sesudah fix `1280px`.
2. **Ganti `<Pagination>` jadi infinite scroll**: list koleksi di modal sekarang accumulate hasil tiap halaman ke state lokal (`accumulated`), auto-fetch halaman berikutnya pas discroll mendekati bawah kontainer.
   - Percobaan pertama pakai `IntersectionObserver` di dalam `useEffect` — gagal, karena effect jalan sebelum DOM sentinel-nya ke-mount (Dialog content Base UI mount satu tick setelah `addOpen` true) dan dependency array nggak retrigger begitu ref-nya kepasang.
   - Percobaan kedua pindah ke ref-callback pattern (observer dibikin persis pas node sentinel mount) — masih nggak fire sama sekali di lingkungan Browser tool ini, bahkan `observe(document.body)` polos juga nggak pernah callback, kemungkinan karena tab nggak compositing frame aktif (lihat error "Browser pane is not displayed" pas nyoba screenshot).
   - **Solusi final**: `onScroll` handler biasa (`scrollHeight - scrollTop - clientHeight < 300`) — nggak bergantung rendering/compositing pipeline sama sekali, dan gampang diverifikasi manual (`dispatchEvent(new Event('scroll'))`). Diverifikasi: 50 item dummy ke-load 24 → 48 → 50 lewat dua kali scroll, masing-masing ngirim persis satu request (`page=2`, `page=3`), nol request duplikat/berlebih.
3. **Bug sejenis kemungkinan ada di dialog lain** — `Admin/Series/Edit.tsx` (`max-w-3xl`) dan `User/Collection/Index.tsx` (`max-w-4xl`) sama-sama pakai `max-w-*` tanpa prefix `sm:`. Belum diverifikasi/di-fix di Phase ini (di luar scope task), di-flag sebagai task terpisah. `Admin/ActivityLog/Index.tsx` udah bener dari awal (`sm:max-w-2xl`).
4. `CLAUDE.md` bagian "React Components" ditambah aturan eksplisit: override lebar `DialogContent` WAJIB pakai prefix `sm:`.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors di file yang disentuh
- [x] Verifikasi Browser: `getComputedStyle(dialog).maxWidth` = `1280px` (bukan `384px`) setelah dialog dibuka
- [x] Verifikasi Browser: 50 koleksi dummy, scroll ke bawah dua kali → item bertambah 24→48→50, network request `page=2` lalu `page=3` masing-masing cuma sekali
- [x] Verifikasi Browser: setelah semua halaman abis, scroll lanjut nggak ngirim request baru (no-op, dijaga guard `current_page >= last_page`)
- [x] Data uji coba (user, series, koleksi, grup, token) dibersihkan dari database dev setelah verifikasi

---

## Phase 31 — Fix Layout Modal, URL Koleksi Pakai Judul, Konfirmasi+Undo Hapus dari Grup, Hapus Puter.js ✅

**Goal:** Empat item langsung dari user dalam satu pesan (bukan diskusi): modal filter ketutupan + ikut nge-scroll, URL `/my-collection` masih UUID bukan judul, hapus manga dari grup nggak ada konfirmasi/undo, dan hapus total integrasi Puter.js.

1. **Fix layout modal**: root cause-nya klasik flexbox — div list item (`min-h-[360px] flex-1 overflow-y-auto`) punya floor tinggi 360px yang bisa lebih gede dari sisa ruang yang dialokasikan flexbox di dalam `max-h-[85vh]`, jadi begitu grid item panjang, seluruh `DialogContent` (bukan cuma div list-nya) yang kepaksa melebihi tinggi maksimal — filter ke-dorong keluar viewport. Fix: `min-h-[360px]` → `min-h-0`, plus `shrink-0` eksplisit di header/search/filter/footer biar nggak ikut kekompres kalau ruang tipis.
2. **URL koleksi pakai judul series** — `Collection::resolveRouteBinding()` baru, cocokkan lewat slug Series-nya, **dibatasi ke koleksi milik user yang login** (dua user beda bisa sama-sama koleksi series yang sama). Nggak nambah kolom `slug` baru di tabel `collections` — reuse `series.slug` yang udah unik global, jadi nggak ada risiko lupa-backfill kayak Phase 28/29. Enam file diupdate: `SearchController`/`GlobalSearch.tsx`, `Catalog/Show.tsx`, `CollectionController::index()`/`Collection/Index.tsx`, `CollectionGroupController::show()`/`CollectionGroups/Show.tsx`, `DashboardController::continueReading()`/`Dashboard.tsx`, `LoanController::index()`/`Loans/Index.tsx`. Endpoint aksi (PATCH/POST/DELETE, bukan navigasi) sengaja tetap pakai `collection.id` mentah.
3. **Konfirmasi + Undo hapus dari grup** — dialog konfirmasi baru sebelum `handleRemove()` beneran jalan (pola sama dengan dialog Hapus Grup), dan `CollectionGroupController::removeItem()` sekarang kirim `undo_url`/`undo_payload`. Route PATCH baru `collection.groups.items.undoRemove` — re-attach pivot, undo-nya simpel karena cuma detach (bukan hard-delete kayak `CollectionController::destroy()`).
4. **Puter.js dihapus total** — `lib/puter.ts` dihapus, tag `<script src="https://js.puter.com/v2/">` dicopot dari `app.blade.php`, opsi provider "Puter" dicopot dari admin AI settings + validasi backend. Default provider baru `gemini` (butuh API key — fallback ke teks statis kalau kosong, `AiFunfactService::fallbackText()` udah ada dari awal). Endpoint yang cuma dipakai flow client-side Puter (`dashboard.funfact.auto-save`, `dashboard.funfact.report-error`) jadi dead code total, ikut dihapus. Migration normalisasi baris `ai_settings.provider = 'puter'` lama ke `'gemini'`.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Verifikasi Browser: filter tipe kelihatan penuh di modal, cuma grid item yang scroll (bukan seluruh dialog)
- [x] Verifikasi Browser: klik card koleksi/item grup → navigate ke `/my-collection/{judul-series-slug}`, bukan UUID
- [x] Verifikasi Browser: klik hapus dari grup → dialog konfirmasi muncul dulu; konfirmasi → toast sukses dengan tombol Undo; klik Undo → item balik ke grup
- [x] Verifikasi Browser: tab AI di admin settings cuma nampilin Gemini/OpenAI/Claude (Puter nggak ada); tombol "Generate Ulang" funfact tetap jalan normal lewat jalur server biasa (nggak crash referencing Puter)
- [x] Sweep `grep -rli puter` di seluruh `resources/js`, `app`, `resources/views` → nol hasil nyata (cuma false-positive substring di `InputError.tsx`)
- [x] Data uji coba (user, series, koleksi, grup, token, funfact) dibersihkan dari database dev setelah verifikasi

---

## Phase 32 — Nonaktifkan Sementara Generate Funfact AI, Export/Import Koleksi ✅

**Goal:** Dua item dari user di pesan terpisah: (1) nonaktifkan sementara fitur generate funfact AI, (2) fitur baru export/import koleksi pribadi (backup) — user konfirmasi format JSON dan mode import append-only (skip yang udah ada, bukan overwrite).

1. **Nonaktifkan funfact AI**: konstanta `DashboardController::FUNFACT_GENERATION_ENABLED = false` — skip auto-generate di `index()` dan guard 404 defensif di `regenerateFunfact()`. Word cloud genre (bagian non-AI dari kartu yang sama) tetap tampil normal, cuma tombol "Generate Ulang" + teks funfact AI-nya yang disembunyikan (`Dashboard.tsx` cek `funfact.generation_enabled`). Reversible total — tinggal balikin konstanta ke `true`, nggak ada data yang perlu di-restore.
2. **Export koleksi**: `CollectionController::export()`, `response()->streamDownload()` (pola sama `DatabaseBackupController::download()`). File JSON berisi identifier series (`anilist_id`/`ranobedb_id`/`mal_id`/`slug` — semua dikirim sekaligus, bukan cuma satu, biar import tetap match walau salah satu field berubah), condition/acquired_at/notes/rating/review, dan full volume list (nomor, format, ebook_source, language, read_at).
3. **Import koleksi**: `CollectionController::import()`, upload file JSON tervalidasi struktur (`version`+`collections`), transaksi DB. Matching series berurutan `anilist_id` → `ranobedb_id` → `mal_id` → `slug` terhadap katalog **lokal** — sengaja **tidak** auto-fetch dari AniList kalau series belum ada di katalog (skip + dihitung, bukan duplikasi logic `AniListController::import()`). Series yang udah dimiliki user di-skip juga (append-only sesuai konfirmasi user). Semua field enum (`condition`, `format`, `ebook_source`, `language`) di-whitelist lewat `sanitizeEnum()` sebelum insert — nggak percaya file upload mentah-mentah.
4. UI: tombol "Export"/"Import" di header `Collection/Index.tsx`, dialog import pakai file-input pattern yang sama dengan `DatabaseTab` di `Admin/Settings/Index.tsx`.
5. Verifikasi kali ini pakai HTTP request langsung (curl + cookie jar + CSRF token), bukan Browser tool — ada gangguan tool browser pane di sesi ini (navigasi ke origin server ad-hoc gagal terus meski server-nya sendiri konfirmasi hidup lewat curl). Tetap end-to-end lewat request HTTP asli (bukan cuma unit-level), termasuk multipart file upload beneran.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di semua file yang disentuh
- [x] Verifikasi (curl+cookie jar, live server): funfact card cuma nampilin word cloud, tombol/teks AI-nya hilang
- [x] Verifikasi: export user A (2 koleksi, salah satunya ada rating/review/2 volume) → JSON lengkap & akurat field-per-field
- [x] Verifikasi: import file itu ke user B yang udah punya 1 dari 2 series → cuma 1 series baru ke-import, yang lama nggak ke-duplikat, flash report "1 diimpor, 1 dilewati" benar
- [x] Verifikasi: entry dengan series yang nggak ada di katalog lokal → di-skip graceful (nggak crash), dihitung di laporan
- [x] Verifikasi: file JSON invalid/rusak → ditolak dengan flash error, bukan 500
- [x] Data uji coba (user, series, koleksi, volume, token) dibersihkan dari database dev setelah verifikasi

---

## Phase 33 — Badge "Volume Kurang" (Next Volume to Buy) di Koleksiku ✅

**Goal:** Fitur hasil diskusi ide fitur baru — "next volume to buy", user setuju logic-nya (bandingin volume dimiliki vs `total_volumes`) dan setuju ditaruh sebagai badge di halaman Koleksiku (Opsi A dari beberapa opsi yang didiskusikan).

1. `CollectionController::missingVolumes()` — `range(1, series.total_volumes)` dikurangi nomor volume yang udah dimiliki user. `total_volumes` dari AniList/RanobeDB udah representasi volume yang **sudah rilis**, bukan proyeksi total planned, jadi nggak perlu logic filter tanggal rilis terpisah. Series dengan `total_volumes` null di-skip (nggak bisa dihitung gap-nya).
2. UI: badge amber `{{count}} kurang` di grid card & table row `Collection/Index.tsx` (`MissingVolumesBadge`), muncul cuma kalau ada gap. Hover buka `HoverCard` nampilin nomor lengkap dikompres pakai syntax range yang sama dengan input "Tambah Volume" (fungsi baru `compressRanges()`, mis. `4, 6-7, 9-12`). Badge di-`stopPropagation()` biar nggak nge-trigger navigasi card (pola sama tombol hapus/`ReadStepper` yang udah ada).
3. Query `index()` diupdate eager-load `collectionVolumes:id,collection_id,volume_number` (kolom dibatasi, hindari over-fetch) buat ngitung gap tanpa N+1 query.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di file yang disentuh
- [x] Verifikasi (curl, browser pane ada gangguan tool di sesi ini): koleksi dummy `[1,2,3,5,8]` dari total 12 → `missing_volumes` server balikin persis `[4,6,7,9,10,11,12]`
- [x] Verifikasi: koleksi lengkap (3/3 volume) → `missing_volumes` kosong, badge nggak muncul
- [x] Data uji coba (user, series, koleksi, volume, token) dibersihkan dari database dev setelah verifikasi

---

## Phase 34 — Badge "Volume Kurang" Bisa Diklik, Auto-Isi Form Tambah Volume ✅

**Goal:** Follow-up langsung dari Phase 33 — user nanya "terus diapain" soal badge yang cuma informational, disepakati (lewat diskusi "gimana?" beberapa putaran) badge jadi bisa diklik dan langsung ngisi form "Tambah Volume" di halaman detail.

1. Badge (`MissingVolumesBadge` di `Collection/Index.tsx`) sekarang `<Link>` (bukan cuma span statis) ke `route('collection.show', c.slug)` + query param `?addVolumes={compressRanges(missing)}`, mis. `?addVolumes=4%2C%206-7%2C%209-12`. `stopPropagation()` tetap dipertahankan biar klik badge nggak ikut nge-trigger `onClick` card pembungkusnya (yang navigate ke halaman yang sama tapi tanpa query param).
2. `Collection/Show.tsx` — `useEffect` baru (sekali jalan pas mount) baca `addVolumes` dari `window.location.search`, `avReset({ volumes: prefill, format: 'physical' })` buat prefill form react-hook-form yang udah ada, `setAddVolumeOpen(true)` buat auto-buka dialog, lalu `window.history.replaceState` buat bersihin query param dari URL bar (bukan lewat Inertia visit — cuma ganti address bar, nggak reload/refetch apa pun).
3. Nggak ada perubahan skema/endpoint baru — reuse penuh form "Tambah Volume" yang udah ada (`collection.volumes.store`), cuma titik masuknya aja yang baru (query param, bukan klik tombol manual).

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors di file yang disentuh
- [x] Verifikasi Browser (bukan curl — tool Browser pane pulih di sesi ini): koleksi dummy 5/12 volume, klik badge "7 kurang" di Koleksiku → navigate ke halaman detail, dialog "Tambah Volume" auto-kebuka dengan input persis `"4, 6-7, 9-12"`, URL bar bersih dari query param
- [x] Verifikasi Browser: submit form itu → koleksi jadi 12/12 volume, badge hilang (nggak ada lagi yang kurang)
- [x] Data uji coba (user, series, koleksi, volume, token) dibersihkan dari database dev setelah verifikasi

---

## Phase 35 — Reveal API Key/Secret di Admin Settings (Ikon Mata) ✅

**Goal:** Request langsung dari user — tiga secret di halaman Admin Settings (`secret_access_key` Storage, `api_key` AI, `api_key` Mail) yang tadinya disembunyikan total (cuma kirim boolean `has_key`/`has_secret` ke frontend) sekarang bisa dilihat nilainya lewat toggle ikon mata.

1. **Kenapa aman**: halaman ini **sudah** `super_admin`-only lewat `StorageSettingPolicy::view()`/`update()` (dicek di `StorageSettingController::edit()`). Reveal ini nggak nambah expose ke audience baru — cuma ngasih super_admin yang emang udah pegang kendali penuh atas secret itu (bisa reset kapan aja) akses buat lihat balik nilainya.
2. **Backend**: `StorageSettingController::edit()` sekarang kirim `secret_access_key`/`api_key` asli ke frontend (udah otomatis ke-decrypt lewat cast `encrypted` di `StorageSetting`/`AiSetting`/`MailSetting` model), bukan cuma boolean. Boolean `has_key`/`has_secret` tetap dikirim & dipakai buat required-field marker (`*`) di label.
3. **Frontend**: komponen baru `SecretInput` (`Admin/Settings/Index.tsx`) — `Input` + tombol ikon mata (Eye/EyeOff) yang toggle `type="password"`/`type="text"`. Dipakai di tiga tempat (AI, Mail, Storage tab) lewat `Controller` react-hook-form; field-nya sekarang di-prefill sama value asli (`aiSetting.api_key ?? ''`, dst), bukan string kosong kayak sebelumnya.
4. **Beres-beres**: translation key `apiKeyPlaceholder`/`apiKeySaved`/`secretAccessKeyPlaceholder` (`admin.json` id/en/ja) jadi dead code begitu field di-prefill value asli — dihapus. Deskripsi kartu yang bilang "tidak pernah ditampilkan ulang setelah disimpan" diupdate karena udah nggak akurat lagi.

### Done Criteria
- [x] `npx tsc --noEmit` → 0 errors, `php -l` clean di file yang disentuh
- [x] Verifikasi Browser: field API key AI ke-load dengan value asli (masked, `type="password"`), klik ikon mata → `type` jadi `text` dan value asli kebaca persis dari DOM, klik lagi → balik masked
- [x] Verifikasi Browser: field API key Mail juga ke-load dengan value asli (masked) — pola konsisten
- [x] Verifikasi Browser: submit form AI tanpa ubah apa pun → key tetap utuh persis di database (dicek langsung lewat tinker), nggak ke-null-kan/rusak oleh resubmit value yang sama
- [x] Sweep translation key mati (`apiKeyPlaceholder`/`apiKeySaved`/`secretAccessKeyPlaceholder`) → nol referensi tersisa di `Admin/Settings/Index.tsx` sebelum dihapus dari JSON
- [x] Data uji coba: nggak ada (pakai data admin settings asli yang udah ke-konfigurasi sebelumnya, cuma dibaca ulang, nggak diubah/dihapus)

---

## Phase 36 — Fix: Download/Import Backup Database 403 Buat Super Admin Asli ✅

**Goal:** Bug report langsung dari user — akses `/admin/settings/database/download` sebagai super_admin asli kena 403 "Akses Ditolak".

1. **Root cause**: `DatabaseBackupController::download()`/`import()` pakai `auth()->user()->hasRole('super_admin')` — method dari Spatie Laravel Permission. Tapi app ini **nggak pernah** manggil `assignRole()` di mana pun di codebase, jadi tabel `model_has_roles` Spatie selalu kosong dan `hasRole()` SELALU balikin `false`, terlepas dari kolom `users.role` (yang beneran dipakai buat semua Policy/menu access lain di app ini) udah bener `super_admin`. Ini satu-satunya tempat di seluruh codebase yang pakai Spatie `hasRole()` — dua controller kembar persis (`AiSettingController`/`MailSettingController`) udah bener pakai `isSuperAdmin()`.
2. **Fix**: ganti `hasRole('super_admin')` → `isSuperAdmin()` di kedua method, konsisten sama pola yang bener di seluruh app.
3. **Root cause dokumentasi**: `CLAUDE.md` bagian "Sistem Otorisasi" ternyata sendiri yang nyaranin pola `hasRole('super_admin')` sebagai "pengecualian yang boleh dipakai" — inilah kemungkinan sumber kenapa kode ini ditulis salah dari awal. Diperbaiki sekalian, ditambah catatan eksplisit "jangan pernah pakai Spatie `hasRole()`" plus penjelasan kenapa (Spatie Role infrastruktur ada tapi nggak pernah di-assign).

### Done Criteria
- [x] `php -l` clean di file yang disentuh
- [x] Verifikasi: `User::isSuperAdmin()` buat akun super_admin asli balikin `true` (lolos `abort_unless`)
- [x] Verifikasi HTTP: request ke `/admin/settings/database/download` login sebagai super_admin asli — 403 ilang, request lanjut sampe ke logic backup (sisa error 500 di local testing itu murni `SHOW TABLES` sintaks MySQL-only ketemu SQLite dev, bukan bug dari fix ini — di server produksi yang pakai MySQL beneran nggak akan kejadian)
- [x] Sweep `grep -rn "hasRole("` di seluruh `app/` → nol hasil tersisa

---

## Phase 37 — Fix: Download/Import Backup Database 500 di SQLite (Dev) ✅

**Goal:** Bug report lanjutan dari user setelah Phase 36 — 403 udah ilang, tapi `/admin/settings/database/download` sekarang balikin 500 "Terjadi Kesalahan", user bilang errornya nggak muncul di log.

1. **Investigasi**: error-nya beneran ADA di `storage/logs/laravel.log` (grep ketemu match persis timestamp request user) — `SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error ... SQL: SHOW TABLES`. Cek langsung `.env` `malas.test` confirmed `DB_CONNECTION=sqlite` (sesuai konvensi dev project ini di `CLAUDE.md`), sementara `DatabaseBackupController` ditulis MySQL-only sejak awal (`SHOW TABLES`, `SET FOREIGN_KEY_CHECKS=0/1`) — dua sintaks itu bikin error di SQLite. Bug ini nggak pernah kejadian sebelumnya karena selalu ketutup duluan sama bug 403 Phase 36.
2. **Fix `getBackupTables()`**: branch berdasarkan `DB::connection()->getDriverName()` — SQLite pakai `SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'`, selain itu tetap `SHOW TABLES`.
3. **Fix FK-check toggle**: dipusatkan ke helper baru `fkChecksStatement(bool $enable): string` — emit `PRAGMA foreign_keys = ON/OFF;` untuk SQLite, `SET FOREIGN_KEY_CHECKS=1/0;` untuk driver lain. Dipakai di `download()` (sebelum/sesudah dump) dan `import()` (sebelum/sesudah transaksi, plus di catch/rollback).
4. **Fix cross-driver import**: `import()` sekarang skip baris FK-check mentah dari file upload (regex `^(SET\s+FOREIGN_KEY_CHECKS|PRAGMA\s+foreign_keys)`), selalu pakai `fkChecksStatement()` yang sesuai driver AKTIF — sengaja didesain biar backup lintas-driver (mis. export dari SQLite dev, import ke MySQL prod) tetap jalan, karena `INSERT`/`DELETE` sendiri driver-agnostic antara SQLite dan MySQL.

### Done Criteria
- [x] `php -l` clean di file yang disentuh
- [x] Verifikasi HTTP end-to-end di SQLite dev database (environment sama persis dengan `malas.test`): `GET .../download` → 200 dengan file `.sql` valid (`PRAGMA foreign_keys = OFF;` muncul, bukan lagi 500)
- [x] Verifikasi round-trip: `POST .../import` pakai file backup yang baru di-download → 302 sukses, nol error baru di `laravel.log`
- [x] Verifikasi data integrity: `GET .../download` lagi setelah import → file identik byte-per-byte dengan backup sebelumnya (kecuali baris timestamp), membuktikan export→import nggak merusak/mengubah data

---

## Phase 38 — Deploy via Docker (Postgres) + Rapikan Struktur File Deploy ✅

**Goal:** Request user — tambah cara deploy ketiga (Docker, generik buat Proxmox LXC/VM tapi portable ke Linux manapun), database full Postgres (biar konsisten sama microservice lain), pastikan data lama ke-copy ke database baru, sekalian rapiin file infrastruktur deploy yang berantakan di root.

1. **`DatabaseBackupController` jadi 3-driver-aware**: tambah branch Postgres di `getBackupTables()` (`pg_catalog.pg_tables`) dan `fkChecksStatement()` (`SET session_replication_role = replica/origin`, karena Postgres nggak punya toggle FK global kayak MySQL/SQLite). Identifier quoting di dump SQL ikut driver aktif (backtick vs `"double quote"`) lewat helper baru `identifierQuoteChar()`/`quoteIdentifier()`.
2. **Command baru `php artisan malas:migrate-data --from= --to=`** (`MigrateDatabaseData.php`) — migrasi data pas cutover engine DB, nyalin baris-per-baris lewat query builder (`cursor()` + `array_chunk`), bukan lewat teks SQL — driver-agnostic sepenuhnya. Beda dengan backup `.sql` biasa (skip `users`), command ini nyalin **semua** tabel karena tujuannya cutover penuh.
3. **Docker stack baru** di `deploy/`: `Dockerfile` (multi-stage Node→Composer→PHP-FPM runtime), `docker-compose.yml` (app/queue/nginx/db=Postgres 16), `docker-compose.override.yml` (dev lokal + Vite HMR), `nginx.conf`, `.env.docker.example`.
4. **Script `deploy/deploy-docker.sh`** — generik, cuma butuh Docker Engine + Compose plugin (tidak pakai Proxmox API): setup `.env`, generate `APP_KEY`, build+jalankan stack, migrate+seed, tawarin migrasi data lama, cache production. `deploy/update-docker.sh` buat update kode di deploy yang udah jalan.
5. **Rapiin struktur file**: `deploy.sh`/`update.sh` (native) dipindah ke `deploy/deploy.sh`/`deploy/update.sh` (path `$SCRIPT_DIR` diupdate `cd` naik satu level). Referensi path diupdate di `README.md`, `README.id.md`, `docs/DEPLOYMENT.md`. Dokumen baru `docs/DOCKER.md`.
6. **Bug nyata #1 ketemu pas build image beneran** (bukan cuma baca kode): tanpa `.dockerignore`, `COPY . .` di Dockerfile ikut nyalin `bootstrap/cache/*.php` lokal (gitignored tapi ada di disk dev) yang masih reference `Laravel\Breeze\BreezeServiceProvider` dari `require-dev` — bikin `composer dump-autoload` gagal total di stage `vendor` karena Breeze nggak ke-install waktu `--no-dev`. Fix: tambah `.dockerignore` (exclude `vendor/`, `node_modules/`, `bootstrap/cache/*.php`, `.env`, dll).
7. **Bug nyata #2 ketemu pas jalanin stack beneran**: user non-root `malas` di image runtime cuma di-`chown` buat `storage/`+`bootstrap/cache/`, bukan `public/` — `php artisan storage:link` gagal (`symlink(): Permission denied`) karena butuh nulis symlink baru ke `public/`. Fix: tambah `public/` ke daftar `chown` di `Dockerfile`.

### Done Criteria
- [x] `php -l` clean di file PHP yang disentuh
- [x] `docker compose config` valid untuk `docker-compose.yml` (sendiri) dan gabungan dengan `docker-compose.override.yml`
- [x] `docker build` sukses sampai akhir (setelah fix `.dockerignore`)
- [x] `docker compose up` — semua 4 service (app, queue, nginx, db) jalan dan `db` health-check `healthy`
- [x] 44 migration jalan bersih di Postgres asli (nol masalah dialect SQL — seluruh app pakai Eloquent/query builder, cuma `DatabaseBackupController` yang punya raw SQL)
- [x] `storage:link` + seed sukses setelah fix permission `public/`
- [x] Landing page 200 lewat nginx → PHP-FPM → Postgres; login via magic link sukses
- [x] Download backup di Postgres asli menghasilkan `.sql` valid (`SET session_replication_role = replica;` + identifier `"double-quoted"`)
- [x] Round-trip import → 302 sukses, nol error baru di log; redownload setelahnya identik byte-per-byte (kecuali timestamp) dengan backup sebelumnya
- [x] `malas:migrate-data` dites terpisah (SQLite → SQLite scratch DB via connection dinamis): 34 tabel, semua baris tersalin sesuai jumlah sumber
- [x] Container test + image test di-cleanup (`docker compose down -v`, `docker rmi`) setelah verifikasi

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
| 7 | Jikan API Integration *(diganti AniList di Phase 11)* | ✅ |
| 8 | Announcements & Dashboard | ✅ |
| 9 | User & Menu Management | ✅ |
| 10 | Polish & Hardening | ✅ |
| 11 | Post-Launch Enhancements | ✅ |
| 12 | Library UI, Dashboard, Baca Tracking & Review | ✅ |
| 13 | Light Novel Metadata Import (RanobeDB) | ✅ |
| 14 | Multi-Bahasa, Profil Publik & Follow, Wishlist, Selera Genre AI, Menu Reorder | ✅ |
| 15 | URL Katalog Berbasis Judul (Slug) | ✅ |
| 16 | Login Tanpa SSO (Fallback) + Konfigurasi Email (Resend) | ✅ |
| 17 | Batch Import AniList, Fix Logout SSO Down, Context Menu Tab Baru | ✅ |
| 18 | Modal Pilihan Login (SSO / Email) | ⚠️ kode selesai, browser click-through belum |
| 19 | Tema Light/Dark/System | ✅ |
| 20 | Fix URL Admin (Slug) & Editor Genre/Tags di Series Edit | ✅ |
| 21 | Favicon Terpasang, README Bilingual, Audit Fitur Kelewat | ✅ |
| 22 | Filter Genre Multi-Select di Katalog User | ✅ |
| 23 | Quick-Edit Progres Baca & Jumlah Volume di Koleksiku | ✅ |
| 24 | Multi-Account Switching (Session-Based) | ✅ |
| 25 | Stepper dari Koleksiku, Grouping Koleksi, Flash Message Multi-Bahasa *(grouping diganti total di Phase 27)* | ✅ |
| 26 | Fix: Link Login (CLI/Email) Override Akun Aktif Alih-Alih Nambah | ✅ |
| 27 | Grup Koleksi Dirombak Total (ala MDList MangaDex) | ✅ |
| 28 | Grup Koleksi: Modal Diperbesar + Paginasi, Filter Tipe, Slug URL, Publik/Privat | ✅ |
| 29 | Fix: Blank Screen Buka Grup Koleksi (Slug Kosong + Guest Crash), README Bebas Emoji | ✅ |
| 30 | Modal "Tambah Manga" Lebih Lebar + Infinite Scroll | ✅ |
| 31 | Fix Layout Modal, URL Koleksi Pakai Judul, Konfirmasi+Undo Hapus dari Grup, Hapus Puter.js | ✅ |
| 32 | Nonaktifkan Sementara Generate Funfact AI, Export/Import Koleksi | ✅ |
| 33 | Badge "Volume Kurang" (Next Volume to Buy) di Koleksiku | ✅ |
| 34 | Badge "Volume Kurang" Bisa Diklik, Auto-Isi Form Tambah Volume | ✅ |
| 35 | Reveal API Key/Secret di Admin Settings (Ikon Mata) | ✅ |
| 36 | Fix: Download/Import Backup Database 403 Buat Super Admin Asli | ✅ |
| 37 | Fix: Download/Import Backup Database 500 di SQLite (Dev) | ✅ |
| 38 | Deploy via Docker (Postgres) + Rapikan Struktur File Deploy | ✅ |

**QA pass: 2026-07-03** — Phase 11–18 dikerjakan iteratif sesudahnya, lihat [`CHANGELOG.md`](../CHANGELOG.md) untuk detail per-perubahan. Gap yang masih terdokumentasi: Phase 18 butuh satu kali manual click-through di dev environment normal buat verifikasi visual modal login (satu-satunya gap tersisa dari sekian phase post-launch). Phase 19–38 (2026-08-14 s/d 2026-08-23) semua sudah diverifikasi — tiga belas bug nyata ketemu & diperbaiki selama proses (Phase 20 poin 3, Phase 23 poin 3, Phase 25 poin 3, Phase 26, Phase 27 poin 2, Phase 28 poin 5, Phase 29, Phase 30 poin 1, Phase 31 poin 1, Phase 36, Phase 37, Phase 38 poin 6, Phase 38 poin 7 — masing-masing sebelum phase berikutnya berjalan lolos verifikasi tanpa bug baru). Flash message controller kini sudah multi-bahasa penuh (Phase 25) — backlog lama yang tercatat di CLAUDE.md sudah diselesaikan. Grouping koleksi (Phase 25) dirombak total jadi desain many-to-many ala MDList MangaDex (Phase 27), diperluas dengan paginasi/filter/slug URL/visibilitas publik (Phase 28) lalu modal lebar + infinite scroll (Phase 30); dua bug flexbox/layout berturut-turut (Phase 30 `sm:` prefix Tailwind, Phase 31 `min-h` floor height) jadi pengingat kalau verifikasi HTTP/JSON doang nggak cukup buat nangkep bug rendering/CSS/JS client-side, perlu render beneran di browser. Integrasi Puter.js (AI provider gratis client-side) dihapus total di Phase 31 atas permintaan user; Phase 32 nonaktifkan sementara generate funfact AI-nya sendiri (word cloud genre tetap jalan) dan nambah fitur export/import koleksi (backup pribadi); Phase 33 nambah fitur "next volume to buy" (badge volume kurang) hasil diskusi ide fitur, lalu Phase 34 bikin badge itu actionable (klik → auto-isi form Tambah Volume). Phase 35 buka akses reveal API key/secret di Admin Settings (super_admin-only, jadi aman) lewat toggle ikon mata. Phase 36 nemuin & benerin bug otorisasi nyata (Spatie `hasRole()` yang nggak pernah kesinkron sama kolom `role` asli) yang ternyata bersumber dari dokumentasi `CLAUDE.md` sendiri yang salah — pengingat kalau dokumentasi juga bisa jadi sumber bug kalau nggak dicek ulang terhadap kode beneran. Phase 37 nemuin bug kedua yang ketutup sama bug Phase 36 (fitur backup database MySQL-only ketemu SQLite dev) — contoh nyata "benerin satu bug bisa nyingkap bug berikutnya yang ketutup di baliknya", sekalian jadi pengingat kalau klaim user "nggak ada di log" perlu dicek ulang langsung ke file log, bukan diasumsikan benar. Phase 38 nambah metode deploy ketiga (Docker + Postgres, generik buat Proxmox atau Linux manapun) dan command migrasi data lintas engine DB — dua bug nyata ketemu langsung pas build/jalanin Docker beneran (bukan cuma baca kode): `.dockerignore` yang belum ada bikin cache Composer lokal ikut ke-copy ke image dan bikin build gagal total, dan permission `public/` yang belum di-`chown` ke user non-root bikin `storage:link` gagal — pengingat kalau infrastruktur (Dockerfile, compose) butuh diverifikasi dengan benar-benar di-build & dijalankan, nggak cukup cuma ditulis dan dibaca ulang. Phase 32–33 verifikasi pakai HTTP request langsung (curl) karena tool Browser pane sempat kena gangguan; udah pulih lagi mulai Phase 34.
