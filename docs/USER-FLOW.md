# User Flow — MALAS (Manga Library Admin System)

**Versi:** 1.0  
**Tanggal:** 2026-06-14

Semua flow menggunakan konvensi warna:
- **Hijau** (`:::success`) — happy path
- **Merah** (`:::error`) — error path  
- **Kuning** (`:::decision`) — decision point

---

## Flow 1: Admin — Onboarding Series Baru via Jikan

Skenario: admin ingin menambah series manga baru ke sistem menggunakan data dari MyAnimeList.

```mermaid
flowchart TD
    A([Admin login ke panel]) --> B[Buka menu Jikan Search]
    B --> C[Ketik judul manga di search box]
    C --> D{Jikan API merespons?}:::decision

    D -->|Timeout / Error| E[Tampil pesan error:\nJikan sedang tidak tersedia]:::error
    E --> F[Admin bisa retry manual]
    F --> C

    D -->|Rate limited 429| G[Sistem tunggu 1 detik\nlalu retry otomatis]:::error
    G --> D

    D -->|Sukses| H[Tampil grid hasil:\ncover, judul, status, score]:::success

    H --> I{Hasil ditemukan?}:::decision
    I -->|Tidak ada hasil| J[Tampil: Tidak ditemukan.\nCoba kata kunci lain]:::error
    J --> C

    I -->|Ada hasil| K[Admin klik salah satu hasil]
    K --> L[Modal preview terbuka:\ndata lengkap dari Jikan]
    L --> M{Admin konfirmasi import?}:::decision

    M -->|Batal| N[Modal tutup, kembali ke hasil]
    N --> H

    M -->|Konfirmasi| O{mal_id sudah ada di DB?}:::decision
    O -->|Ya| P[Error: Series sudah ada di sistem]:::error
    P --> H

    O -->|Tidak| Q[ImportMangaFromJikanJob\ndikirim ke queue]:::success
    Q --> R[Notifikasi: Import sedang diproses]
    R --> S[Background: Download cover dari URL Jikan]

    S --> T{Cover berhasil didownload?}:::decision
    T -->|Gagal| U[cover_path = null\nSeries tetap disimpan]:::error
    T -->|Berhasil| V[Upload cover ke Cloudflare R2]:::success
    V --> W{Upload R2 sukses?}:::decision
    W -->|Gagal| U
    W -->|Sukses| X[cover_path diisi path R2]:::success

    U --> Y[Series tersimpan di DB]
    X --> Y
    Y --> Z[Notifikasi panel: Import berhasil!]:::success
    Z --> AA([Series muncul di daftar Series])
```

**Catatan:** Import berjalan di background queue. Admin tidak perlu menunggu — bisa lanjut kerja lain. Notifikasi muncul saat job selesai via Filament notification.

---

## Flow 2: Admin — Tambah Volume ke Series

Skenario: series sudah ada, admin ingin menambah data volume fisik yang dimiliki.

```mermaid
flowchart TD
    A([Admin buka halaman Series]) --> B[Klik series yang dituju]
    B --> C[Halaman detail series terbuka]
    C --> D[Klik tab Volumes]
    D --> E[Klik tombol + Tambah Volume]
    E --> F[Form inline terbuka:\nvolume_number, isbn, published_at]

    F --> G[Admin isi form]
    G --> H{Validasi form?}:::decision

    H -->|volume_number kosong| I[Error: Nomor volume wajib diisi]:::error
    I --> F

    H -->|Nomor volume duplikat\ndalam series ini| J[Error: Nomor volume sudah ada]:::error
    J --> F

    H -->|Valid| K[Klik Simpan]:::success
    K --> L[Volume tersimpan di DB]
    L --> M[Activity log dicatat:\nuser X menambah Volume N]
    M --> N[Table volumes di-refresh]
    N --> O([Volume muncul di list,\nurut by volume_number]):::success
```

**Catatan:** Volume ditampilkan sebagai `RelationManager` di dalam `SeriesResource`. Form inline — tidak perlu buka halaman baru.

---

## Flow 3: Admin — Catat Peminjaman Baru

Skenario: ada teman yang mau pinjam volume dari koleksi admin.

