# Changelog

Semua perubahan penting pada Malas dicatat di file ini. Format mengikuti prinsip [Keep a Changelog](https://keepachangelog.com/), disederhanakan untuk histori internal (bukan rilis versi berpenomor).

---

## 2026-08-15 (lanjutan 5) — Export/Import Koleksi (Backup Pribadi)

- Fitur baru atas request user: user bisa export seluruh koleksi (+ progres baca, review, rating) ke file JSON dan import balik. Tombol "Export"/"Import" di header `Collection/Index.tsx`.
- **Export** (`CollectionController::export()`) — `response()->streamDownload()` (pola sama `DatabaseBackupController`), filename `malas-koleksi-{username}-{tanggal}.json`. Isi tiap entry: identifier series (`anilist_id`, `ranobedb_id`, `mal_id`, `slug` — dikirim semua sekaligus biar matching pas import tetap robust walau salah satu berubah/kosong), condition, acquired_at, notes, personal_rating, personal_review, dan full volume list (nomor, format, ebook_source, language, read_at).
- **Import** (`CollectionController::import()`) — upload file JSON, validasi struktur (`version`+`collections` array) sebelum disentuh, wrapped `DB::beginTransaction()`. Matching series berurutan: `anilist_id` → `ranobedb_id` → `mal_id` → `slug`, dicari di katalog **lokal** (bukan fetch AniList otomatis — keputusan sengaja, biar nggak duplikasi logic `AniListController::import()`; kalau series belum ada di katalog lokal, entry itu di-skip dan dihitung terpisah). Series yang udah ada di koleksi user juga di-skip (**append-only**, sesuai konfirmasi user — bukan overwrite). Semua field volume (`format`/`ebook_source`/`language`) dan `condition` di-whitelist lewat `sanitizeEnum()` sebelum insert — file upload user nggak dipercaya mentah-mentah. Hasil dilaporkan lewat flash: "X diimpor, Y dilewati (sudah dimiliki atau tidak ada di katalog)".
- Diverifikasi end-to-end lewat HTTP request langsung (curl, bukan browser — ada gangguan tool browser pane sesi ini): export user A dengan 2 koleksi (termasuk rating/review/2 volume) → JSON lengkap dan akurat; import ke user B yang udah punya 1 dari 2 series itu → cuma 1 series baru yang ke-import, yang lama nggak ke-duplikat/ke-timpa; entry dengan series yang nggak ada di katalog lokal → di-skip graceful, nggak crash; file JSON invalid/rusak → ditolak dengan pesan error, bukan 500.

---

## 2026-08-15 (lanjutan 4) — Nonaktifkan Sementara Generate Funfact AI

- Atas permintaan user, fitur generate funfact AI (auto-generate + tombol "Generate Ulang" + teks funfact) dinonaktifkan sementara lewat konstanta `DashboardController::FUNFACT_GENERATION_ENABLED = false`. Word cloud genre (bagian non-AI dari kartu "Selera Genre" yang sama) **tetap** tampil normal — cuma bagian AI-nya yang di-skip. `regenerateFunfact()` juga dikasih guard 404 defensif kalau ada request nyasar ke endpoint itu selagi dinonaktifkan. Nggak ada penghapusan kode/data — tinggal balikin konstanta ke `true` buat ngaktifin ulang.

---

## 2026-08-15 (lanjutan 3) — Fix Layout Modal, URL Koleksi Pakai Judul, Konfirmasi+Undo Hapus dari Grup, Hapus Puter.js

- **Fix layout modal "Tambah Manga"**: filter tipe ketutupan dan modal ikut nge-scroll — root cause div list item (`min-h-[360px] flex-1 overflow-y-auto`) punya floor tinggi 360px yang bisa lebih besar dari sisa ruang yang dialokasikan flexbox (`max-h-[85vh]` dikurangi tinggi header/search/filter/footer), jadi begitu grid item cukup panjang, seluruh `DialogContent` kepaksa lebih tinggi dari `max-h-[85vh]` alih-alih cuma div itu doang yang scroll — filter ke-dorong keluar viewport. Fix: `min-h-[360px]` → `min-h-0` (biar flex item bisa nyusut penuh sesuai sisa ruang), plus `shrink-0` eksplisit di header/search/filter/footer biar nggak ikut kekompres.
- **URL `/my-collection` pakai judul series, bukan UUID** — request user "url my collection masih pake random string, bukan judul koleksinya". `Collection::resolveRouteBinding()` baru: cocokkan lewat slug Series-nya (`whereHas('series', fn ($q) => $q->where('slug', $value))`), **dibatasi ke koleksi milik user yang login** (dua user bisa sama-sama koleksi series yang sama, satu `Series::slug` "dipakai" banyak `Collection`) — fallback ke id kalau nggak ketemu. Nggak nambah kolom `slug` baru sama sekali di tabel `collections`, tinggal reuse `series.slug` yang udah unik global. Enam file frontend + controller yang generate link `collection.show` diupdate: `SearchController`/`GlobalSearch.tsx`, `Catalog/Show.tsx` (reuse `series.slug` yang udah ada di props, nggak perlu query tambahan), `CollectionController::index()`/`Collection/Index.tsx`, `CollectionGroupController::show()`/`CollectionGroups/Show.tsx`, `DashboardController::continueReading()`/`Dashboard.tsx`, `LoanController::index()`/`Loans/Index.tsx`. Endpoint aksi (PATCH/POST/DELETE di belakang `/my-collection/{collection}/...`, bukan navigasi GET) sengaja **tetap** pakai `collection.id` mentah — itu bukan URL yang dibaca/di-bookmark user.
- **Konfirmasi + Undo buat hapus manga dari grup** — sebelumnya tombol X di `CollectionGroups/Show.tsx` langsung `router.delete()` tanpa konfirmasi apa pun, dan toast suksesnya nggak ada tombol Undo (padahal aturan wajib CLAUDE.md: semua aksi reversible wajib ada Undo). Ditambah dialog konfirmasi (pola sama dengan dialog Hapus Grup di file yang sama) sebelum beneran hapus, dan `CollectionGroupController::removeItem()` sekarang kirim `undo_url`/`undo_payload` — route PATCH baru `collection.groups.items.undoRemove` → `undoRemoveItem()` yang re-attach pivot-nya (bukan hard-delete, jadi undo-nya simpel, nggak perlu rekonstruksi payload rumit kayak `CollectionController::undoDestroy()`).
- **Puter.js dihapus total** — request user langsung "kita hapus integrasi puter". Dicopot: `resources/js/lib/puter.ts` (file dihapus), tag `<script src="https://js.puter.com/v2/">` di `app.blade.php`, opsi "Puter" dari dropdown provider AI (`Admin/Settings/Index.tsx`) plus validasi `Rule::in()` di `AiSettingController`, cek `provider === 'puter'` di `AiFunfactService`. Default provider baru: `gemini` (ganti dari `puter`, tapi tetap butuh API key — kalau kosong otomatis fallback ke `AiFunfactService::fallbackText()`, teks statis dari data genre, bukan AI). Endpoint yang cuma dipakai buat flow client-side generate Puter — `dashboard.funfact.auto-save` dan `dashboard.funfact.report-error` (plus method controllernya, `saveAutoGeneratedFunfact()`/`reportFunfactError()`) — jadi dead code total begitu Puter dicopot, ikut dihapus. Migration baru normalisasi baris `ai_settings.provider = 'puter'` lama (kalau ada) jadi `'gemini'`.
- Diverifikasi semua lewat Browser tool: modal filter kelihatan penuh + cuma grid item yang scroll (bukan seluruh dialog), klik card koleksi/grup nge-navigate ke `/my-collection/{judul-series}`, klik hapus dari grup munculin dialog konfirmasi dulu, toast abis hapus ada tombol Undo yang beneran ngembaliin item, tab provider AI admin cuma nampilin Gemini/OpenAI/Claude (Puter nggak ada lagi), tombol "Generate Ulang" funfact tetap jalan normal (gagal graceful lewat jalur server biasa, bukan crash referencing Puter).

---

## 2026-08-15 (lanjutan 2) — Modal "Tambah Manga" Lebih Lebar + Infinite Scroll

- **Modal beneran dilebarin**: request user "bikin modal add manga to group lebih lebar" ternyata nemuin bug lain — `max-w-7xl` yang dipasang di Phase 28 nggak pernah kepakai! `ui/dialog.tsx` punya base style `sm:max-w-sm` bawaan, dan class custom TANPA prefix `sm:` (kayak `max-w-7xl` polos) kalah di cascade Tailwind begitu viewport ≥640px — dialog diam-diam tetap 384px dari awal. Fix: `sm:max-w-7xl` (bukan `max-w-7xl` doang). Diverifikasi langsung lewat `getComputedStyle` di Browser tool — sebelum fix `computedMaxWidth: "384px"`, sesudah fix `"1280px"`.
- **Infinite scroll gantiin `<Pagination>`**: request user eksplisit "infinite scrolling bukan pake paginate". List "koleksi yang bisa ditambahkan" sekarang nge-accumulate hasil tiap halaman ke state lokal (`accumulated`), fetch halaman berikutnya otomatis pas discroll mendekati bawah — bukan klik nomor halaman manual.
  - Percobaan pertama pakai `IntersectionObserver` **gagal kedeteksi** pas verifikasi di Browser tool — root cause: `useEffect` yang bikin observer jalan SEBELUM DOM sentinel-nya ke-mount (Dialog content Base UI mount satu tick setelah `addOpen` jadi `true`), dan dependency array effect nggak pernah retrigger begitu ref-nya kepasang. Percobaan kedua pindah ke ref-callback pattern (observer dibikin pas node sentinel beneran mount) — tapi observer-nya tetep nggak pernah fire sama sekali di lingkungan Browser tool ini (bahkan `observe(document.body)` juga nggak pernah callback), kemungkinan karena tab nggak lagi compositing frame aktif (lihat error "Browser pane is not displayed" pas nyoba screenshot).
  - **Solusi final**: ganti total ke `onScroll` handler biasa (cek `scrollHeight - scrollTop - clientHeight < 300`) — nggak bergantung ke rendering/compositing pipeline, lebih gampang diverifikasi (bisa di-trigger manual lewat `dispatchEvent(new Event('scroll'))`), dan hasilnya beneran jalan: diverifikasi 50 item dummy ke-load 24 → 48 → 50 lewat dua kali scroll, masing-masing ngirim persis satu request (`page=2` lalu `page=3`), nggak ada request duplikat/berlebih setelah semua halaman abis.
- **Bug sejenis kemungkinan ada di tempat lain**: `Admin/Series/Edit.tsx` (`max-w-3xl`) dan `User/Collection/Index.tsx` (`max-w-4xl`) juga pakai pola `max-w-*` tanpa prefix `sm:` — belum diverifikasi/di-fix di sesi ini (di luar scope, di-flag sebagai task terpisah). `Admin/ActivityLog/Index.tsx` udah bener dari awal (`sm:max-w-2xl`).
- `CLAUDE.md` bagian "React Components" ditambah aturan eksplisit soal `sm:` prefix wajib buat override lebar `DialogContent`, biar nggak kejadian lagi.

---

## 2026-08-15 — Fix: Blank Screen Buka Grup Koleksi (Slug Kosong + Guest Crash), README Bebas Emoji

- **Bug #1 (root cause asli)**: user punya grup koleksi ("RomCom") yang dibuat SEBELUM migration slug (Phase 28) — kolom `slug` ditambah `nullable()` biar migration nggak gagal di data lama, tapi lupa di-backfill. Grup itu kesimpen dengan `slug = NULL` selamanya. Begitu frontend (`Index.tsx`/`Show.tsx`) mulai generate semua link pakai `group.slug`, link ke grup ini jadi rusak (`route('collection.groups.show', null)`), navigasi gagal → **blank screen**. Ini penyebab utama laporan user, bukan cuma soal guest.
  - Fix: migration baru `2026_08_15_090000_backfill_collection_groups_slug.php` — backfill semua `collection_groups.slug` yang masih NULL pakai `CollectionGroup::generateUniqueSlug()`, pola identik dengan backfill slug Series (`2026_08_02_090000_add_slug_to_series_table.php`). Diverifikasi langsung: grup "RomCom" dapet slug `sehnauoi-romcom`, halaman Index dan Show dua-duanya ke-render normal setelah backfill (dicek pakai Browser tool langsung, bukan curl — link `href` di Index sekarang beneran ngarah ke slug yang benar, klik masuk ke halaman detail sukses tanpa JS error).
- **Bug #2**: `CollectionGroups/Show.tsx` selalu render lewat `UserLayout`, yang berasumsi ada user login (`AccountSwitcher.tsx` akses `auth.user!` — non-null assertion). Untuk guest (belum login) yang buka grup **publik**, `auth.user` itu `null` lewat `HandleInertiaRequests`, jadi React crash begitu nyoba render sidebar/account switcher → blank screen juga, tapi kasus terpisah dari Bug #1 (cuma kena guest, Bug #1 kena siapa aja termasuk owner login).
  - Fix: tambah prop `is_guest` dari `CollectionGroupController::show()` (`$viewer === null`), lalu di `Show.tsx` pakai `Layout = is_guest ? PublicShell : UserLayout` — pola yang sama persis dengan `Profile/Show.tsx`. Breadcrumb ke `/collection-groups` (route auth-only) juga disembunyikan buat guest. Badge status publik/privat di header sekarang baca `group.is_public` yang sebenarnya, bukan selalu nampilin "Publik" buat non-owner (kena kasus admin liat grup privat orang lain).
- Kedua bug nggak kena verifikasi Phase 27/28 karena verifikasi waktu itu cuma ngecek response JSON Inertia lewat curl (props server benar), bukan render React beneran di browser — jadi masalah slug NULL di data lama & crash client-side lolos.
- **README.md & README.id.md dirombak** — semua emoji dicopot (badge bagian, header section, list fitur, bullet troubleshooting/backlog, tombol "back to top") atas permintaan user ("jangan gunakan emoji berlebih, lebih baik tidak ada sekalian"). Anchor link internal (`#troubleshooting`, dll) disesuaikan karena GitHub generate slug heading beda begitu emoji dicopot.

---

## 2026-08-14 (lanjutan 9) — Grup Koleksi: Modal Diperbesar + Paginasi, Filter Tipe, Slug URL, Publik/Privat

- **Modal "Tambah Manga" diperbesar & dipaginasi** — `max-w-3xl` → `max-w-5xl`, dan daftar koleksi yang bisa ditambahkan sekarang di-paginate server-side (24/halaman, `Pagination.tsx` yang sama dipakai di halaman list lain) supaya user bisa menjelajahi seluruh koleksinya, bukan cuma list pendek yang di-load sekali lalu difilter di client.
- **Filter tipe** (Manga/Light Novel/One Shot/Doujinshi/Manhwa/Manhua, segmented control) ditambahkan di modal ini, reuse `useTypeFilterOptions()` — pola yang sama dengan filter tipe di halaman list lain (lihat aturan wajib di CLAUDE.md).
- Pencarian & filter tipe di modal sekarang **server-side** (debounced `router.get` partial reload `only: ['available']`), bukan filter client-side atas array yang sudah di-load penuh — otomatis exclude manga yang udah ada di grup (`whereNotIn`) di level query, bukan cuma di UI.
- **URL grup pakai slug, bukan UUID** — format `{username}-{nama-grup-slug}` (mis. `/collection-groups/testowner-romcom`), dibikin di `CollectionGroup::generateUniqueSlug()` (pola yang sama dengan `Series::generateUniqueSlug()`), regenerate otomatis kalau nama grup diubah. Fallback ke id kalau username belum ke-sync dari SSO.
- **Opsi Publik/Privat per grup** — `Switch` di halaman detail grup (owner-only). Grup publik muncul di section baru "Grup Koleksi Publik" di profil publik pengguna (`ProfileController::publicGroups()`) dan bisa diakses siapa saja (termasuk guest) lewat URL langsung; grup privat cuma bisa dilihat pemiliknya (dan admin). Route `GET /collection-groups/{group}` dipindah ke luar grup middleware `auth` (sama polanya dengan `/u/{user}` profil publik) — visibilitas dicek manual di controller (`abort_unless($group->is_public || $isOwner || ...)`), bukan lewat middleware.
- `PublicShell` (shell minimal buat halaman publik tanpa login — header + tombol login) diekstrak dari `Profile/Show.tsx` ke komponen bersama `Components/app/PublicShell.tsx`, dipakai juga di `CollectionGroups/Show.tsx` supaya guest yang buka grup publik nggak nabrak `UserLayout` yang butuh sesi login.
- Migration baru: `collection_groups.slug` (unique) + `collection_groups.is_public` (default false).
- **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: query `available` yang di-`join()` ke tabel `series` (buat urutkan berdasarkan judul & filter tipe) bikin `whereNotIn('id', ...)` ambigu — SQLite nggak tahu `id` itu punya tabel `collections` atau `series`, error `ambiguous column name: id`. Fix: qualify jadi `whereNotIn('collections.id', ...)`.
- Live HTTP test lengkap: 30 koleksi dummy → filter tipe (manga vs novel) benar, search substring benar, pagination halaman 2 benar, tambah 2 item → langsung ke-exclude dari `available` (total 30→28), toggle publik → guest tanpa login bisa lihat grup (`is_owner: false`, `available: null`), toggle privat lagi → guest kena 403, rename grup → slug regenerate otomatis, profil publik pemilik grup menampilkan grup publik dengan cover collage. Data uji coba (user, koleksi, series, grup, token) dibersihkan dari database dev setelah verifikasi.

---

## 2026-08-14 (lanjutan 8) — Grup Koleksi Dirombak Total (ala MDList MangaDex)

- **Desain grouping di lanjutan 6 ternyata salah semantik** — user jelasin maksudnya "ala MDList MangaDex": grup itu objek pertama (dikasih nama, mis. "RomCom"), diisi banyak manga dari koleksi user — bukan sekadar label string tunggal per koleksi. Diganti total, bukan di-patch.
- `CollectionGroup` model baru + pivot `collection_group_items` — **many-to-many**, satu manga bisa masuk lebih dari satu grup sekaligus. Kolom `collections.group_name` (desain lama) dihapus.
- Halaman baru: `/collection-groups` (daftar grup, cover collage dari 4 manga pertama) → `/collection-groups/{group}` (isi grup — tambah manga dari koleksi lewat dialog picker, hapus dari grup, ubah nama, hapus grup). Link "Grup Koleksi" ditambahkan di header halaman Koleksiku.
- Semua UI grouping lama di `Collection/Index.tsx` (popover inline, filter dropdown, kolom tabel "Grup") dicopot bersih.
- **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: pivot table `collection_group_items` sempat dikasih `uuid('id')->primary()` kayak tabel lain di app ini — tapi `attach()`/`syncWithoutDetaching()` Eloquent insert baris pivot lewat query builder mentah (bukan lewat model event), jadi `HasUuids` nggak sempat ngisi kolom `id`, bikin `NOT NULL constraint violation` di SQLite. Fix: pivot table murni pakai composite primary key (`collection_group_id`, `collection_id`), bukan `id` sintetis — pola standar Laravel buat pivot table tanpa data tambahan.

---

## 2026-08-14 (lanjutan 7) — Fix: Link Login (CLI/Email) Override Akun Aktif Alih-Alih Nambah

- **Bug ditemukan user**: login lewat link darurat CLI (`sso:emergency-login`) atau lewat magic link email, pas dibuka sementara sudah login sebagai akun lain, malah **meng-override** akun yang lagi aktif alih-alih menambahkannya sebagai akun ke-link (fitur multi-account switching, lanjutan 5). Root cause: keputusan link-atau-replace sebelumnya bergantung ke flag `sso_link_mode` yang cuma di-set kalau login dimulai dari modal "Tambah Akun" di UI — link CLI/email nggak pernah lewat modal itu sama sekali, jadi flag-nya nggak pernah ke-set.
- **Fix**: `AccountLinkService::loginAs()` sekarang nentuin link-atau-replace dari status login **saat link dikonsumsi** (`auth()->check()`), bukan dari flag UI — kalau ada user lain yang lagi aktif pas link diklik, otomatis ditambahkan ke daftar akun ke-link, apa pun sumber link-nya (modal "Tambah Akun", CLI, atau email). Flag `sso_link_mode` dan parameter query `?link=1` yang jadi biang masalah dihapus total (dead code setelah fix ini).
- Diverifikasi lewat HTTP langsung: login sebagai A → konsumsi magic link bare buat B (simulasi CLI/email, tanpa modal "Tambah Akun") → identitas aktif jadi B, **A tetap muncul di daftar akun ke-link** (sebelumnya A akan hilang total). Kasus guest fresh-login (belum ada sesi sebelumnya) tetap bersih, nggak ada akun phantom nyangkut.

---

## 2026-08-14 (lanjutan 6) — Stepper dari Koleksiku, Grouping Koleksi, Flash Message Multi-Bahasa

- Stepper +/- progres baca sekarang juga bisa dipakai langsung dari halaman **Koleksiku** (`/my-collection`), nggak perlu buka detail koleksi dulu — reuse endpoint yang sama persis dengan halaman detail (Phase 23), cuma tambahan UI di grid card & table row.
- Fitur **grouping koleksi** baru — user bisa kasih label bebas ke koleksinya (mis. "Rak Kamar", "Rak Kantor") lewat popover inline, plus filter dropdown "Semua Grup"/"Tanpa Grup"/nama grup. Kolom `collections.group_name` (string bebas, bukan tabel terpisah). **⚠️ Desain ini diganti total di lanjutan 8** — ternyata salah semantik, lihat entri di bawah.
- **Flash message controller akhirnya multi-bahasa** — backlog lama yang sudah lama tercatat di CLAUDE.md. `lang/{id,en,ja}/flash.php` baru, ~70 pemanggilan `->with('success'/'error'/'info', ...)` di 24 controller dikonversi dari hardcode Bahasa Indonesia jadi `__('flash.xxx', [...])`. Diverifikasi langsung lewat HTTP: request yang sama dengan `users.locale` beda-beda menghasilkan pesan flash yang benar di ketiga bahasa.
- **Bug ketemu & diperbaiki saat sweep flash-message**: `VolumeController::generate()` pakai key dinamis (`->with($created > 0 ? 'success' : 'info', $message)`) yang lolos dari grep pencarian literal `with('success'` — ketemu lewat sweep kedua yang nyari sisa kata "berhasil"/"gagal" di luar pemanggilan `__()`.

---

## 2026-08-14 (lanjutan 5) — Multi-Account Switching (Session-Based)

- User (bukan cuma admin) sekarang bisa nyambungin akun lain ("Tambah Akun") dan switch cepat antar akun tanpa login ulang, selama masih di browser yang sama — dipicu dari kasus admin yang nggak bisa akses `/my-collection` pakai akun admin-nya sendiri.
- Sengaja **session-based, tanpa link permanen di DB** — daftar akun ke-link (`linked_account_ids`) cuma hidup di session. Ganti browser/device, harus link ulang dari nol. Alasannya: link permanen cuma soal convenience, bukan keamanan — switch akun tetap selalu wajib bukti login beneran ke akun target lebih dulu.
- Dibuka buat **semua user**, bukan dibatasi admin — nggak ada gap keamanan buat dibuka luas karena validasinya di session, bukan di role.
- `AccountSwitcher.tsx` baru (Popover di avatar sidebar footer, Admin & User Layout) — lihat profil, quick-switch akun, "Tambah Akun" (reuse `LoginMethodDialog`), dan dua opsi logout: "Keluar dari Akun Ini" (pola X/Twitter — auto-switch ke akun ke-link lain kalau ada) vs "Keluar dari Semua Akun".
- `AccountLinkService`, `Auth\AccountController` (`switch`/`logoutCurrent`) baru di backend — validasi ketat: target switch wajib sudah ada di whitelist session, nggak bisa switch ke akun sembarangan.

---

## 2026-08-14 (lanjutan 4) — Quick-Edit Progres Baca & Jumlah Volume di Koleksiku

- Stepper +/- "progres baca" baru di halaman detail koleksi — geser batas volume terbaca satu langkah tanpa harus cari & klik ikon mata volume tertentu. `+` menandai volume-belum-dibaca bernomor terendah jadi sudah dibaca, `-` membalik volume-sudah-dibaca bernomor tertinggi jadi belum dibaca. Ikon mata per-volume tetap ada buat koreksi manual di luar urutan.
- Stepper +/- "jumlah dimiliki" per format (fisik/ebook/online/webtoon) — nambah/hapus volume cepat tanpa buka dialog "Tambah Volume". `+` ambil nomor volume berikutnya yang belum dimiliki sama sekali (nomor dibagi bersama lintas format dalam satu koleksi), `-` hapus volume bernomor tertinggi dari format itu. Stepper cuma muncul buat format yang sudah punya minimal 1 volume.
- Volume yang lagi dipinjamkan dilindungi dari penghapusan lewat stepper — tombol `-` didisable + tooltip kalau volume tertinggi format itu lagi dipinjam.
- **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: implementasi awal query di server diam-diam jatuh ke volume non-loaned berikutnya kalau top volume lagi dipinjam, alih-alih menolak aksinya — kontradiksi sama desain "disable + tooltip, jangan diam-diam ganti target". Fix: query cuma pernah incar top volume asli, tolak (info toast) kalau itu lagi dipinjam.

---

## 2026-08-14 (lanjutan 3) — Filter Genre Multi-Select di Katalog User

- Filter genre di halaman Katalog diganti dari dropdown single-select jadi combobox yang bisa diketik (fuzzy search) dan pilih lebih dari satu genre — `GenreMultiSelect.tsx` baru (Popover + `Command`/cmdk), genre terpilih tampil sebagai badge yang bisa dihapus satu-satu.
- Filter genre sekarang **OR-match**: series lolos kalau punya salah satu genre yang dipilih, bukan wajib semuanya. Diverifikasi: Comedy sendiri 59 hasil, Romance sendiri 43 hasil, gabungan keduanya 67 hasil (union yang benar).
- Item ini adalah miss dari sesi sebelumnya — diminta eksplisit tapi sempat ke-skip; ketemu lagi lewat audit "cek fitur kelewat" (lihat entri di bawah).

---

## 2026-08-14 (lanjutan 2) — Favicon Terpasang, README Dirombak (Bilingual), Audit Fitur Kelewat

- Favicon yang sudah digenerate sebelumnya (`public/images/favicon/`) akhirnya di-wire ke `resources/views/app.blade.php` — sebelumnya nol favicon sama sekali. SVG dengan varian light/dark (`prefers-color-scheme`), PNG fallback, `apple-touch-icon`, dan `site.webmanifest` baru buat PWA/home-screen icon.
- README dirombak total jadi bilingual (🇮🇩 Indonesia / 🇬🇧 English) dengan language-switcher, logo, badge tech stack, dan section baru "Belum Selesai (Backlog)" yang transparan soal gap fitur.
- Audit fitur yang diminta tapi kelewat: grep `TODO`/`FIXME` di kode (nihil), cross-check `CLAUDE.md`/`PHASES.md`/`prd.md`. Ketemu satu miss nyata — filter genre multi-select di Katalog (diminta sesi sebelumnya, belum dikerjakan) — langsung dibangun, lihat entri di atas.

---

## 2026-08-14 (lanjutan) — Fix URL Admin (Slug) & Editor Genre/Tags di Series Edit

- **Fix URL admin masih UUID**: rollout slug di Phase 15 cuma nyentuh sisi user (`catalog.show`) — semua `route('admin.series.show'/'edit', ...)` di 9 halaman admin (Index, Show, EditVolume, Edit, AniList, RanobeDb, Search, Tickets, Command Palette) masih pakai UUID mentah. Backend diupdate kirim `slug`/`series_slug`, semua route call di frontend diganti pakai slug (UUID tetap dipakai di tempat yang butuh — bulk-select, target API delete/update).
- **Editor genre/tags di Series Edit**: halaman ini sebelumnya sama sekali nggak punya UI buat genre/authors/illustrators/themes/demographics — `TagListInput.tsx` baru (editor tag bebas, Enter/koma nambah, klik-X hapus), 5 field baru di form. Popover "Sync AniList/RanobeDB" sekarang ikut ngisi tag (sebelumnya cuma judul/sinopsis/dll, walau data genre-nya sudah di-fetch dari API).
- **Bug ketemu & diperbaiki saat verifikasi HTTP langsung**: middleware global Laravel 12 `ConvertEmptyStringsToNull` ngubah sentinel string-kosong (dipakai buat sinyal "hapus semua tag") jadi `null` sebelum validasi jalan — rule `genres.*` yang nggak punya `nullable` nolak `null` dengan 422. Artinya fitur "hapus semua tag" gagal total kalau sungguhan dicoba. Fix: tambah `nullable` ke rule tag, filter sentinel di controller diubah buang `null` juga.

---

## 2026-08-14 — Tema Light/Dark/System

- Toggle dark/light satu-klik di sidebar footer (Admin/User Layout) dan Landing page diganti jadi 3 opsi eksplisit (Light/Dark/System) — pola sama persis dengan `LanguageSwitcher` yang sudah ada: `ThemeSwitcher.tsx` (Popover), sync ke DB per-user (`users.theme`, `PATCH /settings/theme`), guest-safe (localStorage-only kalau belum login).
- `useTheme()` ditulis ulang — resolve `system` lewat `matchMedia`, live-update kalau preferensi OS berubah selagi app terbuka.
- Kartu "Tema" baru di halaman Settings, antara kartu "Bahasa" dan "Profil Publik".

---

## 2026-08-03 (lanjutan 3) — Fix: Tombol Login di Halaman Profil Publik

- Tombol Login di header `PublicShell` dan CTA "Login untuk follow" (keduanya di `Profile/Show.tsx`, dilihat guest yang buka profil publik) masih redirect langsung ke SSO — ketinggalan dari rollout modal login karena rollout awal cuma nyentuh Landing page.
- Diperbaiki: kedua tempat sekarang buka `LoginMethodDialog` juga, masing-masing dengan state lokal sendiri (`loginOpen` di `PublicShell`, `followLoginOpen` di komponen utama).
- Grep ulang `sso.redirect` di seluruh `resources/js` mastiin tidak ada tombol Login lain yang ketinggalan.

---

## 2026-08-03 (lanjutan 2) — Modal Pilihan Login (SSO / Email)

- Landing page sekarang munculin modal pilihan cara login begitu tombol "Login" diklik — "Login dengan whitearchive.id" atau "Login dengan Email" (`LoginMethodDialog.tsx`), bukan langsung redirect ke SSO.
- Login lewat Email dipromosikan dari link kecil "SSO nggak bisa diakses?" (fallback darurat tersembunyi) jadi opsi setara SSO di modal — mekanisme backend-nya sama persis (magic link sekali-pakai, `SsoFallbackController`), cuma framing UI-nya berubah.
- Rate limit endpoint `POST /auth/fallback` dinaikkan dari `throttle:3,15` ke `throttle:5,10` — sekarang dipakai sebagai opsi harian, bukan cuma darurat, jadi limitnya perlu lebih longgar.
- **Catatan penting**: profil (nama/avatar/username) cuma ikut ke-sync ulang dari whitearchive.id pas login lewat SSO. User yang seterusnya login lewat email nggak dapat update profil otomatis — ini keputusan sadar, bukan bug (didiskusikan & disetujui sebelum implementasi).

---

## 2026-08-03 (lanjutan) — Batch Import AniList, Fix Logout SSO Down, Context Menu Tab Baru

### Batch Import AniList (genre, tahun, popularitas)

- Filter genre (dropdown enum kanonis AniList), tahun rilis, dan toggle "Urutkan Popularitas" ditambahkan ke halaman `Admin/AniList/Index.tsx` — boleh browse cuma dari filter tanpa ketik judul apa pun.
- Checkbox multi-select per hasil (cuma yang belum ada di katalog) + "Pilih Semua" + import sekaligus. `AniListService::getMangaBatch()` ambil sampai 50 series dalam **satu** request GraphQL (`media(id_in: [...])`), bukan N request terpisah per series — penting buat hemat kuota rate-limit AniList (~90 req/menit).
- Toggle "Sembunyikan yang sudah ada di katalog" — filter client-side, dikombinasikan dengan badge "Sudah diimpor" yang sudah ada sebelumnya.
- **Ketemu saat riset (diverifikasi langsung ke API sebelum coding)**: `seasonYear` di skema AniList adalah konsep musim tayang anime, selalu balikin array kosong untuk `type: MANGA`. Filter tahun untuk manga yang benar pakai rentang `startDate_greater`/`startDate_lesser` (`FuzzyDateInt`, format `YYYYMMDD`).

### Fixed

- `SsoController::logout()` selalu memaksa browser navigasi ke domain SSO (`whitearchive.id/logout`) buat destroy sesi di sana juga — kalau SSO down, browser nge-hang lama nunggu koneksi ke domain yang mati, padahal sesi lokal sebenarnya sudah invalid duluan. Ditambah `ssoReachable()`, pengecekan cepat (timeout 3 detik) sebelum redirect — kalau tidak bisa dihubungi, langsung balik ke halaman utama tanpa nunggu. Diverifikasi lewat HTTP request langsung: logout sekarang selesai dalam ~3.4 detik yang dibatasi timeout, bukan berpotensi hang tanpa batas menunggu browser sendiri yang menyerah.

### Added

- Context menu (klik kanan) di `Admin/Series/Index.tsx` sekarang punya opsi "Buka di Tab Baru" (`window.open()`) di samping navigasi SPA biasa.

---

## 2026-08-03 — Login Tanpa SSO (Fallback), Konfigurasi Email (Resend), Landing Page Dirombak

### Login Tanpa SSO (Fallback)

Jalan darurat kalau whitearchive.id (SSO) benar-benar tidak bisa diakses (down/migrasi/maintenance) — bukan pengganti SSO, cuma buat kondisi darurat. Sengaja **tidak** pakai password lokal (tidak ada user yang punya password tersimpan, semua dikelola SSO) atau sistem approval admin (dibahas lalu disederhanakan) — dipilih magic link sekali-pakai lewat email yang sudah tersinkron dari SSO, verifikasi identitas lewat kepemilikan inbox.

#### Added
- Link "SSO nggak bisa diakses?" di Landing page → `/auth/fallback` — form isi email → magic link sekali-pakai dikirim (kalau email terdaftar & mail service terkonfigurasi) → klik link → langsung login.
- Tabel `sso_fallback_tokens` — token disimpan **ter-hash** (SHA-256), TTL 15 menit, single-use (sama pola dengan `password_reset_tokens` bawaan Laravel).
- Rate limit `throttle:3,15` di endpoint request, dan response **selalu pesan generik yang sama** baik email terdaftar/tidak/banned — anti email-enumeration.
- Kegagalan kirim email (API key salah, provider down) ditangkap try/catch — tidak bikin request 500, dicatat ke Log Aktivitas + log Laravel biasa.

#### Fixed
- `ActivityLog::record()` selalu pakai `auth()->id()` buat `user_id` (kolom NOT NULL) — meledak (SQL constraint violation → 500) untuk aksi yang dipicu guest seperti request login tanpa SSO. Ditambah fallback ke ID user subject kalau tidak ada yang login. Ketemu & diperbaiki saat verifikasi end-to-end lewat HTTP request langsung, bukan cuma tinker.
- `SsoController::logout()` selalu maksa browser navigasi ke domain SSO (`whitearchive.id/logout`) buat destroy sesi SSO juga — kalau SSO down, browser nge-hang lama nunggu koneksi ke domain yang mati (session lokal sebenarnya sudah keburu invalid duluan). Ditambah pengecekan cepat (`ssoReachable()`, timeout 3 detik) sebelum redirect — kalau SSO tidak bisa dihubungi, langsung balik ke halaman utama tanpa nunggu.

### Login Darurat via CLI

- `php artisan sso:emergency-login {identifier=super_admin}` — reuse `SsoFallbackToken` yang sama dengan magic link email, tapi diterbitkan dari CLI (butuh akses SSH ke server). Identifier boleh role (`super_admin`/`admin`/`user`) atau email/username spesifik. Selalu minta konfirmasi dan tampilkan siapa yang bakal dikasih akses sebelum menerbitkan link — kalau ada beberapa user dengan role yang sama, command kasih pilihan interaktif, bukan asal ambil satu.

### Konfigurasi Email (Resend)
- Tabel `mail_settings` (single-row, `api_key` ter-encrypt) + tab baru "Email" di `/admin/settings` — pola sama dengan Storage/AI (dikonfigurasi UI admin, bukan `.env`).
- `MailSettingsService` set config Resend secara runtime dari DB sebelum kirim, pola sama dengan `StorageSettingsService::disk()`.
- Package `resend/resend-php` (native Laravel `resend` mail transport, `config/mail.php` sudah punya stub bawaan Laravel 12).

### Landing Page Dirombak
- Tambah header (brand Malas, ganti bahasa, toggle dark/light mode, tombol Login) dan footer (tagline, copyright dinamis per tahun) — sebelumnya cuma hero + grid fitur tanpa navigasi.
- `LanguageSwitcher` dibikin guest-safe — kalau belum login, ganti bahasa cukup di client (`i18n.changeLanguage()`) tanpa panggil endpoint yang butuh sesi otentikasi.

---

## 2026-08-02 — URL Katalog Berbasis Judul (Slug), Multi-Bahasa Admin Selesai

### URL Katalog Berbasis Judul

- Kolom `series.slug` baru — auto-generated dari `title_romaji` lewat `Series::generateUniqueSlug()`. Simbol seperti `@`, `!`, koma, titik dua dibuang langsung (bukan dikonversi jadi kata — dictionary default `Str::slug()` sengaja dikosongkan), full judul dipakai apa adanya walau panjang. Regenerate otomatis kalau judul diubah; tambah suffix `-2`/`-3` kalau ada judul kembar.
- `/catalog/{series}` (dan semua route lain yang bind model `Series`) sekarang menerima slug — `Series::resolveRouteBinding()` coba cocokkan slug dulu, fallback ke `id` supaya link/bookmark lama yang masih pakai UUID tetap jalan.
- Semua halaman yang link ke katalog (Catalog, Dashboard, Koleksiku, Wishlist, Tiket, Profil Publik, Global Search) diupdate untuk pakai slug, bukan UUID, saat membangun URL.
- Migration baru membackfill slug untuk semua series yang sudah ada di database.

### Multi-Bahasa (id/en/ja) — Admin Selesai

Seluruh backlog halaman `Admin/**` (~25 halaman: Series, Settings, Users, Announcements, Tickets, Loans, Menus, GenreFunfacts, AniList, RanobeDb, Search, Collections), halaman root (`Landing`, `Error`, `Auth/Banned`, `Maintenance`), dan komponen `Components/app/**` yang tersisa sudah diterjemahkan penuh. Lihat [`CLAUDE.md`](CLAUDE.md) bagian "Sistem Multi-Bahasa" untuk detail cakupan terkini — sisa gap yang terdokumentasi cuma flash message controller (backlog terpisah) dan `Pages/Dashboard.tsx` root yang ternyata dead code (tidak direferensikan controller manapun, sengaja tidak disentuh).

---

## 2026-08-01 — Multi-Bahasa (id/en/ja), Profil Publik untuk Guest, Puter.js AI

### Multi-Bahasa (id/en/ja)

Fondasi sistem multi-bahasa ditambahkan dan sebagian besar halaman user-facing sudah diterjemahkan penuh — lihat entri 2026-08-02 di atas untuk penyelesaian sisi admin.

#### Added
- `react-i18next` + resource JSON per-namespace di `resources/js/lang/{id,en,ja}/` (`common.json`, `dashboard.json`, `user.json`, `catalog.json`, `collection.json`, `admin.json`).
- Kolom `users.locale` (default `id`) + card "Bahasa" di halaman Settings, **plus tombol quick-switch bahasa** (`LanguageSwitcher.tsx`) langsung di sidebar footer Admin & User Layout — ganti bahasa tanpa perlu buka Settings.
- Middleware `SetLocale` — set `App::setLocale()` server-side dari preferensi user tiap request, di-share ke frontend lewat `HandleInertiaRequests`.
- `lang/{id,en,ja}/validation.php` + `lang/{id,en,ja}/pagination.php` — pesan validasi & paginator Laravel bawaan sekarang otomatis ikut bahasa aktif user (sebelumnya selalu Inggris karena `lang/` belum pernah di-publish).
- `menuTranslationKey()` (`resources/js/lib/menu.ts`) — memetakan `key` menu dari database ke translation key, supaya label sidebar ikut berganti bahasa; menu custom hasil rename admin tetap fallback ke label DB apa adanya.
- `useTypeFilterOptions()` (`resources/js/lib/typeFilters.ts`) — satu sumber terjemahan untuk Segmented Control filter tipe yang berulang di banyak halaman (aturan wajib CLAUDE.md).

#### Diterjemahkan penuh
Layouts & shared (`AdminLayout`, `UserLayout`, `SidebarNav`, `CommandPalette`, `GlobalSearch`, `StatusBadge`, `Pagination`, `LanguageSwitcher`); semua halaman `User/**` (Dashboard, Catalog, Collection — termasuk `Collection/Show.tsx` yang ~1200 baris, Wishlist, Tickets, Loans, Directory, Profile); `Settings/Index.tsx` penuh; `Admin/Dashboard.tsx` dan `Admin/ActivityLog/Index.tsx`.

#### Belum diterjemahkan (backlog)
Sisa halaman `Admin/**` (Series, Users, Announcements, Tickets, Menus, dll — lihat CLAUDE.md untuk daftar lengkap per file dengan estimasi ukuran), beberapa komponen kecil `Components/app/**`, halaman root (`Landing`, `Error`, dll), serta flash message dari controller (`->with('success', '...')`) yang masih hardcode Bahasa Indonesia.

### Profil Publik Bisa Diakses Non-Login

- Route `/u/{user}` (`profile.show`) dipindah keluar dari grup middleware `auth` — sekarang guest (belum login) bisa lihat profil publik seseorang tanpa perlu SSO login dulu.
- `ProfileController::show()` dibikin null-safe untuk viewer tamu (`is_owner`/`is_following` tidak lagi crash), plus flag baru `is_guest` dikirim ke frontend.
- Guest dapat layout publik minimal (`PublicShell` — cuma header Malas + tombol Login, tanpa sidebar), dan kartu koleksi di grid **tidak bisa diklik** untuk guest (karena `catalog.show` tetap butuh login) — cuma bisa lihat cover & judul.

### Provider AI Gratis via Puter.js

- Fitur "Selera Genre" (funfact AI) sekarang defaultnya pakai [Puter.js](https://docs.puter.com/) — jalan langsung dari browser user, gratis, tanpa API key. Admin tetap bisa switch ke Gemini/OpenAI/Claude di `Admin/Settings` (tab AI) kalau mau.
- Auto-generate & generate manual funfact tetap jalan otomatis di background untuk provider Puter (lihat `lib/puter.ts` + `DashboardController::regenerateFunfact()`/`saveAutoGeneratedFunfact()`).
- Penanganan HTTP 429 (rate limit) khusus untuk provider Gemini/OpenAI/Claude — `AiRateLimitException` bikin funfact jatuh ke fallback text alih-alih gagal total, dan tidak memotong kuota generate ulang manual user.

---

## 2026-07-26 — Koleksi Pribadi: Baca Tracking, Review & Rating, Undo

Iterasi lanjutan dari batch UI library — fokus ke fitur baca-tracking per volume, review pribadi, dan perbaikan UX di Koleksiku.

### Added — Baca Tracking
- Kolom `collection_volumes.read_at` — tandai volume individual sudah/belum dibaca lewat icon mata di tiap volume card/baris; volume yang sudah dibaca ditampilkan greyed out.
- Tombol icon mata di header daftar volume (sebelah kiri judul "Volume yang Dimiliki") untuk menandai **semua** volume sudah dibaca sekaligus (`CollectionController::markAllVolumesRead()`).
- Indikator "Terakhir dibaca: Vol. N" di halaman detail koleksi, dihitung otomatis dari volume bernomor tertinggi yang sudah ditandai dibaca.
- Datatable Koleksiku (`/my-collection`) menampilkan progres baca per series: `N/M dibaca`, dihitung dari `read_volumes_count` (query `withCount` dengan kondisi `whereNotNull('read_at')`).

### Added — Mode Hapus Volume
- Tombol "Hapus" di toolbar volume men-toggle "mode seleksi" — saat aktif, icon mata di tiap volume berubah jadi checkbox (menggantikan posisi yang sama) untuk bulk-select, lalu hapus lewat endpoint bulk delete yang sudah ada.

### Added — Review & Rating Pribadi
- Kolom baru `collections.personal_rating` (smallint, -10 s/d 10) dan `collections.personal_review` (text, nullable).
- Card "Review & Rating Pribadi" di halaman detail koleksi — slider -10 (Tidak Direkomendasikan) sampai +10 (Direkomendasikan) gaya MyAnimeList, plus textarea komentar. Endpoint `PATCH /my-collection/{collection}/review`.
- Genre, theme, dan demographic series ditampilkan lengkap di halaman detail koleksi (sebelumnya cuma genre).

### Added — Undo pada Toast
- `useFlash` sekarang bisa menampilkan tombol "Undo" di toast sukses (sonner `action`), didorong dari flash session `undo_url` + `undo_payload` (di-share lewat `HandleInertiaRequests`).
- Toggle baca per-volume: undo memanggil endpoint toggle yang sama (reversible secara alami).
- Tandai semua dibaca: endpoint baru `unmarkVolumesRead` — undo hanya me-revert volume yang *baru saja* diubah oleh aksi tandai-semua, bukan semua volume (supaya tidak salah revert volume yang memang sudah dibaca sebelumnya).

### Added — Global Search (User Side)
- Search bar di tengah header `UserLayout` (desktop) + icon search di topbar mobile, plus shortcut ⌘K/Ctrl+K dari mana saja.
- `GlobalSearch.tsx` (berbasis `cmdk`, komponen sama dengan Command Palette admin) — hasil pencarian gabungan navigasi statis (fuzzy-match otomatis, misal ketik "pinjaman" langsung muncul menu Pinjaman) + judul manga dari Katalog + judul dari Koleksiku sendiri.
- Endpoint baru `GET /search` (`User\SearchController`).

### Changed — Koleksiku
- Grid view Koleksiku diganti dari kartu horizontal (thumbnail kecil 80px) jadi poster card vertikal (cover full-width, aspect 2:3) dengan `grid-template-columns: repeat(auto-fill, minmax(160px,1fr))` — jumlah kolom & lebar cover otomatis menyesuaikan lebar layar, bukan breakpoint tetap.
- Rekomendasi di dashboard user diganti dari grid jadi **Carousel** (`embla-carousel-react` + `ui/carousel.tsx`) — tiap slide nampilkan cover, judul, author, genre/tags, dan sinopsis singkat (dipotong 160 karakter).
- Chart "Progres Volume" di dashboard user dihapus — bias karena banyak series belum punya `total_volumes` terisi (ongoing/ambigu).

### Fixed
- Rekomendasi genre di dashboard user kadang kosong total meski user punya banyak koleksi — root cause: sisa katalog yang belum dikoleksi user tidak semuanya punya data genre, jadi skor overlap selalu 0. Fix: fallback ke pilihan random dari series belum dikoleksi kalau scoring genre tidak menghasilkan apa-apa (dipakai juga di endpoint Surprise Me).
- Baris tabel Koleksiku & Admin Series kadang cuma bisa diklik lewat judulnya — `onClick` navigasi dipindah dari nested di dalam `render` prop `ContextMenuTrigger` ke prop langsung di `ContextMenuTrigger` (jalur merge props yang lebih pasti di Base UI, bukan merge dua lapis).

---

## 2026-07-25 (lanjutan) — Library UI Baru: Empty, Hover Card, Context Menu, Command Palette, Chart

Batch integrasi beberapa komponen shadcn/Base UI yang sebelumnya sudah terpasang tapi belum dipakai, plus fitur baru di dashboard.

### Added
- **`Empty` component** (`ui/empty.tsx`) — dipakai di state kosong Koleksiku dan Pinjaman, menggantikan `EmptyState` lama di kedua halaman itu.
- **Selector jumlah data per halaman** (5/10/25/50/100) di semua datatable server-paginated (Admin Series/Users/Collections/Tickets/Loans/Announcements, User Tickets/Loans) — param `per_page` di-whitelist lewat helper `Controller::perPage()`, `Pagination.tsx` di-extend dengan selector opsional yang baca `data.per_page` dari paginator langsung.
- **Avatar kolektor** di halaman detail Katalog — nampilin stack avatar (tanpa nama, privasi) + jumlah total user yang mengoleksi series tersebut.
- **Hover Card preview** — hover judul series di Admin Series & Koleksiku (table view) nampilin cover, tipe, status, skor tanpa perlu klik.
- **Context menu (klik kanan)** — shortcut Lihat/Edit/Hapus di baris Admin Series & Koleksiku, melengkapi dropdown menu yang sudah ada.
- **Command Palette admin** (⌘K/Ctrl+K, `CommandPalette.tsx`) — navigasi cepat ke semua halaman admin + search langsung ke Series/Users/Tiket lewat endpoint `GET /admin/command-search`. Trigger button juga ada permanen di sidebar admin.
- **Dashboard charts** (`recharts` + `ui/chart.tsx`) — Admin: Series per Status, Koleksi per Tipe, Status Pinjaman (donut). User: Koleksi per Status.
- **Rekomendasi genre + Surprise Me** — dashboard user nampilin rekomendasi series berdasarkan overlap genre dengan koleksi user (dihitung di PHP, bukan raw JSON query DB, supaya portable SQLite/MySQL); tombol "Surprise Me" pilih satu series random (genre-weighted) dengan dialog reveal.

### Skipped
- Komponen `Attachment` untuk upload gambar galeri media admin — spec yang di-paste user hilang saat context compaction sesi sebelumnya, ditunda sampai di-paste ulang.

---

## 2026-07-25 — UX Overhaul: Katalog, Koleksiku, Admin Tools, Konten 18+

Batch besar perbaikan & fitur di sisi user dan admin, plus infrastruktur queue worker untuk deployment.

### Fixed
- Pagination Katalog & Admin Series balik ke halaman 1 sendiri — `useEffect` debounce search yang tidak seharusnya jalan di setiap mount (termasuk saat pindah halaman), sekarang di-skip pada render pertama.

### Added — User Side
- Modal "Tambah Series" di Koleksiku diperbesar, grid pemilihan lebih lega.
- Toggle tampilan Grid/Table + sort (nama, tanggal ditambahkan, jumlah volume) di Koleksiku, tersimpan per-device via `localStorage`.
- Toggle Grid/Table untuk daftar volume yang dimiliki di halaman detail koleksi.
- Filter "sudah/belum di koleksi" di Katalog.
- Checklist volume di koleksi kini bisa diklik di area manapun dalam kotak cover, tidak harus tepat di checkbox.
- Tombol refresh cepat di sebelah search — Katalog, Koleksiku, Admin Series.
- Filter status series (publishing/selesai/hiatus/dll) di Koleksiku.
- Genre series ditampilkan di kartu Koleksiku.
- Kondisi koleksi opsional (mint/bagus/cukup/buruk), bisa diubah dari halaman detail koleksi.
- Widget "tiket terakhir" di dashboard user.
- Galeri media tambahan (screenshot/artwork) di halaman detail Katalog; badge jumlah volume di kartu grid katalog dihapus (dianggap tidak informatif).
- Avatar user (dengan fallback inisial) ditampilkan konsisten di sidebar.

### Added — Admin Side
- Halaman Koleksi admin direstruktur: daftar dikelompokkan per user (dengan drill-down), bukan tabel flat semua koleksi.
- Import AniList tidak lagi pindah halaman setelah import — tetap di halaman cari, dengan tombol "lihat di katalog" untuk series yang sudah diimpor.
- Sidebar admin & user direstruktur jadi kategori/sub-kategori collapsible (mis. grup "AniList", grup "Lainnya").
- Halaman Storage Settings + Database Backup digabung jadi satu halaman `/admin/settings` bertab.
- Filter "sembunyikan konten 18+" saat mencari di AniList, plus badge 18+ pada hasil.
- Pengaturan global "blur konten 18+" (tab Konten di halaman Pengaturan) — cover series 18+ otomatis di-blur di seluruh halaman user, klik untuk membuka sementara (gaya Reddit/Instagram).
- Log aktivitas admin — mencatat aksi sensitif (hapus/bulk-delete series & volume, ban/unban/ganti role user, import database, ubah pengaturan storage/konten) dengan halaman viewer baru.
- Upload galeri media tambahan per series dari halaman Edit Series.
- Migrasi file storage otomatis (Local ↔ S3) saat driver/bucket/endpoint diganti — berjalan di background lewat queue job, status ditampilkan di halaman Pengaturan.

### Changed — Infrastruktur Deployment
- `deploy.sh` sekarang install & konfigurasi **Supervisor** untuk queue worker (`malas-worker`) — wajib supaya job antrian seperti migrasi storage benar-benar berjalan di production.
- `update.sh` menjalankan `php artisan queue:restart` setelah update kode, supaya worker yang sedang berjalan pakai kode terbaru.
- `docs/DEPLOYMENT.md` diperbarui: prasyarat Supervisor, langkah manual setup queue worker, troubleshooting job antrian tidak jalan.

---

## 2026-07-23 — Admin Series Bulk Delete

### Added
- Checkbox multi-select di `Admin/Series/Index.tsx` — pilih banyak series sekaligus untuk dihapus dalam satu aksi, tidak perlu satu-satu.
- `SeriesController::bulkDestroy()` + route `DELETE /admin/series/bulk` (didaftarkan sebelum resource route agar tidak bentrok dengan wildcard `{series}`).
- Toolbar "Hapus (N)" muncul di `PageHeader` saat ada series terpilih, dengan dialog konfirmasi terpisah dari delete single-item.

---

## 2026-07-22 — Storage Settings, Database Backup, Ticket System, AniList Fixes

### Added
- **Storage settings via UI admin** (`/admin/settings/storage`) — driver `local` atau `s3` (kompatibel AWS S3 maupun S3-compatible seperti Cloudflare R2), disimpan di tabel `storage_settings` dengan `secret_access_key` ter-encrypt. Konfigurasi tidak lagi lewat `.env`.
- **`StorageSettingsService`** — satu pintu untuk semua operasi file (disk, url, store, delete). Semua kode yang sebelumnya akses `Storage::` facade langsung dimigrasi ke service ini.
- **Database backup & import** (`/admin/settings/database`, super_admin only) — download dump SQL (exclude tabel sensitif: `users`, `sessions`, `jobs`, dll), import dengan `DELETE FROM` + `INSERT` per tabel dibungkus `DB::transaction()` supaya atomic dan bisa rollback kalau gagal di tengah.
- **Sistem tiket** — user bisa buat tiket request (misal minta judul baru masuk katalog) dari `User/Tickets/Create.tsx` (bisa pre-filled dari halaman katalog), admin merespon dari `Admin/Tickets/Show.tsx`. Status: `open`, `in_progress`, `resolved`, `closed`.
- Note "buat tiket request" di halaman Catalog dan Collection saat hasil pencarian kosong / koleksi belum ada di katalog.
- Volume range input syntax — `CollectionController::storeVolumes()` menerima format campuran seperti `1,2,3,5-9,11,12,15-18`, di-expand jadi list volume individual (auto-swap kalau range terbalik, dedupe, limit 100 per batch).

### Fixed
- Popover "Sync AniList" / search card yang posisinya aneh (nempel di bawah/kanan trigger, bukan di tengah) — diganti absolute overlay `inset-0` di dalam wrapper `relative` per-card, karena anchor engine Base UI Popover tidak didesain untuk centering penuh.
- Cover preview di Edit Series tidak muncul setelah upload baru — React reuse DOM node `<img>` yang sama antar render sehingga `style.display = 'none'` dari `onError` lama nyangkut. Fix: tambah `key={displayCover}` supaya React remount elemen saat source berubah.

---

## 2026-07-21 — SSO Integration

### Added
- `SsoController` — autentikasi PKCE-based OAuth2 ke whitearchive.id. Semua user (termasuk admin) login lewat SSO, tidak ada form register/login lokal lagi.
- Kolom baru di `users`: `sso_id` (unique), `username`, `avatar`. `password` diubah jadi nullable.
- Halaman `Settings/Index.tsx` — profil user ditampilkan read-only (data profil dikelola di sisi SSO).

---

## Sebelumnya — AniList Migration & Series Management

### Changed
- **Migrasi total dari Jikan (MyAnimeList) ke AniList GraphQL** — `JikanService` dihapus, diganti `AniListService`. Kolom baru di `series`: `anilist_id`, `genres`, `authors`, `themes`, `demographics` (semua json).
- Search & import series dari AniList (`Admin/AniList/Index.tsx`) dan sync ulang metadata ke series existing (Popover "Sync AniList" di Edit Series).

### Added
- Volume tracking per-user di koleksi pribadi, bulk delete volume dari halaman detail koleksi.
- Sistem peminjaman (loan) volume dari koleksi pribadi.

---

## Sebelumnya — Fondasi (Phase 0–10)

Setup awal Laravel 12 + Inertia v2 + React 19, sistem auth & role (Spatie Permission), menu management berbasis database, CRUD series/volume admin, katalog & koleksi user, announcements, user & menu management. Detail lengkap ada di [`docs/PHASES.md`](docs/PHASES.md). QA pass pertama: 2026-07-03.
