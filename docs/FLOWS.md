# FLOWS — MALAS v2

**Versi:** 2.0  
**Tanggal:** 2026-06-26

---

## 1. Navigation Map

### Admin Sidebar
```
Dashboard             /admin/dashboard
├── Katalog
│   ├── Series        /admin/series
│   └── Volume        (nested di halaman detail series, bukan route sendiri)
├── Koleksi           /admin/collections
├── Peminjaman        /admin/loans
├── Pengguna          /admin/users
├── Sistem
│   ├── Menu          /admin/menus
│   └── Pengumuman    /admin/announcements
└── Jikan Search      /admin/jikan
```

Catatan: tidak ada `/admin/roles` dan `/admin/settings`. Role management bagian dari halaman detail user (`/admin/users/{id}`).

### User Sidebar
```
Dashboard             /dashboard
Katalog               /catalog              (browse series, read-only)
Koleksiku             /my-collection        (koleksi milik sendiri)
Pinjaman Saya         /my-loans
```

---

## 2. Auth Flows

### Login
```
GET /login
  └─ Isi email + password → POST /login
        ├─ Gagal → kembali ke form, tampil error inline
        └─ Sukses → redirect berdasarkan role:
              ├─ admin / super_admin → /admin/dashboard
              └─ user → /dashboard
```

### Akses Route Terproteksi
```
Request masuk
  └─ auth middleware
        ├─ Belum login → redirect /login
        └─ Sudah login
              └─ EnsureNotBanned
                    ├─ is_banned = true → redirect /banned (halaman info ban)
                    └─ Lanjut
                          └─ CheckMenuAccess
                                ├─ is_maintenance = true (dan bukan admin)
                                │     └─ render Maintenance.tsx dengan pesan
                                ├─ Role tidak ada di role_access
                                │     └─ abort(403)
                                └─ Lanjut ke Controller
```

---

## 3. Admin Flows

### F-A1: Tambah Series Manual
```
/admin/series → klik "Tambah Series"
  └─ Form: judul, status, tipe, cover (upload), sinopsis, score, total volume
        └─ Submit → SeriesController@store
              ├─ Validasi gagal → kembali ke form, error inline per field
              └─ Sukses → redirect /admin/series/{id} + toast "Series berhasil disimpan"
```

### F-A2: Import Series dari Jikan
```
/admin/jikan → ketik judul di search
  └─ Debounce 500ms → GET /admin/jikan/search?q=...
        ├─ Jikan timeout/error → tampil pesan error, tombol retry
        └─ Hasil muncul sebagai grid card (cover, judul, tahun, status)
              └─ Klik card → modal preview (data lengkap)
                    └─ Klik "Import"
                          ├─ MAL ID sudah ada → toast warning "Sudah ada di katalog"
                          └─ Belum ada → import + redirect ke halaman edit series
```

### F-A3: Kelola Volume
```
/admin/series/{id} (halaman detail series)
  └─ Tab "Volume" → daftar volume
        ├─ Tambah volume → form inline
        ├─ Edit volume → /admin/volumes/{id}/edit
        ├─ Hapus volume → konfirmasi dialog
        └─ Generate otomatis (jika status = finished & total_volumes diset)
              └─ Tombol "Generate (N)" → buat volume 1 s/d total_volumes yang belum ada
```

### F-A4: Menu Management
```
/admin/menus → tabel daftar semua menu
  └─ Per baris ada toggle:
        ├─ Visible (tampil/tidak di sidebar user)
        └─ Maintenance (aktif/tidak maintenance mode)
  └─ Klik "Edit" → form: label, icon, sort_order, role_access, maintenance_message
        └─ Save → CheckMenuAccess middleware langsung pakai data baru
```

### F-A5: Ban User
```
/admin/users → klik user
  └─ /admin/users/{id} → tombol "Ban User"
        └─ Modal: isi alasan ban
              └─ Konfirmasi → UserController@ban
                    └─ User logout otomatis + tidak bisa login sampai di-unban
```

