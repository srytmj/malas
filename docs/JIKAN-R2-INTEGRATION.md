# Jikan & R2 Integration Spec
# MALAS (Manga Library Admin System)

**Versi:** 1.0  
**Tanggal:** 2026-06-14

---

## 1. Endpoint Jikan yang Digunakan

Base URL: `https://api.jikan.moe/v4`  
Auth: tidak ada (anonymous)  
Rate limit: 3 req/detik, 60 req/menit

### GET /manga — Search Manga

```
GET https://api.jikan.moe/v4/manga?q={query}&limit=10&page={page}
```

| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `q` | string | Kata kunci pencarian |
| `limit` | int | Jumlah hasil (max 25, kita pakai 10) |
| `page` | int | Halaman (default 1) |

**Response fields yang dipakai:**

```json
{
  "data": [
    {
      "mal_id": 1,
      "title": "Monster",
      "title_english": "Monster",
      "title_japanese": "モンスター",
      "images": {
        "jpg": {
          "large_image_url": "https://cdn.myanimelist.net/..."
        }
      },
      "status": "Finished",
      "score": 9.1,
      "rank": 3,
      "volumes": 18
    }
  ],
  "pagination": {
    "last_visible_page": 5,
    "has_next_page": true
  }
}
```

**Cache:** 5 menit. Key: `jikan.search.{md5($query)}.page{$page}`

---

### GET /manga/{id} — Detail Manga

```
GET https://api.jikan.moe/v4/manga/{mal_id}
```

**Response fields yang dipakai:**

```json
{
  "data": {
    "mal_id": 1,
    "title": "Monster",
    "title_english": "Monster",
    "title_japanese": "モンスター",
    "synopsis": "...",
    "status": "Finished",
    "published": {
      "from": "1994-12-05T00:00:00+00:00",
      "to": "2001-12-20T00:00:00+00:00"
    },
    "volumes": 18,
    "score": 9.1,
    "rank": 3,
    "images": {
      "jpg": {
        "large_image_url": "https://cdn.myanimelist.net/..."
      }
    }
  }
}
```

**Cache:** 1 jam. Key: `jikan.detail.{$malId}`

---

### GET /manga/{id}/pictures — Gambar Manga

```
GET https://api.jikan.moe/v4/manga/{mal_id}/pictures
```

Dipakai jika `large_image_url` dari endpoint detail tidak tersedia atau ingin gambar alternatif.

```json
{
  "data": [
    {
      "jpg": {
        "large_image_url": "https://cdn.myanimelist.net/..."
      }
    }
  ]
}
```

**Cache:** 1 jam. Key: `jikan.pictures.{$malId}`

---

## 2. Rate Limiting Strategy

Jikan membatasi 3 request/detik. Implementasi di Laravel:

```php
// app/Services/JikanService.php

use Illuminate\Support\Facades\RateLimiter;

private function throttledRequest(string $url): array
{
    $executed = RateLimiter::attempt(
        key: 'jikan-api',
        maxAttempts: 3,
        callback: fn() => true,
        decaySeconds: 1,
    );

    if (! $executed) {
        // Tunggu sisa window dan retry
        sleep(1);
        return $this->throttledRequest($url);
    }

    $response = Http::timeout(30)
        ->connectTimeout(10)
        ->get($url);

    if ($response->status() === 429) {
        sleep(2);
        return $this->throttledRequest($url);
    }

    $response->throw();

    return $response->json();
}
```

**Strategi ringkas:**
- Gunakan `RateLimiter::attempt` dengan decay 1 detik, max 3 hits
- Jika rate limit terlampaui: sleep 1 detik, retry
- Jika Jikan kembalikan 429: sleep 2 detik, retry
- Semua HTTP call via queue job — tidak pernah blocking di request cycle Filament

---

## 3. JikanService — Interface & Implementation

### Interface

