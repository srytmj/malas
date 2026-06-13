# Security

## Model Ancaman

MALAS adalah panel admin internal — tidak ada akses publik ke data sensitif. Ancaman utama:
1. Unauthorized access ke panel admin (auth bypass)
2. Credential R2 bocor ke repository
3. SQL injection / XSS melalui input form
4. CSRF attack pada form mutasi

## Kontrol Akses

### Middleware Stack

Semua route admin dilindungi:

```php
Route::prefix('admin')->middleware(['auth', 'role:super_admin'])->group(function () { ... });
```

- `auth` — harus memiliki session login aktif
- `role:super_admin` — harus punya kolom `role = 'super_admin'` di tabel `users`

Pengguna dengan role `user` yang mencoba akses `/admin/*` akan di-redirect ke halaman utama atau mendapat 403.

### Password

- Password di-hash dengan `bcrypt` (Laravel default via `Hash::make()`)
- Tidak ada plain-text password di database

## Proteksi Input

### CSRF Protection

- Semua form HTML: `@csrf` directive → `<input type="hidden" name="_token" value="...">`
- Semua AJAX mutasi: header `X-CSRF-TOKEN: <meta[name=csrf-token].content>`
- Laravel middleware `VerifyCsrfToken` aktif di semua non-API route

### SQL Injection

- Semua query menggunakan Eloquent ORM atau Query Builder dengan parameter binding
- Tidak ada raw query string concatenation
- `LIKE "%{$search}%"` di DataTable controller: `$search` dari `$request->input()` — aman karena binding

### XSS

- Blade `{{ $var }}` auto-escape HTML entities
- Di JavaScript (DataTable render): `escHtml()` function manual:
  ```js
  function escHtml(str) {
      return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  ```
- Gunakan `escHtml()` setiap kali menyisipkan data server ke innerHTML

### Mass Assignment

- Semua model menggunakan `$guarded = []`
- Proteksi mass assignment dilakukan di layer validasi controller (`$request->validate()`)
- Jangan lewatkan validasi sebelum `Model::create($request->all())`

## Credential Management

### Cloudflare R2

Credential R2 **hanya** boleh ada di `src/.env`:

```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
```

- `.env` sudah ada di `.gitignore` — **jangan pernah commit**
- Jangan cantumkan credential di kode, komentar, atau dokumen
- Jika credential terekspos: segera rotate di Cloudflare R2 dashboard

### APP_KEY

- `APP_KEY` digunakan untuk enkripsi session dan cookie
- Generate sekali: `php artisan key:generate`
- Simpan di `.env`, jangan expose

## File Upload Security

Cover image (series dan volume) di-upload ke R2:

- Validasi di controller: `'cover' => 'nullable|image|max:2048'`
  - `image` rule: verifikasi MIME type via `getimagesize()` (bukan hanya ekstensi)
  - `max:2048`: maksimal 2MB
- File disimpan di path acak via `$request->file('cover')->store('covers/series', 'public')`
- Path yang disimpan adalah path relatif, bukan URL publik

## Activity Logging

Semua aksi destruktif dicatat otomatis via `HasSoftDeletesWithActor`:
- Siapa yang melakukan (user_id)
- Apa yang dilakukan (action: `series.deleted`, `user.banned`, dll.)
- Entity apa (entity_type + entity_id)
- Alasan (reason) — wajib diisi untuk soft delete
- IP address

## Checklist Security Review

- [ ] Tidak ada raw SQL dengan string interpolation user input
- [ ] Semua form ada `@csrf`
- [ ] Semua AJAX mutasi kirim `X-CSRF-TOKEN` header
- [ ] File upload: validasi MIME type + ukuran
- [ ] Credential tidak ada di kode atau git history
- [ ] Output data user di JavaScript: selalu lewat `escHtml()`
- [ ] Middleware `auth` + `role:super_admin` ada di semua route admin
