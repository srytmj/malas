# API Contract — Internal Admin AJAX Endpoints

Dokumen ini mendeskripsikan endpoint yang dikonsumsi oleh JavaScript frontend (Alpine.js, TomSelect, DataTable) di panel admin. Semua endpoint memerlukan autentikasi session dan role `super_admin`.

## Auth & Headers

Semua request memerlukan:
- Cookie session aktif (sudah login)
- Header `X-Requested-With: XMLHttpRequest`
- Untuk request mutasi (POST/PATCH/DELETE): header `X-CSRF-TOKEN: <token>`

## DataTable Endpoints

### GET /admin/series

AJAX DataTable untuk daftar series.

**Query Parameters:**
| Param | Type | Keterangan |
|---|---|---|
| `draw` | int | Echo kembali di response (untuk deteksi out-of-order) |
| `start` | int | Offset pagination (default: 0) |
| `length` | int | Jumlah per halaman (default: 25) |
| `search[value]` | string | Filter teks (title_romaji, title_english, mal_id) |
| `status_filter` | string | Filter status: `publishing`, `finished`, `on_hiatus`, `discontinued`, `not_yet_published` |
| `order[0][column]` | int | Kolom sort (0=title_romaji, 1=status, 2=total_volumes, 3=score) |
| `order[0][dir]` | string | `asc` atau `desc` |

**Response 200:**
```json
{
  "draw": 1,
  "recordsTotal": 1500,
  "recordsFiltered": 42,
  "data": [
    {
      "id": "uuid",
      "title_romaji": "Berserk",
      "title_english": "Berserk",
      "mal_id": 2,
      "status": "publishing",
      "total_volumes": 41,
      "score": "9.45",
      "cover_url": "https://pub-xxx.r2.dev/covers/series/xxx.jpg",
      "trashed": false,
      "show_url": "/admin/series/uuid",
      "edit_url": "/admin/series/uuid/edit",
      "delete_url": "/admin/series/uuid",
      "delete_token": "csrf-token"
    }
  ]
}
```

### GET /admin/users

AJAX DataTable untuk daftar users. Parameter sama dengan series + tambahan:
| Param | Keterangan |
|---|---|
| `role` | Filter: `user`, `super_admin` |
| `status` | Filter: `banned`, `deleted` |

**Response data item:**
```json
{
  "id": "uuid",
  "name": "John Doe",
  "email": "john@example.com",
  "role": "user",
  "is_banned": false,
  "trashed": false,
  "show_url": "/admin/users/uuid",
  "ban_url": "/admin/users/uuid/ban",
  "unban_url": null,
  "delete_url": "/admin/users/uuid"
}
```

## Internal API Endpoints

### GET /admin/api/series/search

Autocomplete series untuk TomSelect (form tambah koleksi).

**Query Parameters:**
| Param | Type | Keterangan |
|---|---|---|
| `q` | string | Search query, minimal 2 karakter |

**Response 200:**
```json
[
  {
    "id": "uuid",
    "title_romaji": "Naruto",
    "title_english": "Naruto"
  }
]
```

Batas: 25 hasil. Error jika `q` < 2 karakter: response kosong `[]`.

### GET /admin/api/series/{series}/volumes

Daftar volume dari suatu series, dengan filter volume yang sudah dimiliki user.

**Path Parameters:**
| Param | Type | Keterangan |
|---|---|---|
| `series` | uuid | UUID series |

**Query Parameters:**
| Param | Type | Keterangan |
|---|---|---|
| `user_id` | uuid | (Opsional) UUID user — exclude volumes yang sudah dimiliki user ini |

**Response 200:**
```json
{
  "volumes": [
    {
      "id": "uuid",
      "volume_number": "1",
      "title": "Volume 1: The Beginning"
    }
  ]
}
```

**Response 200 (kosong):**
```json
{
  "volumes": []
}
```

## Mutation Endpoints

### POST /admin/collections/bulk

Tambah banyak volume ke koleksi user sekaligus.

**Request Body (JSON):**
```json
{
  "user_id": "uuid",
  "entries": [
    {
      "series_id": "uuid",
      "volume_ids": ["uuid1", "uuid2"],
      "condition": "good",
      "is_for_loan": true,
      "purchase_price": 75000,
      "purchase_date": "2026-01-15",
      "notes": "Beli di Gramedia"
    }
  ]
}
```

**Validasi:**
- `user_id`: required, exists di `users`
- `entries`: required array, min 1
- `entries.*.series_id`: required, exists di `series`
- `entries.*.volume_ids`: required array, min 1, each exists di `volumes`
- `entries.*.condition`: required, in: `mint|very_good|good|fair|poor`
- `entries.*.is_for_loan`: boolean
- `entries.*.purchase_price`: nullable, numeric, min 0
- `entries.*.purchase_date`: nullable, date
- `entries.*.notes`: nullable, string

**Response 200:**
```json
{
  "message": "5 volume koleksi berhasil ditambahkan.",
  "count": 5
}
```

**Response 422 (validation error):**
```json
{
  "message": "The entries.0.condition field is required.",
  "errors": { ... }
}
```

### POST /admin/series/batch-destroy

Hapus banyak series sekaligus (soft delete).

**Request Body (JSON):**
```json
{
  "ids": ["uuid1", "uuid2", "uuid3"],
  "reason": "Series duplikat dari scraping Jikan"
}
```

**Validasi:**
- `ids`: required array, min 1, max 200
- `reason`: required string, min 5 karakter

**Response 200:**
```json
{
  "message": "3 series berhasil dihapus.",
  "count": 3
}
```

**Notes:** Series yang sudah di-trashed sebelumnya di-skip (tidak dihitung). ActivityLog dibuat per series yang berhasil dihapus.

### GET /admin/jikan/status

Status scraping Jikan untuk polling di halaman admin.

**Response 200:**
```json
{
  "active": {
    "id": 5,
    "status": "running",
    "current_page": 12,
    "total_pages": 45,
    "schedule_name": "Manga 2020-2023",
    "started_at": "2026-06-13T10:30:00Z"
  },
  "schedules": [
    {
      "id": 1,
      "name": "Semua Manga",
      "hour": 2,
      "minute": 0,
      "start_year": null,
      "end_year": null,
      "sort_order": 0,
      "last_run_at": "2026-06-13T02:00:00Z"
    }
  ],
  "recent_sessions": [ ... ]
}
```