```php
// app/Services/Contracts/JikanServiceInterface.php

namespace App\Services\Contracts;

use App\Models\Series;
use Illuminate\Support\Collection;

interface JikanServiceInterface
{
    /**
     * Cari manga di MAL berdasarkan query string.
     * Return Collection of arrays (raw Jikan data, belum dimap ke model).
     */
    public function searchManga(string $query, int $page = 1): Collection;

    /**
     * Ambil detail manga by MAL ID.
     *
     * @throws \App\Exceptions\Jikan\NotFoundException
     * @throws \App\Exceptions\Jikan\ServiceUnavailableException
     */
    public function getMangaDetail(int $malId): array;

    /**
     * Ambil daftar gambar alternatif manga.
     */
    public function getMangaPictures(int $malId): array;

    /**
     * Import series dari Jikan ke database.
     * Download cover dan upload ke R2.
     *
     * @throws \App\Exceptions\Jikan\DuplicateSeriesException
     * @throws \App\Exceptions\Jikan\NotFoundException
     */
    public function importSeries(int $malId): Series;
}
```

### Implementation Outline

```php
// app/Services/JikanService.php

namespace App\Services;

use App\Models\Series;
use App\Services\Contracts\JikanServiceInterface;
use App\Exceptions\Jikan\{NotFoundException, DuplicateSeriesException, ServiceUnavailableException};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, Http, Storage, RateLimiter};

class JikanService implements JikanServiceInterface
{
    private const BASE_URL = 'https://api.jikan.moe/v4';

    public function searchManga(string $query, int $page = 1): Collection
    {
        $cacheKey = 'jikan.search.' . md5($query) . ".page{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $page) {
            $data = $this->throttledRequest(
                self::BASE_URL . '/manga?' . http_build_query([
                    'q' => $query,
                    'limit' => 10,
                    'page' => $page,
                ])
            );

            return collect($data['data'] ?? []);
        });
    }

    public function getMangaDetail(int $malId): array
    {
        return Cache::remember("jikan.detail.{$malId}", now()->addHour(), function () use ($malId) {
            return $this->throttledRequest(self::BASE_URL . "/manga/{$malId}");
        });
    }

    public function getMangaPictures(int $malId): array
    {
        return Cache::remember("jikan.pictures.{$malId}", now()->addHour(), function () use ($malId) {
            $data = $this->throttledRequest(self::BASE_URL . "/manga/{$malId}/pictures");
            return $data['data'] ?? [];
        });
    }

    public function importSeries(int $malId): Series
    {
        if (Series::where('mal_id', $malId)->exists()) {
            throw new DuplicateSeriesException("Series dengan MAL ID {$malId} sudah ada.");
        }

        $detail = $this->getMangaDetail($malId);
        $data   = $detail['data'];

        $series = Series::create([
            'mal_id'          => $data['mal_id'],
            'title_romaji'    => $data['title'],
            'title_english'   => $data['title_english'] ?? null,
            'title_japanese'  => $data['title_japanese'] ?? null,
            'synopsis'        => $data['synopsis'] ?? null,
            'status'          => $this->mapStatus($data['status'] ?? ''),
            'published_from'  => isset($data['published']['from'])
                                    ? substr($data['published']['from'], 0, 10)
                                    : null,
            'published_to'    => isset($data['published']['to'])
                                    ? substr($data['published']['to'], 0, 10)
                                    : null,
            'total_volumes'   => $data['volumes'] ?? null,
            'score'           => $data['score'] ?? null,
            'rank'            => $data['rank'] ?? null,
            'cover_path'      => null, // diisi setelah download cover
        ]);

        // Download dan upload cover
        $imageUrl = $data['images']['jpg']['large_image_url'] ?? null;
        if ($imageUrl) {
            $coverPath = $this->downloadCover($imageUrl, $series->id);
            if ($coverPath) {
                $series->update(['cover_path' => $coverPath]);
            }
        }

        return $series->fresh();
    }

    private function mapStatus(string $jikanStatus): string
    {
        return match ($jikanStatus) {
            'Publishing'        => 'publishing',
            'Finished'          => 'finished',
            'On Hiatus'         => 'on_hiatus',
            'Discontinued'      => 'discontinued',
            'Not yet published' => 'not_yet_published',
            default             => 'publishing',
        };
    }

    private function downloadCover(string $url, string $seriesId): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $path      = "covers/series/{$seriesId}.{$extension}";

            Storage::disk('r2')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null; // Cover gagal didownload — series tetap disimpan
        }
    }

    private function throttledRequest(string $url): array
    {
        $executed = RateLimiter::attempt('jikan-api', 3, fn() => true, 1);

        if (! $executed) {
            sleep(1);
            return $this->throttledRequest($url);
        }

        $response = Http::timeout(30)->connectTimeout(10)->get($url);

        if ($response->status() === 429) {
            sleep(2);
            return $this->throttledRequest($url);
        }

        if ($response->status() === 404) {
            throw new NotFoundException("MAL ID tidak ditemukan.");
        }

        if ($response->status() >= 500) {
            throw new ServiceUnavailableException("Jikan API sedang tidak tersedia.");
        }

        $response->throw();

        return $response->json();
    }
}
```

