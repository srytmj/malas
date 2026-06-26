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
| Komponen UI | shadcn/ui (copy-paste, bukan dependency black-box) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database (dev) | SQLite |
| Database (prod) | MySQL 8+ |
| Auth/Role | Spatie Laravel Permission |
| File storage | Cloudflare R2 (via `league/flysystem-aws-s3-v3`) |

---

## Struktur Folder

```
app/
  Http/
    Controllers/
      Admin/     — controller untuk halaman admin
      User/      — controller untuk halaman user
    Middleware/
      CheckMenuAccess.php   — cek maintenance mode & role_access per menu
      EnsureNotBanned.php
  Models/
  Policies/
  Services/
    JikanService.php

resources/js/
  Pages/
    Admin/       — halaman admin (Inertia pages)
    User/        — halaman user (Inertia pages)
    Auth/        — login, register, dll
  Layouts/
    AdminLayout.tsx
    UserLayout.tsx
  Components/
    ui/          — shadcn/ui components (JANGAN MODIFIKASI)
    app/         — custom reusable components
  hooks/         — custom React hooks
  lib/
    utils.ts
    types.ts     — shared TypeScript types
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
- Form pakai **React Hook Form** + **Zod** untuk validasi
- Tidak ada inline style — semua pakai Tailwind utility class
- File komponen: `PascalCase.tsx`
- File hooks/utils: `camelCase.ts`

```typescript
// BENAR — pakai shadcn/ui
import { Button } from "@/components/ui/button"
<Button variant="outline" onClick={handleSave}>Simpan</Button>

// SALAH — custom HTML
<button className="bg-blue-500 px-4 py-2">Simpan</button>
```

### Inertia Controller
- Setiap controller method return `Inertia::render()` atau `redirect()`
- Gunakan `Route::middleware(['auth', 'check.menu'])` untuk semua route
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

---

## Menu Management

Menu disimpan di database (tabel `menus`). Setiap route navigation harus punya entri di tabel menu.

`CheckMenuAccess` middleware dijalankan di setiap request:
1. Ambil menu berdasarkan route name saat ini
2. Jika `is_maintenance = true` DAN user bukan admin/super_admin → return halaman maintenance
3. Jika role user tidak ada di `role_access` → abort 403

---

## UX — Wajib

- Semua tombol yang trigger server request harus punya **loading state** (disable + spinner)
- Gunakan `router.visit()` dengan `onStart`/`onFinish` untuk kontrol loading state
- Form error harus ditampilkan inline di bawah field, bukan hanya toast
- Toast/notification untuk success action (pakai `sonner` dari shadcn)
- Skeleton loading untuk data yang di-fetch (jangan blank page)

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
