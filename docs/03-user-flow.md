# User Flow Diagrams

Dokumen ini berisi 4 flow utama dalam aplikasi MALAS: penambahan koleksi fisik, peminjaman volume, restore data oleh admin, dan login/register.

## Flow A: User Menambah Koleksi Fisik

```mermaid
flowchart TD
    A[Login] --> B[Cari Series<br/>POST /api/series/search]
    B --> C{Series ditemukan?}
    C -- Tidak --> B
    C -- Ya --> D[Pilih Series<br/>GET /api/series/id]
    D --> E[Pilih Volume dari daftar volumes]
    E --> F[Tambah ke Koleksi<br/>POST /api/user/collections]
    F --> G[Pilih Ownership Status]
    G --> H{Status?}
    H -- owned --> I[Isi condition, storage_location,<br/>purchase_date, purchase_price]
    H -- missing --> J[Simpan tanpa detail kepemilikan]
    H -- wishlist --> J
    H -- preordered --> J
    I --> K[Simpan ke user_collections]
    J --> K
    K --> L[Invalidate cache user dashboard]
    L --> M[Tampilkan koleksi terupdate]
```

## Flow B: User Meminjamkan Volume

```mermaid
flowchart TD
    A[Buka Koleksi<br/>GET /api/user/collections] --> B[Pilih Volume owned]
    B --> C[Klik Pinjam]
    C --> D{Volume sedang dipinjam?<br/>cek loans aktif}
    D -- Ya --> E[Tolak: tampilkan error<br/>volume sudah dipinjam]
    D -- Tidak --> F[Input borrower_name,<br/>borrower_contact, due_date]
    F --> G[Konfirmasi]
    G --> H[POST /api/loans]
    H --> I[Buat record loans<br/>status = active]
    I --> J[Catat loan_events<br/>event_type = created]
    J --> K[Tampilkan konfirmasi peminjaman]
```

## Flow C: Admin Restore Data

```mermaid
flowchart TD
    A[Login Admin Panel] --> B[Buka halaman Trashed Data]
    B --> C[onlyTrashed: tampilkan record<br/>deleted_at IS NOT NULL]
    C --> D[Cari record<br/>filter entity_type, deleted_by, deletion_reason]
    D --> E[Klik Restore]
    E --> F[Tampilkan form<br/>'Alasan Restore' wajib diisi]
    F --> G{Alasan diisi?}
    G -- Tidak --> F
    G -- Ya --> H[POST /api/admin/restore/entityType/id<br/>body: reason]
    H --> I[Panggil method restore]
    I --> J[deleted_at = NULL<br/>deleted_by = NULL<br/>deletion_reason tetap]
    J --> K[Catat ke activity_log<br/>action=restored, reason=alasan restore]
    K --> L[Tampilkan notifikasi sukses]
```

## Flow D: User Login & Register

```mermaid
flowchart TD
    A[Buka Aplikasi] --> B{Sudah punya akun?}

    B -- Tidak --> C[Buka Form Register]
    C --> D[Input name, email, password]
    D --> E[POST /api/register]
    E --> F{Validasi sukses?}
    F -- Tidak --> G[Tampilkan error validasi<br/>email sudah terdaftar / password lemah]
    G --> C
    F -- Ya --> H[User dibuat dengan role=user]
    H --> I[Auto-login: generate Sanctum token]

    B -- Ya --> J[Buka Form Login]
    J --> K[Input email, password]
    K --> L[POST /api/login]
    L --> M{Kredensial valid?}
    M -- Tidak --> N[Tampilkan error<br/>email/password salah]
    N --> J
    M -- Ya --> I

    I --> O[Simpan token di client<br/>secure storage]
    O --> P[GET /api/user<br/>ambil data profil + role]
    P --> Q{role?}
    Q -- "user" --> R[Redirect ke Dashboard User]
    Q -- "admin/super_admin" --> S[Tampilkan opsi akses Filament Admin Panel]
```
