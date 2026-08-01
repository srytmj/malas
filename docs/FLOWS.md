# FLOWS — MALAS v2

**Versi:** 2.2
**Tanggal:** 2026-06-26, diperbarui 2026-08-01

---

## 1. Navigation Map

### Admin Sidebar
```
Dashboard             /admin/dashboard      (stat cards + chart)
├── Katalog
│   ├── Series        /admin/series         (bulk delete, hover card, context menu, ⌘K)
│   └── Volume        (nested di halaman detail series, bukan route sendiri)
├── Koleksi           /admin/collections    (per user, drill-down)
├── Peminjaman        /admin/loans
├── Pengguna          /admin/users
├── Tiket             /admin/tickets
├── Kuota AI Funfact  /admin/funfact-quota  (kuota generate-ulang Selera Genre per user)
├── Log Aktivitas     /admin/activity-logs
├── Sistem
│   ├── Menu          /admin/menus          (drag-drop reorder, preview /admin/menus/user)
│   └── Pengumuman    /admin/announcements
├── AniList Search    /admin/anilist
├── RanobeDB Search   /admin/ranobedb       (light novel, paralel dengan AniList)
├── Search Gabungan   /admin/search-external (AniList + RanobeDB sekaligus)
└── Pengaturan        /admin/settings       (tab Storage/Database/Konten/AI, super_admin only)
```

Catatan: tidak ada `/admin/roles` terpisah. Role management bagian dari halaman detail user (`/admin/users/{id}`). Command Palette (⌘K/Ctrl+K) tersedia di semua halaman admin lewat tombol di sidebar. Tombol ganti bahasa (id/en/ja) ada di sidebar footer, di sebelah toggle dark/light mode.

### User Sidebar
```
Dashboard             /dashboard            (stat cards, chart, Carousel rekomendasi, Selera Genre)
Katalog               /catalog              (browse series, read-only, avatar kolektor)
Koleksiku             /my-collection        (koleksi milik sendiri, grid poster/table)
Wishlist              /wishlist             (series yang ingin dibaca, belum dikoleksi)
Pinjaman Saya         /my-loans
Direktori             /directory            (cari & follow user lain dengan profil publik)
Tiket                 /tickets
```

Global Search (⌘K/Ctrl+K, atau search bar di header desktop / icon di mobile) tersedia di semua halaman user — cari judul di Katalog/Koleksiku atau navigasi cepat. Tombol ganti bahasa (id/en/ja) ada di sidebar footer, di sebelah toggle dark/light mode.

Profil publik (`/u/{username-atau-id}`) **tidak ada di sidebar** — diakses lewat Direktori, link share, atau langsung dari URL. Bisa diakses tanpa login (lihat §2 Guest Flow).

---

## 2. Auth Flows

### Login (SSO whitearchive.id)
```
GET /auth/redirect
  └─ Redirect ke whitearchive.id dengan PKCE code_challenge
        └─ User login di whitearchive.id (di luar MALAS)
              └─ Redirect balik ke GET /auth/callback
                    ├─ Tukar code → token, ambil klaim user (sso_id, name, username, email, avatar)
                    ├─ User baru → dibuat otomatis (role default `user`)
                    ├─ User lama → data di-update dari klaim SSO terbaru
                    └─ Session dibuat → redirect berdasarkan role:
                          ├─ admin / super_admin → /admin/dashboard
                          └─ user → /dashboard
```

Tidak ada form register/login lokal — semua akun (termasuk admin) dikelola lewat SSO.

### Guest Access — Profil Publik
```
GET /u/{username-atau-id}  (di luar grup middleware 'auth', jadi diakses tanpa login)
  └─ ProfileController@show
        ├─ Viewer null (guest) → is_guest = true, is_owner = false, is_following = false
        └─ Viewer login → is_owner cek $viewer->id === $user->id, is_following cek relasi Follow

Frontend (Profile/Show.tsx):
  ├─ is_guest = true  → render PublicShell (header minimal "MALAS" + tombol Login, tanpa sidebar)
  │                      Grid koleksi user: cover + judul saja, TIDAK bisa diklik ke /catalog/{slug}
  │                      (karena katalog butuh login). Tombol Follow diganti link "Login untuk follow".
  └─ is_guest = false → render UserLayout normal, koleksi bisa diklik, tombol Follow aktif
```

