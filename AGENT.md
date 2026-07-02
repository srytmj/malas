# MALAS — Agent Context

Baca file ini sebelum mengerjakan task apapun di project ini.

## Apa itu MALAS?

**MALAS** (Manga Library Admin System) adalah aplikasi web untuk mengelola koleksi manga pribadi dan perpustakaan.

Dua sisi:
- **Admin panel** — kelola katalog, pengguna, menu, pengumuman
- **User-facing** — browse katalog, kelola koleksi pribadi, catat peminjaman

UI terinspirasi dari MangaDex (mangadex.org): card-based, cover-forward, dark/light mode.

---

## Stack

| | |
|--|--|
| Backend | Laravel 12 |
| Frontend | React 19 + TypeScript 5 via Inertia.js v2 |
| UI | shadcn/ui + Tailwind CSS v4 |
| DB (dev) | SQLite |
| DB (prod) | MySQL 8+ |
| Auth | Laravel Breeze + Spatie Permission |
| Storage | Cloudflare R2 |
| Bundler | Vite |

---

## Roles

| Role | Deskripsi |
|------|-----------|
| `super_admin` | Akses penuh, tidak bisa di-ban |
| `admin` | Akses penuh kecuali manage super_admin |
| `user` | Browse katalog (read-only), kelola koleksi sendiri |

---

## Fitur Utama

1. **Menu Management** — admin toggle visibility & maintenance mode per menu
2. **Katalog Series & Volume** — admin CRUD, user browse read-only
3. **Koleksi User** — user catat manga yang dimiliki, per volume
4. **Jikan API** — import data dari MyAnimeList
5. **Loans** — catat peminjaman volume
6. **Announcements** — pengumuman dari admin ke semua user

---

## Navigasi per Role

**Admin sidebar:**
Dashboard → Users → Roles → Menu Management → Series → Volumes → Collections (all) → Loans → Announcements → Settings

**User sidebar:**
Dashboard → Katalog (Series, read-only) → Koleksiku → Pinjaman Saya

---

## Key Commands

```bash
# Development
php artisan serve          # start Laravel (port 8000)
npm run dev                # start Vite

# Database
php artisan migrate        # run migrations
php artisan migrate:fresh --seed  # reset + seed
php artisan db:seed        # seed data

# Type checking
npx tsc --noEmit           # TypeScript check (0 error = pass)

# Tinker
php artisan tinker         # interactive REPL

# Cache
php artisan optimize:clear # clear all caches
php artisan optimize       # rebuild all caches
```

---

## Dokumen yang Wajib Dibaca

| File | Isi |
|------|-----|
| `CLAUDE.md` | Coding rules, naming conventions, larangan |
| `docs/PRD.md` | Requirement lengkap + access matrix |
| `docs/ARCHITECTURE.md` | DB schema, folder structure |
| `docs/FLOWS.md` | Navigation map + user flows |
| `docs/PHASES.md` | Breakdown fase implementasi + checklist |
| `QA.md` | Instruksi untuk QA chat setelah kode selesai |

---

## Pola Penting

### Controller → Inertia Page
```php
// Admin controller
return Inertia::render('Admin/Series/Index', [
    'series'  => SeriesResource::collection(Series::paginate(20)),
    'filters' => request()->only(['status', 'search']),
    'can'     => ['create' => $user->can('create', Series::class)],
]);
```

### Policy check di controller
```php
$this->authorize('update', $series); // throws 403 jika tidak punya akses
```

### Menu maintenance check
`CheckMenuAccess` middleware otomatis dijalankan di setiap request. Tidak perlu manual check di controller.

### Form submission (Inertia)
```typescript
const form = useForm({ title: '', status: 'publishing' })
form.post(route('admin.series.store'), {
  onSuccess: () => toast.success('Series berhasil disimpan'),
})
```

---

## Hal yang TIDAK Boleh Dilakukan

- Jangan mulai implementasi jika pesan user diakhiri "gimana?" (itu diskusi)
- Jangan modifikasi file di `resources/js/components/ui/` (shadcn source)
- Jangan skip middleware atau policy
- Jangan commit `.env`