### F-A6: Buat Pengumuman
```
/admin/announcements → klik "Buat Pengumuman"
  └─ Form: title, body (markdown editor), type, tanggal mulai-selesai
        └─ Submit → AnnouncementController@store
              └─ Langsung tampil di dashboard semua user yang aktif
```

---

## 4. User Flows

### F-U1: Browse Katalog
```
/catalog → grid series (cover, judul, status badge)
  └─ Filter: status, tipe, search judul
        └─ Klik series → /catalog/{slug}
              └─ Halaman detail:
                    ├─ Info series (cover besar, sinopsis, score, status)
                    ├─ Daftar volume (grid cover kecil, nomor volume)
                    └─ Tombol "Tambah ke Koleksiku" → F-U2
```

### F-U2: Tambah Series ke Koleksi
```
Cara 1 — dari katalog:
  /catalog/{slug} → klik "Tambah ke Koleksiku"
    └─ POST CollectionController@store { series_ids: [id] }
          └─ Toast sukses / info jika sudah ada

Cara 2 — dari halaman koleksi:
  /my-collection → klik "Tambah Series"
    └─ Dialog: search series (debounce 350ms ke /catalog/search)
          └─ Multi-select series (grid cover, checkmark overlay)
                └─ Submit → CollectionController@store { series_ids: [...] }
                      └─ Toast sukses + halaman refresh
```

### F-U3: Catat Volume yang Dimiliki
```
/my-collection/{id} → halaman detail koleksi
  └─ Grid volume yang sudah dicatat (volume_number, format badge, status loan)
  └─ Tombol "Tambah Volume"
        └─ Dialog: input CSV nomor volume (misal: "1,2,3,5") + pilih format
              └─ Submit → CollectionController@storeVolumes
                    └─ Toast: "N volume ditambahkan, M dilewati (sudah ada)"
  └─ Hapus volume → konfirmasi dialog → CollectionController@destroyVolume
```

### F-U4: Catat Peminjaman
```
/my-collection/{id} → klik volume card yang belum dipinjam → "Pinjamkan"
  └─ Modal: nama peminjam, tanggal pinjam, tanggal kembali (opsional), catatan
        └─ Submit → LoanController@store { collection_volume_id, ... }
              └─ Volume card berubah status "Dipinjam"
              └─ Muncul di /my-loans
```

### F-U5: Tandai Dikembalikan
```
/my-loans → cari loan yang aktif
  └─ Klik "Tandai Dikembalikan"
        └─ Konfirmasi → LoanController@markReturned
              └─ returned_at diisi → status berubah jadi "Dikembalikan"
```

### F-U6: Dismiss Pengumuman
```
Dashboard → announcement banner muncul
  └─ Klik ✕ → POST /announcements/{id}/dismiss
        └─ Banner hilang, tidak muncul lagi untuk user ini
```

---

## 5. Maintenance Mode Flow

```
Admin set menu "Katalog" ke is_maintenance = true
  └─ User yang akses /catalog:
        └─ CheckMenuAccess → is_maintenance = true, user bukan admin
              └─ render Maintenance.tsx
                    ├─ Ikon maintenance
                    ├─ Pesan: "Katalog sedang dalam pemeliharaan. Coba lagi nanti."
                    │   (atau pesan custom dari maintenance_message di tabel menus)
                    └─ Tombol "Kembali ke Dashboard"

Admin yang akses /catalog:
  └─ CheckMenuAccess → is_maintenance = true, TAPI user adalah admin
        └─ Lanjut normal ke halaman
        └─ Ada badge/banner kecil "Mode Maintenance Aktif" di top bar sebagai reminder
```

---

## 6. Error States

| Kondisi | Tampilan |
|---------|---------|
| 403 Forbidden | Halaman error: "Kamu tidak punya akses ke halaman ini" |
| 404 Not Found | Halaman error: "Halaman tidak ditemukan" |
| Maintenance | Halaman maintenance dengan pesan custom |
| User di-ban | Halaman: "Akunmu dinonaktifkan. Hubungi admin." |
| Jikan API error | Toast error + retry button (tidak crash halaman) |
| Upload gagal | Error inline di field upload |