```mermaid
flowchart TD
    A([Admin buka Loan Resource]) --> B[Klik New Loan]
    B --> C[Form peminjaman terbuka]

    C --> D[Pilih Collection\npemilik + volume]
    D --> E{Volume sedang dipinjam?}:::decision

    E -->|Ya, ada loan active/pending| F[Error: Volume ini sedang dipinjam]:::error
    F --> D

    E -->|Tidak| G[Isi data peminjam:\nnama, kontak, due_date]:::success
    G --> H[Opsional: pilih borrower_user_id\njika peminjam terdaftar di sistem]
    H --> I{Validasi form?}:::decision

    I -->|due_date di masa lalu| J[Error: Due date harus di masa depan]:::error
    J --> G

    I -->|borrower_name kosong| K[Error: Nama peminjam wajib diisi]:::error
    K --> G

    I -->|Valid| L[Klik Simpan]:::success
    L --> M[Loan dibuat dengan status: pending]
    M --> N[Admin klik Approve pada loan]
    N --> O[Status → active\nloaned_at = now]
    O --> P[Activity log dicatat]
    P --> Q{Email borrower tersedia?}:::decision

    Q -->|Ya| R[Kirim notifikasi email\nkonfirmasi peminjaman]:::success
    Q -->|Tidak| S[Skip notifikasi]

    R --> T([Loan aktif tercatat di sistem]):::success
    S --> T
```

---

## Flow 4: Admin — Proses Pengembalian

Skenario: peminjam mengembalikan volume, admin mencatat di sistem.

```mermaid
flowchart TD
    A([Admin buka Loan Resource]) --> B[Filter: status = active ATAU overdue]
    B --> C[Cari loan yang akan dikembalikan]
    C --> D[Klik action Return pada loan]
    D --> E[Modal konfirmasi muncul:\nApakah volume sudah dikembalikan?]

    E --> F{Admin konfirmasi?}:::decision
    F -->|Batal| G[Modal tutup, tidak ada perubahan]

    F -->|Konfirmasi| H[Status → returned]:::success
    H --> I[returned_at = now]
    I --> J[Activity log dicatat:\nLoan X dikembalikan oleh admin Y]
    J --> K[Volume kembali tersedia\nuntuk dipinjam lagi]
    K --> L{Kondisi volume perlu diupdate?}:::decision

    L -->|Ya| M[Admin buka Collection record\nupdate condition jika perlu]
    M --> N([Collection condition terupdate]):::success
    L -->|Tidak| O([Selesai, loan berstatus returned]):::success
```

---

## Flow 5: Admin — Manajemen User (Ban)

Skenario: admin ingin memblokir akses user tertentu.

```mermaid
flowchart TD
    A([Super Admin buka User Resource]) --> B[Cari user yang akan di-ban]
    B --> C{User adalah super_admin?}:::decision

    C -->|Ya| D[Tombol Ban tidak tersedia\nPolicy mencegah ban super_admin]:::error
    D --> B

    C -->|Tidak| E[Klik action Ban User]
    E --> F[Modal konfirmasi:\nisi alasan ban]

    F --> G{Ban reason diisi?}:::decision
    G -->|Kosong| H[Error: Alasan ban wajib diisi]:::error
    H --> F

    G -->|Diisi| I[Klik Konfirmasi]:::success
    I --> J[is_banned = true\nban_reason = input\nbanned_at = now]
    J --> K[Activity log dicatat]
    K --> L[Session aktif user dihapus\nBreezy browser sessions cleared]
    L --> M{User sedang login?}:::decision

    M -->|Ya| N[User di-redirect ke halaman login\ndengan pesan: Akun Anda di-ban]:::error
    M -->|Tidak| O[User tidak bisa login\npada percobaan berikutnya]:::error

    N --> P([User tidak bisa akses sistem]):::success
    O --> P
```

---

## Flow 6: User (Borrower) — Melihat Katalog

Skenario: teman peminjam ingin melihat koleksi apa yang tersedia.

```mermaid
flowchart TD
    A([User akses URL portal]) --> B{Sudah login?}:::decision

    B -->|Belum| C[Redirect ke halaman login]
    C --> D[User isi email + password]
    D --> E{Kredensial valid?}:::decision

    E -->|Salah| F[Error: Email atau password salah]:::error
    F --> D

    E -->|Benar tapi di-ban| G[Error: Akun Anda di-ban.\nAlasan: ban_reason]:::error
    G --> H([User tidak bisa masuk])

    E -->|Benar| I[Login sukses, redirect ke katalog]:::success

    B -->|Sudah| J[Halaman katalog series]
    I --> J

    J --> K[Tampil daftar series:\ncover, judul, status, total volume]
    K --> L[User bisa filter: status terbit,\nsearch by judul]
    L --> M[User klik series]
    M --> N[Halaman detail series:\nsynopsis, daftar volume + status ketersediaan]

    N --> O{Volume tersedia?}:::decision
    O -->|Sedang dipinjam| P[Label: Sedang Dipinjam]:::error
    O -->|Tersedia| Q[Label: Tersedia]:::success

    N --> R[User lihat status pinjaman sendiri]
    R --> S([User tahu apa yang bisa dipinjam]):::success
```

