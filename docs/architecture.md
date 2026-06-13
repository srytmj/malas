# Architecture Deep Dive

## Keputusan Arsitektur Utama

### 1. Modular Monolith (bukan Microservices)
Semua domain (Core, Collection, Admin, Jikan) dalam satu aplikasi Laravel. Trade-off: lebih mudah di-develop dan debug tanpa network overhead, cocok untuk skala MVP.

### 2. Custom Admin Panel (bukan Filament)
Admin panel dibangun manual dengan Blade + Alpine.js. Keuntungan: kontrol penuh atas UI/UX, tidak ada dependency besar. Kekurangan: lebih banyak kode boilerplate.

### 3. Database Queue (bukan Redis)
`QUEUE_CONNECTION=database` — job queue disimpan di tabel `jobs`. Mudah disetup (tidak perlu Redis server). Cukup untuk volume scraping yang tidak real-time.

### 4. UserLibrary sebagai Bridge
Alih-alih hubungan langsung `user_id → volume_id`, MALAS menggunakan `users → user_libraries → user_collections → volumes`. Keuntungan: bisa menyimpan metadata per-series-per-user (misalnya statistik koleksi per series), dan memudahkan query "semua volume yang dimiliki user dari series X".

### 5. UUID Primary Keys via HasUuids
Semua model utama menggunakan UUID (bukan auto-increment integer). Keuntungan: aman untuk merge data dari multiple sumber, tidak bocorkan jumlah record via URL.

### 6. $guarded = [] (bukan $fillable)
Semua model menggunakan `$guarded = []`. Validasi dilakukan di layer controller/FormRequest. Pattern ini lebih fleksibel untuk development cepat.

## Data Flow Diagrams

### AJAX DataTable Pattern

```
Browser                     Controller (SeriesController)
  |                               |
  |-- GET /admin/series?draw=1 -->|  (X-Requested-With: XMLHttpRequest)
  |   start=0&length=25           |
  |   search[value]=naruto        |  detect $request->ajax()
  |   status_filter=publishing    |  → datatableResponse()
  |                               |  → DB query dengan filter
  |<-- JSON {draw,               |  → map ke array
  |    recordsTotal,             |
  |    recordsFiltered,          |
  |    data[{id,title,...,       |
  |    show_url, edit_url...}]}  |
  |                               |
  |  renderRow(s) per item        |
  |  → innerHTML ke tbody         |
```

### TomSelect AJAX Series Search

```
TomSelect onChange=load          AdminApiController
  |                                    |
  |-- GET /admin/api/series/search --> |
  |   ?q=naru (min 2 chars)            |  searchSeries()
  |                                    |  → Series::where(title_romaji LIKE %)
  |<-- JSON [{id, title_romaji,       |  → limit 25
  |    title_english}]                |
  |                                    |
  |-- GET /admin/api/series/{id}/volumes?user_id=X --> |
  |                                    |  seriesVolumes()
  |                                    |  → excludes volumes user already owns
  |<-- JSON {volumes: [{id, volume_number, title}]} |
```

### Jikan Scraping Queue Chain

```
routes/console.php (scheduler)
  every minute:
    → loop JikanSchedule
    → if hour:minute matches now:
        if session running/queued → create 'queued' session
        else → create 'pending' session + dispatch ScrapeJikanPageJob(sessionId, page=1)

ScrapeJikanPageJob::handle()
  → load session (with schedule for start/end year)
  → GET jikan.moe/v4/manga?page=N&order_by=...&start_date=...&end_date=...
  → foreach manga: Series::updateOrCreate(['mal_id' => $mal_id], [...])
  → if has_next_page → dispatch ScrapeJikanPageJob(sessionId, page=N+1)
  → else → session.status = 'completed'
          → JikanSchedule::last_run_at = now()
          → dispatchNextQueued()
              → find next 'queued' session ordered by schedule.sort_order
              → session.status = 'running' → dispatch ScrapeJikanPageJob
```

## Security Model

Semua route admin di-protect oleh dua middleware:
1. `auth` — harus login
2. `role:super_admin` — harus punya role `super_admin`

Request validation di controller sebelum setiap operasi tulis. CSRF protection aktif di semua form (termasuk AJAX via `X-CSRF-TOKEN` header).

Lihat [security.md](./security.md) untuk detail lengkap.

## Performance Considerations

| Area | Pertimbangan |
|---|---|
| **DataTable** | Server-side pagination — tidak load semua record ke browser |
| **Series search** | LIKE query dengan `%title%` — cukup untuk data volume kecil, bisa jadi bottleneck jika >10k series |
| **Jikan scraping** | Queue-based, tidak blocking request. Page-by-page untuk hindari timeout |
| **Cover image** | Disimpan di R2 (CDN), bukan served dari server langsung |
| **Assets** | Vite build dengan code splitting; Alpine.js ringan (~15KB) |

## Known Technical Debt

- Cover image path di `SeriesController::datatableResponse()` menggunakan `Storage::disk('public')` — seharusnya `Storage::disk()` atau `Storage::disk('r2')` karena default disk sudah r2.
- Tidak ada test suite yang aktif — semua testing manual.
- `UserManagementController` belum diubah ke AJAX pattern sepenuhnya untuk actions (ban/unban masih form POST biasa).