Route `follow`/`unfollow`/`/directory` tetap di dalam grup middleware `auth` — guest bisa lihat profil tapi tidak bisa follow atau akses direktori tanpa login.

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

### F-A2: Import Series dari AniList
```
/admin/anilist → ketik judul di search
  └─ Debounce → GET /admin/anilist/search?q=...
        ├─ AniList timeout/error → tampil pesan error
        └─ Hasil muncul sebagai card overlay (cover, judul, tahun, status, badge 18+ jika applicable)
              └─ Klik "Import"
                    ├─ AniList ID sudah ada → toast info "Sudah ada di katalog" + tombol lihat
                    └─ Belum ada → import (genres/authors/themes/demographics ikut tersimpan)
                          tetap di halaman search, tidak pindah halaman
```

Sync ulang metadata AniList ke series yang sudah ada tersedia dari Popover "Sync AniList" di halaman Edit Series.

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

### F-A7: Import Series dari RanobeDB (Light Novel)
```
/admin/ranobedb → ketik judul di search
  └─ Debounce → GET /admin/ranobedb/search?q=...
        ├─ RanobeDB timeout/error → tampil pesan error
        └─ Hasil muncul sebagai card overlay (cover, judul, tahun)
              └─ Klik hasil → GET /admin/ranobedb/detail (preview lengkap: authors, illustrators
              │                terpisah, genres/themes/demographics dari tags[].ttype)
                    └─ Klik "Import"
                          ├─ ranobedb_id sudah ada → toast info "Sudah ada di katalog" + tombol lihat
                          └─ Belum ada → import (authors/illustrators/genres/themes/demographics
                                tersimpan terpisah; tanggal sentinel 99999999 di-treat sebagai "ongoing")
                                tetap di halaman search, tidak pindah halaman

Halaman /admin/search-external menggabungkan hasil AniList + RanobeDB dalam satu pencarian.
Sync ulang metadata RanobeDB ke series yang sudah ada tersedia dari Popover "Sync RanobeDB" di
halaman Edit Series (sama pola dengan "Sync AniList").
```

### F-A8: Reorder Menu Sidebar (Drag & Drop)
```
/admin/menus → drag salah satu baris menu (handle drag, @dnd-kit)
  └─ Drop di posisi baru
        └─ PATCH /admin/menus/reorder { ordered_ids: [...] }
              └─ menus.sort_order di-update sesuai urutan baru
                    └─ Sidebar user (dan admin) langsung ikut urutan baru di request berikutnya
  └─ Tombol "Preview Sidebar User" → /admin/menus/user
        └─ Render ulang sidebar persis seperti yang dilihat user, tanpa harus login sebagai user biasa
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

### F-U7: Tandai Volume Sudah Dibaca
```
/my-collection/{id} → klik icon mata di volume card/baris
  └─ PATCH CollectionController@toggleVolumeRead
        └─ read_at diisi (atau di-null-kan jika toggle lagi) → volume greyed out
              └─ Toast sukses dengan tombol "Undo" (panggil endpoint yang sama untuk revert)
              └─ Indikator "Terakhir dibaca: Vol. N" di header ikut update

Tandai semua sekaligus:
  └─ Klik icon mata di sebelah kiri judul "Volume yang Dimiliki"
        └─ PATCH CollectionController@markAllVolumesRead
              └─ Semua volume yang belum dibaca → read_at = now()
                    └─ Toast + tombol "Undo" → PATCH unmarkVolumesRead
                          (hanya revert volume yang baru diubah aksi ini)
```

### F-U8: Mode Hapus Volume
```
/my-collection/{id} → klik tombol "Hapus" di toolbar volume
  └─ Masuk mode seleksi — icon mata di tiap volume berubah jadi checkbox
        └─ Pilih volume → tombol "Hapus (N)" muncul
              └─ Konfirmasi → CollectionController@destroyVolumes (bulk)
        └─ Klik "Selesai" → keluar mode seleksi, checkbox kembali jadi icon mata