---

## Flow 7: Loan Status Machine

State machine lengkap untuk status peminjaman.

```mermaid
stateDiagram-v2
    [*] --> pending : Admin buat loan baru

    pending --> active : Admin klik Approve\n(loaned_at = now)
    pending --> cancelled : Admin klik Cancel\n(sebelum disetujui)

    active --> returned : Admin klik Return\n(returned_at = now)
    active --> overdue : Scheduler harian\n(due_date < today)
    active --> lost : Admin klik Mark as Lost

    overdue --> returned : Admin klik Return\n(returned_at = now)
    overdue --> lost : Admin klik Mark as Lost

    returned --> [*]
    cancelled --> [*]
    lost --> [*]
```

**Aturan transisi:**
| Dari | Ke | Trigger | Actor |
|------|----|---------|-------|
| `pending` | `active` | Approve | Admin manual |
| `pending` | `cancelled` | Cancel | Admin manual |
| `active` | `returned` | Return | Admin manual |
| `active` | `overdue` | `MarkOverdueLoansCommand` | Scheduler (01:00 WIB) |
| `active` | `lost` | Mark as Lost | Admin manual |
| `overdue` | `returned` | Return | Admin manual |
| `overdue` | `lost` | Mark as Lost | Admin manual |

Transisi yang **tidak valid** akan ditolak oleh method `canTransitionTo()` di model `Loan`.

---

## Flow 8: Jikan Import Error Handling

Skenario-skenario error saat melakukan import dari Jikan API.

```mermaid
flowchart TD
    A[ImportMangaFromJikanJob berjalan] --> B[HTTP GET ke Jikan API]

    B --> C{HTTP Response?}:::decision

    C -->|200 OK| D[Parse response JSON]:::success
    C -->|429 Rate Limited| E[Catat attempt ke RateLimiter]:::error
    C -->|404 Not Found| F[Throw NotFoundException\nTidak di-retry]:::error
    C -->|503 Service Unavailable| G[Throw ServiceUnavailableException]:::error
    C -->|Timeout 10s connect\n30s read| H[Throw ConnectionException]:::error

    E --> I[Sleep 1 detik]
    I --> J{Attempt ke-berapa?}:::decision
    J -->|< 3| B
    J -->|≥ 3| K[Job masuk failed_jobs\nAdmin bisa retry manual]:::error

    G --> J
    H --> J

    F --> L([Job selesai dengan failure,\ntidak masuk retry queue])

    D --> M{Data lengkap?}:::decision
    M -->|mal_id atau title kosong| N[Throw InvalidResponseException\nTidak di-retry]:::error
    N --> L

    M -->|Data lengkap| O[Buat record Series di DB]:::success
    O --> P[Download cover dari image_url]

    P --> Q{Cover URL valid?}:::decision
    Q -->|URL kosong / 404| R[cover_path = null\nLanjut tanpa cover]:::error
    Q -->|Download timeout| R
    R --> S[Series disimpan tanpa cover]

    Q -->|Berhasil download| T[Upload ke R2]:::success
    T --> U{R2 upload sukses?}:::decision
    U -->|Gagal| R
    U -->|Sukses| V[cover_path = R2 path]:::success
    V --> S

    S --> W([Job selesai sukses\nNotifikasi Filament dikirim]):::success
```

**Ringkasan penanganan error:**

| Error | Behavior | Retry? |
|-------|----------|--------|
| 429 Rate Limited | Sleep 1s, retry | Ya (max 3x) |
| 404 Not Found | Fail immediately | Tidak |
| 503 / Timeout | Retry dengan backoff | Ya (10s, 60s, 180s) |
| Data tidak lengkap | Fail immediately | Tidak |
| Cover URL invalid | Skip cover, lanjut | N/A |
| R2 upload gagal | Skip cover, lanjut | N/A |
| `mal_id` duplikat | Fail immediately | Tidak |
