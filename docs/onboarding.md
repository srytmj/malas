# Onboarding — Panduan Mulai Cepat

Dokumen ini untuk developer baru yang ingin mengenal dan menjalankan MALAS secara lokal.

## Dalam 15 Menit: Jalankan Lokal

```bash
# 1. Clone
git clone <repo-url>
cd malas/src

# 2. Install
composer install
npm install

# 3. Env
cp .env.example .env
php artisan key:generate

# 4. Database (pastikan XAMPP MySQL aktif)
# Buat database: CREATE DATABASE malas;
# Edit .env: DB_DATABASE=malas, DB_USERNAME=root, DB_PASSWORD=

php artisan migrate

# 5. Build
npm run build

# 6. Jalankan
php artisan serve
# Buka: http://localhost:8000
```

Buat akun super admin:
```bash
php artisan tinker
>>> App\Modules\Core\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@example.com',
...   'password' => bcrypt('password'),
...   'role' => 'super_admin',
... ]);
```

Buka `http://localhost:8000/admin` dan login.

## Orientasi Codebase

### Di Mana Kode Penting?

| Apa | Di Mana |
|---|---|
| Model | `app/Modules/{Core,Collection,Jikan}/Models/` |
| Controller admin | `app/Modules/Admin/Http/Controllers/` |
| Route admin | `app/Modules/Admin/routes/admin.php` |
| Views admin | `resources/views/admin/` |
| Job Jikan | `app/Jobs/ScrapeJikanPageJob.php` |
| Scheduler | `routes/console.php` |
| Config R2 | `config/filesystems.php` (disk `r2`) |
| CSS + JS entry | `resources/css/app.css`, `resources/js/app.js` |

### Alur Request Tipikal (Admin Series)

```
Browser → GET /admin/series
  → RouteServiceProvider → admin.php
  → middleware: auth + role:super_admin
  → SeriesController::index()
    → if ajax() → datatableResponse() → JSON
    → else → view('admin.series.index')
  → view: DataTable AJAX via vanilla JS
```

### Pola yang Akan Sering Kamu Temui

**1. AJAX DataTable** — semua list view pakai pola ini:
```php
// Controller
public function index(Request $request) {
    if ($request->ajax()) return $this->datatableResponse($request);
    return view('admin.xxx.index');
}
```

**2. HasSoftDeletesWithActor** — semua model utama pakai ini untuk delete:
```php
$series->deleteWithReason('Alasan penghapusan');
// → sets deleted_at, deleted_by, deletion_reason
// → auto-creates ActivityLog entry
```

**3. UUID via HasUuids** — ID model adalah UUID string, bukan integer:
```php
$series = Series::find('550e8400-e29b-41d4-a716-446655440000');
// UUID, bukan 1, 2, 3...
```

**4. Alpine.js di View** — form kompleks pakai Alpine:
```html
<div x-data="collectionForm()" x-init="initApp()">
  <!-- reaktif -->
</div>
<script>
function collectionForm() {
    return { userId: '', entries: [], ... };
}
</script>
```

## Apa yang Belum Ada

- **Test suite** — belum ada test. Semua testing manual.
- **Halaman user** — hanya panel admin yang diimplementasi.
- **Tracking bacaan** — tabel `chapters` dan `user_tracking` belum ada.
- **Restore soft-delete** — data yang dihapus belum bisa di-restore dari UI.

## Pertanyaan Umum

**Q: Kenapa queue tidak jalan?**
A: Pastikan `php artisan queue:work` berjalan di terminal terpisah. Tanpa ini, Jikan scraping tidak akan berjalan.

**Q: Cover image tidak muncul?**
A: Pastikan credential R2 di `.env` sudah benar. Test: `php artisan tinker` → `Storage::disk('r2')->exists('test.txt')`.

**Q: Error "Route [admin.xxx] not defined"?**
A: Cek apakah route di `app/Modules/Admin/routes/admin.php` sudah terdaftar di ServiceProvider dan `php artisan route:cache` sudah dijalankan ulang.

**Q: Vite error saat `npm run dev`?**
A: Pastikan `package.json` ada `vite` dan `laravel-vite-plugin` di devDependencies. Jalankan `npm install` dulu.