---

## 4. Error Handling Matrix

| HTTP Status | Kondisi | Action | Retry? |
|-------------|---------|--------|--------|
| `200` | Sukses | Parse response | — |
| `304` | Not Modified (cache) | Pakai cached response | — |
| `400` | Bad request | Log + throw `InvalidRequestException` | Tidak |
| `404` | Manga tidak ditemukan | Throw `NotFoundException` | Tidak |
| `429` | Rate limited | Sleep 2s, retry | Ya (max 3x) |
| `500` | Server error Jikan | Throw `ServiceUnavailableException` | Ya (backoff) |
| `503` | Jikan down | Throw `ServiceUnavailableException` | Ya (backoff) |
| Timeout (connect) | Server tidak merespons dalam 10s | Throw `ConnectionException` | Ya (backoff) |
| Timeout (read) | Response tidak selesai dalam 30s | Throw `ConnectionException` | Ya (backoff) |
| Invalid JSON | Response bukan JSON | Throw `InvalidResponseException` | Tidak |

### Custom Exceptions

```
app/Exceptions/Jikan/
├── NotFoundException.php           -- 404, tidak perlu retry
├── DuplicateSeriesException.php    -- mal_id sudah ada, tidak perlu retry
├── ServiceUnavailableException.php -- 5xx, retry dengan backoff
├── InvalidResponseException.php    -- JSON rusak, tidak perlu retry
└── RateLimitException.php          -- 429, sleep + retry
```

---

## 5. Caching Strategy

| Data | TTL | Cache Key | Invalidasi |
|------|-----|-----------|------------|
| Search results | 5 menit | `jikan.search.{md5(query)}.page{n}` | Otomatis expire |
| Detail manga | 1 jam | `jikan.detail.{malId}` | Otomatis expire |
| Pictures | 1 jam | `jikan.pictures.{malId}` | Otomatis expire |

**Cache driver:** database (sesuai ADR-004 — tidak ada Redis).

**Alasan TTL:**
- Search: 5 menit — user biasanya search beberapa kali untuk judul yang sama dalam satu sesi
- Detail: 1 jam — data MAL jarang berubah dalam waktu singkat
- Setelah import berhasil, data sudah ada di DB — tidak perlu cache lagi

---

## 6. Cover Download Flow

```
Admin import series
        │
        ▼
importSeries($malId) dipanggil
        │
        ▼
Ambil large_image_url dari Jikan response
        │
        ├── URL null/kosong ──────────────────────────┐
        │                                             │
        ▼                                             │
Http::get($imageUrl, timeout: 30)                    │
        │                                             │
        ├── Gagal (timeout/404/5xx) ─────────────────┤
        │                                             │
        ▼                                             │
Cek response successful()                            │
        │                                             │
        ├── Tidak sukses ────────────────────────────┤
        │                                             │
        ▼                                             │
Tentukan extension dari URL                          │
Path: covers/series/{uuid}.{ext}                     │
        │                                             │
        ▼                                             │
Storage::disk('r2')->put($path, $response->body())   │
        │                                             │
        ├── Upload gagal ────────────────────────────┤
        │                                             │
        ▼                                             ▼
series->update(['cover_path' => $path])    cover_path = null
                                           Series tetap tersimpan
```