```

### F-U9: Review & Rating Pribadi
```
/my-collection/{id} → card "Review & Rating Pribadi"
  └─ Geser slider (-10 s/d +10) + isi komentar
        └─ Klik "Simpan Review" → PATCH CollectionController@updateReview
              └─ Toast sukses, nilai tersimpan (personal_rating, personal_review)
```

### F-U10: Global Search
```
Klik search bar di header (atau ⌘K / Ctrl+K dari halaman manapun)
  └─ Dialog Command terbuka → ketik query
        ├─ < 2 karakter → hanya tampil navigasi statis (fuzzy-match dari cmdk)
        └─ ≥ 2 karakter → debounce 300ms → GET /search?q=...
              └─ Hasil dikelompokkan: Navigasi, Koleksiku, Katalog
                    └─ Klik salah satu → router.visit ke halaman terkait, dialog tertutup
```

### F-U11: Wishlist
```
/catalog/{slug} atau /catalog → klik "Tambah ke Wishlist"
  └─ POST WishlistController@store { series_id }
        └─ Toast sukses / info jika sudah ada di wishlist

/wishlist → grid series yang di-wishlist
  └─ Klik "Hapus" → WishlistController@destroy
        └─ Toast + tombol "Undo" → PATCH WishlistController@restore
  └─ Klik "Tambah ke Koleksiku" langsung dari card wishlist → sama alur dengan F-U2
```

### F-U12: Profil Publik & Follow
```
Opt-in tampilkan profil (di Settings):
/settings → toggle "Profil Publik"
  └─ PATCH SettingsController@updateProfileVisibility
        └─ is_profile_public tersimpan → profil bisa diakses di /u/{username-atau-id}

Follow user lain:
/directory (butuh login) atau /u/{user} → klik "Follow"
  └─ POST ProfileController@follow { user }
        └─ Tombol berubah "Unfollow", follower_count bertambah
  └─ Klik "Unfollow" → DELETE ProfileController@unfollow

Lihat profil orang lain (guest atau login) → lihat §2 Guest Access — Profil Publik untuk
perbedaan tampilan guest vs user login.
```

### F-U13: Selera Genre (AI Funfact)
```
Dashboard → card "Selera Genre"
  ├─ Word cloud genre dihitung dari distribusi genre koleksi user
  └─ Funfact text:
        ├─ Provider = puter (default)
        │     └─ Auto-generate saat halaman load pertama kali / koleksi bertambah signifikan:
        │           needs_auto_generate = true dari backend → frontend panggil window.puter.ai.chat()
        │                 └─ Hasil dikirim balik → POST dashboard.funfact.auto-save → tersimpan
        │     └─ Klik "Generate Ulang" (manual, dibatasi kuota mingguan)
        │           └─ Sama alur client-side → POST dashboard.funfact.regenerate
        │                 ├─ Kuota habis → toast info, tombol disabled sampai window reset
        │                 └─ Berhasil → funfact baru tersimpan & tampil
        │     └─ Client-side generate gagal → POST dashboard.funfact.report-error (log ke ActivityLog)
        └─ Provider = gemini/openai/claude (dikonfigurasi admin)
              └─ Generate terjadi server-side di AiFunfactService
                    ├─ HTTP 429 dari provider → AiRateLimitException → fallback text, kuota TIDAK terpotong
                    └─ Sukses → funfact tersimpan seperti biasa
```

### F-U14: Ganti Bahasa
```
Klik tombol bahasa di sidebar footer (sebelah toggle dark/light mode) — atau kartu "Bahasa" di Settings
  └─ Popover: pilih id / en / ja
        └─ i18n.changeLanguage(value) — UI berubah instan tanpa reload
        └─ PATCH settings.locale.update { locale } → users.locale tersimpan
              └─ Request berikutnya, App::setLocale() server-side ikut bahasa baru
                    (pesan validasi, paginator Laravel bawaan ikut berubah juga)
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
| AniList/RanobeDB API error | Toast/pesan error (tidak crash halaman) |
| Upload gagal | Error inline di field upload |
| AI Funfact rate limit (429) | Fallback ke teks default, kuota generate-ulang manual tidak terpotong |
| AI Funfact generate gagal (client-side) | Toast error + dilaporkan ke ActivityLog (kategori `ai`), tidak crash dashboard |
