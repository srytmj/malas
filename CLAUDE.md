# MALAS — Claude Code Rules

## Tech Stack

- **Laravel 12** + **Filament v5.6.7** + **SQLite** (dev) / **MySQL** (prod)
- **Livewire 3** + **Alpine.js** (via Filament)
- **Cloudflare R2** via `league/flysystem-aws-s3-v3`

## Filament Rule — WAJIB DIIKUTI

**Setiap kode yang berkaitan dengan Filament HARUS mengacu pada dokumentasi resmi Filament v5 atau plugin yang sudah terinstall.**

- Cek dokumentasi: https://filamentphp.com/docs/5.x
- Cek plugin terinstall: lihat `app/Filament/Pages/InstalledPlugins.php` untuk daftar lengkap
- **JANGAN** membuat komponen UI manual (HTML/CSS kustom) jika sudah ada padanan native Filament
- **JANGAN** menggunakan API Filament v3/v4 — selalu pakai v5
- Jika perlu sesuatu yang TIDAK ada di Filament docs atau plugin terinstall → **konfirmasi dulu** sebelum membuat

### Komponen yang BOLEH dipakai (sudah diverifikasi Filament v5)

| Kebutuhan | Komponen |
|-----------|----------|
| Layout halaman | `content(Schema $schema): Schema` di Page class |
| Tab | `Filament\Schemas\Components\Tabs` + `Tab::make()` |
| Embed tabel | `Filament\Schemas\Components\EmbeddedTable` |
| Grid layout | `Filament\Schemas\Components\Grid` |
| Section / card | `Filament\Schemas\Components\Section` |
| Tombol aksi | `Filament\Schemas\Components\Actions` + `Filament\Actions\Action` |
| Input teks | `Filament\Forms\Components\TextInput` |
| Dropdown pilihan | `Filament\Forms\Components\Select` |
| Tabel data | `HasTable` + `InteractsWithTable` + `table(Table $table)` |

### Cara panggil Livewire method dari Action dalam schema

```php
// BENAR — string name, wire:click="methodName" dipanggil langsung
Action::make('search')->label('Cari')->action('search')

// SALAH — closure action di schema tidak bekerja via mountAction
Action::make('search')->action(fn () => $this->search())
```

### Aturan `->visible()` dan closure

```php
// Capture $this lewat closure
->visible(fn () => $this->hasResults())
->label(fn () => "Hal. {$this->page} / {$this->total}")
```

## Struktur Proyek

```
app/
  Filament/
    Pages/        — custom pages (JikanScraper, Settings, InstalledPlugins)
    Resources/    — CRUD resources (Series, Volumes, Collections, etc.)
  Models/         — Eloquent models
  Services/       — JikanService (API wrapper)
  Actions/        — ImportSeriesFromJikan, dll
database/
  migrations/
resources/views/filament/pages/  — blade minimal (hanya jika ada elemen non-Filament)
```

## UX Smoothness — WAJIB

Setiap interaksi harus memberikan feedback visual yang halus. Tidak boleh ada elemen yang tiba-tiba "spawn" tanpa transisi.

### Loading State pada Tombol

Semua `Action` yang trigger request ke server WAJIB ditambahkan `wire:loading` attributes via `->extraAttributes()`:

```php
Action::make('search')
    ->action('search')
    ->extraAttributes([
        'wire:loading.attr'  => 'disabled',
        'wire:loading.class' => 'opacity-60 cursor-wait',
        'wire:target'        => 'search',   // nama method Livewire — scope agar tidak trigger loading dari action lain
    ])
```

Isi `wire:target` dengan nama method yang dipanggil (bisa koma-separated untuk beberapa method).

### Loading Bar Global

Loading bar sudah ada di blade view. Jika ada method baru yang butuh indikator, tambahkan ke `wire:target` di blade:

```html
wire:target="search,searchLoadMore,scrape,methodBaru"
```

### Elemen yang Muncul/Menghilang

Gunakan `wire:transition` untuk elemen yang conditionally rendered:
- Di **blade**: `<div wire:transition>...</div>` — Livewire 3 fade-in/out otomatis
- Di **Filament schema** via `->visible()`: tambahkan `->extraAttributes(['wire:transition' => ''])` jika tersedia, atau terima bahwa schema component Filament tidak support wire:transition secara native

### Prinsip Umum

- Klik = respons visual instan (disabled + opacity) → tidak perlu tunggu respons server untuk feedback
- Gunakan `wire:loading.delay` (delay 200ms) untuk menghindari flicker pada request cepat
- Jangan biarkan tabel/konten muncul tanpa indikasi sebelumnya

## Jangan Lakukan

- Jangan buat HTML manual (`<div>`, `<form>`, `<select>`) di blade jika bisa pakai Filament component
- Jangan override `protected string $view` kecuali perlu elemen yang benar-benar tidak ada di Filament (mis. CSS animation)
- Jangan pakai `Filament\Tables\Actions\BulkAction` — pakai `Filament\Actions\BulkAction`
- Jangan pakai `Filament\Forms\Components\Grid` — pakai `Filament\Schemas\Components\Grid`
- Jangan pakai plugin yang tercantum sebagai "Tidak Support v5" di InstalledPlugins