**Prinsip:** Cover adalah optional enhancement. Kegagalan download/upload cover **tidak boleh** menggagalkan import series.

---

## 7. Import Job Design

```php
// app/Jobs/ImportMangaFromJikanJob.php

namespace App\Jobs;

use App\Models\Series;
use App\Services\Contracts\JikanServiceInterface;
use App\Exceptions\Jikan\{DuplicateSeriesException, NotFoundException, InvalidResponseException};
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class ImportMangaFromJikanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 180]; // detik antar retry

    public int $timeout = 120; // 2 menit max per attempt

    public function __construct(
        public readonly int    $malId,
        public readonly string $adminUserId, // untuk kirim notifikasi balik ke admin
    ) {}

    public function handle(JikanServiceInterface $jikan): void
    {
        $series = $jikan->importSeries($this->malId);

        // Kirim notifikasi sukses ke admin yang men-trigger import
        Notification::make()
            ->title("Import berhasil: {$series->title_romaji}")
            ->success()
            ->sendToDatabase(\App\Models\User::find($this->adminUserId));
    }

    public function failed(\Throwable $exception): void
    {
        // Jangan retry untuk error yang sifatnya final
        if ($exception instanceof DuplicateSeriesException ||
            $exception instanceof NotFoundException ||
            $exception instanceof InvalidResponseException) {
            $this->fail($exception);
        }

        // Kirim notifikasi gagal ke admin
        Notification::make()
            ->title('Import gagal')
            ->body($exception->getMessage())
            ->danger()
            ->sendToDatabase(\App\Models\User::find($this->adminUserId));
    }
}
```

**Cara dispatch dari Filament action:**

```php
ImportMangaFromJikanJob::dispatch(
    malId: $malId,
    adminUserId: auth()->id(),
);

Notification::make()
    ->title('Import sedang diproses...')
    ->info()
    ->send();
```

---

## 8. R2 Storage Config

### config/filesystems.php

```php
'disks' => [
    // ... disk lainnya

    'r2' => [
        'driver'                  => 's3',
        'key'                     => env('AWS_ACCESS_KEY_ID'),
        'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
        'region'                  => env('AWS_DEFAULT_REGION', 'auto'),
        'bucket'                  => env('AWS_BUCKET'),
        'url'                     => env('AWS_URL'),
        'endpoint'                => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => true,  // wajib untuk R2
        'throw'                   => false, // jangan throw exception langsung, handle manual
    ],
],
```

### .env Variables

```env
FILESYSTEM_DISK=r2
FILAMENT_FILESYSTEM_DISK=r2

AWS_ACCESS_KEY_ID=your-r2-access-key-id
AWS_SECRET_ACCESS_KEY=your-r2-secret-access-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=malas-media
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_URL=https://pub-<hash>.r2.dev
```

> `AWS_URL` adalah public URL bucket (muncul di dashboard R2 jika bucket di-set public).  
> Jika bucket private, gunakan signed URL (lihat di bawah).

### Generate Public URL

```php
// Untuk bucket public
$url = Storage::disk('r2')->url($coverPath);
// Output: https://pub-<hash>.r2.dev/covers/series/uuid.jpg

// Atau manual
$url = config('filesystems.disks.r2.url') . '/' . $coverPath;
```

### Signed URL (Bucket Private)

```php
// Signed URL berlaku 1 jam
$url = Storage::disk('r2')->temporaryUrl(
    path: $coverPath,
    expiration: now()->addHour(),
);
```

Untuk cover manga yang bersifat publik, **gunakan public bucket** — tidak perlu signed URL. Signed URL lebih cocok untuk file sensitif.

### Dependency

R2 menggunakan AWS SDK. Pastikan package terinstall:

```bash
composer require league/flysystem-aws-s3-v3
```
