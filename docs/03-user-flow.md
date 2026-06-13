# User Flow Diagrams

Dokumen ini berisi flow utama dalam aplikasi MALAS (saat ini semua operasi via panel admin `super_admin`).

## Flow A: Admin Tambah Koleksi Bulk

```mermaid
flowchart TD
    A[Buka /admin/collections/create] --> B[Pilih Anggota\nTomSelect dari daftar users]
    B --> C[Klik + Tambah Manga]
    C --> D[Ketik judul manga\nTomSelect AJAX ke /admin/api/series/search]
    D --> E{Series ditemukan?}
    E -- Tidak --> D
    E -- Ya --> F[Pilih series\nonChange → load volumes]
    F --> G[AJAX GET /admin/api/series/id/volumes?user_id=...\nFilter volume yang sudah dimiliki anggota]
    G --> H[Centang volume yang ingin ditambah\nSelect all / deselect all tersedia]
    H --> I[Isi detail per manga\nKondisi, harga, tanggal, catatan, bisa dipinjam]
    I --> J{Tambah manga lain?}
    J -- Ya --> C
    J -- Tidak --> K[Klik Simpan Koleksi]
    K --> L[POST /admin/collections/bulk\nJSON: user_id + entries array per manga]
    L --> M{Validasi OK?}
    M -- Gagal --> N[Tampilkan error di form]
    M -- OK --> O[Loop: UserLibrary::firstOrCreate\nUserCollection::create per volume]
    O --> P[Return JSON success]
    P --> Q[Redirect ke /admin/collections setelah 1.2 detik]
```

## Flow B: Admin Menambah Series Manual

```mermaid
flowchart TD
    A[Buka /admin/series/create] --> B[Isi form: judul, status, synopsis, dll]
    B --> C[Upload cover image opsional]
    C --> D[Submit POST /admin/series]
    D --> E[Validasi\nmal_id unique, cover max 2048KB]
    E -- Gagal --> B
    E -- OK --> F[Store cover ke R2\nStorage::disk store covers/series/]
    F --> G[Series::create]
    G --> H[ActivityLog::create series.created]
    H --> I[Redirect ke /admin/series/id]
```

## Flow C: Admin Jikan Scraping

```mermaid
flowchart TD
    A[Buka /admin/jikan] --> B[Lihat daftar schedules\nSortableJS drag-drop untuk reorder]
    B --> C{Aksi?}
    C -- Tambah jadwal --> D[Isi modal: nama, jam, menit, rentang tahun]
    D --> E[POST /admin/jikan/schedules]
    C -- Scrape Sekarang --> F[Buka modal Scrape Now\nOpsional: isi year range]
    F --> G[POST /admin/jikan/scrape-now]
    G --> H[Buat JikanScrapeSession pending\nDispatch ScrapeJikanPageJob ke queue]
    H --> I[Polling status setiap 2 detik\nGET /admin/jikan/status]
    I --> J{Status?}
    J -- running --> I
    J -- completed --> K[Tampilkan ringkasan:\njumlah series diupsert]
    J -- failed --> L[Tampilkan error message]
    C -- Edit/Hapus jadwal --> M[PATCH/DELETE /admin/jikan/schedules/id]
```

## Flow D: Admin Batch Delete Series

```mermaid
flowchart TD
    A[Buka /admin/series] --> B[Centang checkbox satu atau lebih baris]
    B --> C[Batch bar muncul dengan jumlah terpilih]
    C --> D[Klik Hapus Terpilih]
    D --> E[Confirm dialog: hapus N series?]
    E -- Batal --> B
    E -- OK --> F[Prompt alasan penghapusan\nmin 5 karakter]
    F --> G[POST /admin/series/batch-destroy\nJSON: ids array + reason]
    G --> H[Loop: Series::find → deleteWithReason + ActivityLog per series]
    H --> I[Return JSON: N series berhasil dihapus]
    I --> J[Reload DataTable\nClear selection]
```

## Flow E: Login Super Admin

```mermaid
flowchart TD
    A[Buka /login] --> B[Isi email + password]
    B --> C[POST /login]
    C --> D{Credential valid?}
    D -- Tidak --> E[Error: credentials tidak cocok]
    D -- Ya --> F{Role?}
    F -- user --> G[Redirect ke / halaman user]
    F -- super_admin --> H[Redirect ke /admin]
    H --> I[Dashboard admin\nStatistik koleksi, series, loans]
```

## Flow F: Admin Peminjaman Volume

```mermaid
flowchart TD
    A[Buka /admin/loans/create] --> B[Pilih peminjam\nisi nama dan kontak]
    B --> C[Pilih volume dari koleksi anggota]
    C --> D[Set due date opsional]
    D --> E[POST /admin/loans]
    E --> F[Buat Loan + LoanItem]
    F --> G[Status: active]
    G --> H{Update status?}
    H -- Dikembalikan --> I[PATCH /admin/loans/id/return\nStatus → returned, isi return_date]
    H -- Hilang --> J[PATCH /admin/loans/id/lost\nStatus → lost]
```
