> ⚠️ **Legacy — tidak dipakai lagi.** Dokumen ini mendeskripsikan desain untuk stack lama (Blade + Alpine.js + TomSelect + Jikan API) dari sebelum rebuild ke React 19 + Inertia.js v2 + shadcn/ui (Base UI) yang jadi stack aktual sekarang (lihat [`docs/prd.md`](../docs/prd.md) §1). Desain sistem yang berlaku saat ini adalah token & komponen shadcn/ui default (Tailwind CSS v4) — bukan token custom TailAdmin/Kenmei di bawah ini. Dibiarkan sebagai referensi historis, jangan dipakai sebagai acuan implementasi baru.

# Design System — MALAS Admin Panel (Legacy, Pra-Rebuild)

> Referensi visual: **TailAdmin Free** (layout + komponen admin) + **Kenmei** (UX pattern + data density).  
> Stack: Blade + Alpine.js + Tailwind CSS (kustom token TailAdmin).  
> Dark mode: class-based (`darkMode: 'class'`).

---

## Prinsip Desain

- **Clean & breathable**: minimal elemen, konsisten whitespace, hierarchy lewat ukuran dan weight — bukan warna.
- **Data-dense**: tabel padat, spacing ketat (4px grid), tapi setiap baris tetap mudah di-scan.
- **Cover-first**: artwork manga adalah identitas utama — tampilkan cover di setiap konteks yang relevan.
- **Subtle**: shadow hanya `shadow-sm`, borders tipis, hover states halus. Tidak ada gradien atau dekorasi berlebihan.
- **Keyboard-first**: setiap elemen interaktif punya `focus-visible` ring yang jelas.

---

## Token Warna (TailAdmin)

Token warna TailAdmin sudah ditambah di `tailwind.config.js` sebagai extend. Gunakan class berikut di semua komponen baru.

### Custom Token (tersedia sebagai class Tailwind)

| Token | Hex | Class Tailwind | Digunakan untuk |
|---|---|---|---|
| `boxdark` | `#24303F` | `bg-boxdark` | Card/surface gelap di dark mode |
| `boxdark-2` | `#1A222C` | `bg-boxdark-2` | Body background di dark mode |
| `stroke` | `#E2E8F0` | `border-stroke` | Border di light mode |
| `strokedark` | `#2E3A47` | `border-strokedark` | Border di dark mode |
| `whiten` | `#F1F5F9` | `bg-whiten` | Body background di light mode |
| `bodydark` | `#AEB7C0` | `text-bodydark` | Body text di dark mode |
| `bodydark2` | `#8A99AF` | `text-bodydark2` | Label/caption di dark sidebar |
| `graydark` | `#333A48` | `bg-graydark` | Sidebar nav item hover/active |
| `meta-3` | `#10B981` | `text-meta-3` | Success (emerald) |
| `meta-6` | `#FFBA00` | `text-meta-6` | Warning amber |

### Card Pattern (TailAdmin)

```html
<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
  <div class="border-b border-stroke px-5 py-4 dark:border-strokedark">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Judul</h3>
  </div>
  <div class="p-5"><!-- konten --></div>
</div>
```

**Key differences dari old pattern:**
- `rounded-sm` bukan `rounded-lg` — lebih angular/tegas
- `shadow-default` bukan `shadow-sm` — shadow lebih visible (8px blur)
- `dark:bg-boxdark` (`#24303F`) bukan `dark:bg-zinc-900` (`#18181b`) — lebih blue-tinted
- `border-stroke / dark:border-strokedark` sebagai pengganti `border-slate-200 / dark:border-zinc-700`

### Light Mode

| Peran | Kelas Tailwind | Hex |
|---|---|---|
| Page background | `bg-gray-50` | #f9fafb |
| Card / surface | `bg-white` | #ffffff |
| Muted surface | `bg-gray-100` | #f3f4f6 |
| Border utama | `border-gray-200` | #e5e7eb |
| Border halus | `border-gray-100` | #f3f4f6 |
| Text primary | `text-gray-900` | #111827 |
| Text secondary | `text-gray-600` | #4b5563 |
| Text muted | `text-gray-500` | #6b7280 |
| Text placeholder | `text-gray-400` | #9ca3af |
| Primary | `bg-blue-600` | #2563eb |
| Primary hover | `bg-blue-700` | #1d4ed8 |
| Danger | `bg-red-600` | #dc2626 |
| Success | `text-emerald-600` | #059669 |
| Warning | `text-amber-600` | #d97706 |

> **Note implementasi**: file-file yang sudah ada menggunakan `slate`/`indigo` palette — kedua palette itu tetap valid. Untuk komponen baru, gunakan `gray`/`blue` agar lebih sesuai Kenmei. Jangan refactor massal yang existing.

> **Verified dari source Kenmei** (CSS build aktual): Dark cards pakai `gray-800` (#1f2937), dark surface `gray-700` (#374151), dark deep bg `gray-900` (#111827). Zinc palette yang kita pakai hampir identik — tidak perlu migrasi.

### Dark Mode (prefix `dark:`)

| Peran | Kelas Tailwind | Hex | Kenmei analog |
|---|---|---|---|
| Page background | `dark:bg-zinc-950` | #09090b | gray-900 #111827 |
| Card / surface | `dark:bg-zinc-900` | #18181b | gray-800 #1f2937 |
| Muted surface | `dark:bg-zinc-800` | #27272a | gray-700 #374151 |
| Border utama | `dark:border-zinc-700` | #3f3f46 | gray-700 #374151 |
| Border halus | `dark:border-zinc-800` | #27272a | gray-800 #1f2937 |
| Text primary | `dark:text-zinc-50` | #fafafa | white #ffffff |
| Text secondary | `dark:text-zinc-200` | #e4e4e7 | gray-300 #d1d5db |
| Text muted | `dark:text-zinc-400` | #a1a1aa | gray-300 #d1d5db |
| Text placeholder | `dark:text-zinc-500` | #71717a | gray-500 #6b7280 |
| Primary dark | `dark:bg-blue-500` | #3b82f6 | blue-600 #2563eb |

> **Catatan**: Kenmei menggunakan amber `#ffc322` untuk link warna di dark mode — kita tetap pakai blue (lebih cocok untuk admin panel).

### Aktivasi Dark Mode

Toggle dengan Alpine store:
```js
Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark',
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    }
});
```

---

## Tipografi

- **Font**: Inter var (`font-sans` + Google Fonts)
- **Base size**: `text-sm` (14px) untuk body/table
- **Page heading**: `text-sm font-semibold text-gray-900 dark:text-zinc-50`
- **Section heading**: `text-sm font-semibold text-gray-700 dark:text-zinc-200`
- **Label form**: `text-sm font-medium text-gray-700 dark:text-zinc-200`
- **Helper / muted**: `text-xs text-gray-400 dark:text-zinc-500`
- **Caption uppercase**: `text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-zinc-400`

---

## Spacing & Radius

| Token | Value | Tailwind |
|---|---|---|
| space-1 | 4px | `p-1` / `gap-1` |
| space-2 | 8px | `p-2` / `gap-2` |
| space-3 | 12px | `p-3` / `gap-3` |
| space-4 | 16px | `p-4` / `gap-4` |
| space-5 | 24px | `p-6` / `gap-6` |
| space-6 | 32px | `p-8` / `gap-8` |
| radius-xs | 4px | `rounded` |
| radius-sm | 6px | `rounded-md` |
| radius-md | 8px | `rounded-lg` |
| radius-pill | 9999px | `rounded-full` |

**Rules:**
- `rounded-lg` (8px) untuk card/panel
- `rounded-md` (6px) untuk button/input/select
- `rounded-full` untuk badge/chip/avatar
- `rounded` (4px) untuk tag kecil/kode

---

## Komponen

### Button

```html
<!-- Primary -->
<button class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600">
  Simpan
</button>

<!-- Secondary -->
<button class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
  Batal
</button>

<!-- Destructive -->
<button class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-red-700 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
  Hapus
</button>

<!-- Ghost (tabel actions) -->
<button class="rounded-md px-2.5 py-1 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50">
  Edit
</button>
```

### Card

```html
<div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
  <!-- Header -->
  <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-zinc-200">Judul</h3>
  </div>
  <!-- Body -->
  <div class="p-5"><!-- konten --></div>
</div>
```

### Table

```html
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
  <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
    <thead class="bg-gray-50 dark:bg-zinc-800">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Col</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
      <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-zinc-800/50">
        <td class="px-4 py-3 text-sm text-gray-800 dark:text-zinc-200">Nilai</td>
      </tr>
    </tbody>
  </table>
</div>
```

### Badge / Chip

```html
<!-- Pill badge — Kenmei style, rounded-full -->
<!-- Generic / default -->
<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-zinc-700 dark:text-zinc-200">Default</span>
<!-- Reading / Aktif -->
<span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Reading</span>
<!-- Completed -->
<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Completed</span>
<!-- On Hold / Paused -->
<span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">On Hold</span>
<!-- Dropped -->
<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400">Dropped</span>
<!-- Plan to read -->
<span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/40 dark:text-purple-400">Plan to Read</span>
<!-- Content type tag (Manhwa, Manga, Manhua) -->
<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-zinc-700 dark:text-zinc-300">Manhwa</span>
```

**Status badge color map** (dari Kenmei dashboard visual):

| Status | Light | Dark |
|--------|-------|------|
| Reading / Aktif | emerald-100 / emerald-700 | emerald-900/40 / emerald-400 |
| Completed | blue-100 / blue-700 | blue-900/40 / blue-400 |
| On Hold | amber-100 / amber-700 | amber-900/40 / amber-400 |
| Dropped | red-100 / red-700 | red-900/40 / red-400 |
| Plan to Read | purple-100 / purple-700 | purple-900/40 / purple-400 |
| Tag (Manhwa dll) | gray-100 / gray-600 | zinc-700 / zinc-300 |

```html
<!-- Volume chip — selected/unselected state -->
<button class="rounded-md border border-blue-600 bg-blue-600 px-2.5 py-1 text-xs font-medium text-white">Vol 1</button>
<button class="rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 hover:border-blue-300 hover:text-blue-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">Vol 2</button>
```

### Input / Select

```html
<input type="text"
       class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">

<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-zinc-200">
  Label <span class="text-red-500">*</span>
</label>
```

### Alert / Feedback

```html
<!-- Success -->
<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
  Berhasil.
</div>
<!-- Error -->
<div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
  Gagal.
</div>
```

### Modal (Alpine.js)

```html
<div x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
     @click.self="open = false">
  <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
    <h3 class="mb-5 text-base font-semibold text-gray-900 dark:text-zinc-50">Judul</h3>
    <div class="mt-5 flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-zinc-800">
      <button @click="open = false">Batal</button>
      <button>Konfirmasi</button>
    </div>
  </div>
</div>
```

### Manga Cover Image

Cover manga (thumbnail) di semua konteks — dropdown search, panel, kartu:

```html
<!-- Di dalam card/panel (desktop) -->
<img src="{cover_url}"
     class="h-20 w-14 shrink-0 rounded object-cover shadow-[0_3px_10px_rgba(0,0,0,0.2)]"
     alt="{title}">

<!-- Di dropdown TomSelect (lebih kecil) -->
<img src="{cover_url}"
     class="h-10 w-7 shrink-0 rounded object-cover shadow-sm"
     alt="">
```

Shadow `0 3px_10px_rgba(0,0,0,0.2)` diambil langsung dari Kenmei UpsertEntry CSS.

---

### Stat / Score Display

Untuk angka statistik besar (total volume, skor, dll):

```html
<dd class="text-4xl font-bold tracking-tight tabular-nums text-gray-900 dark:text-zinc-50">
  127
</dd>
<dt class="text-xs font-medium uppercase tracking-widest text-gray-500 dark:text-zinc-400">
  Volume
</dt>
```

### Progress Bar

```html
<div class="h-3 w-full rounded-full border border-gray-200 bg-gray-100 dark:border-zinc-800 dark:bg-zinc-800">
  <div class="h-full rounded-full bg-blue-600 dark:bg-blue-500"
       style="width: 65%"></div>
</div>
```

---

### Add Manga / Search Modal

Modal pencarian manga — observed langsung dari Kenmei "add manga" screen:

```html
<!-- Modal overlay -->
<div class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 pt-16 px-4">
  <div class="w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-zinc-900">
    <!-- Search input -->
    <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-zinc-800">
      <svg class="h-4 w-4 shrink-0 text-gray-400"><!-- search icon --></svg>
      <input type="text" placeholder="Search series title..."
             class="w-full bg-transparent text-sm text-gray-900 outline-none placeholder-gray-400 dark:text-zinc-100 dark:placeholder-zinc-500">
    </div>
    <!-- Tabs: Series | Users (jika ada) -->
    <div class="flex border-b border-gray-100 dark:border-zinc-800">
      <button class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">Series</button>
    </div>
    <!-- Result list -->
    <ul class="max-h-80 overflow-y-auto py-1">
      <li class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-zinc-800/60">
        <img src="{cover}" class="h-14 w-10 shrink-0 rounded object-cover shadow-sm" alt="">
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-gray-900 dark:text-zinc-100">{title}</p>
          <p class="mt-0.5 text-xs text-gray-400 dark:text-zinc-500">{chapters} chapters · {users} users</p>
          <!-- tag badge -->
          <span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-zinc-700 dark:text-zinc-300">Manhwa</span>
        </div>
        <!-- Already in list indicator -->
        <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Ada</span>
      </li>
    </ul>
  </div>
</div>
```

### Manga Search Result Item (TomSelect dropdown)

Digunakan di TomSelect custom render saat user mencari manga untuk ditambah ke koleksi:

```html
<!-- Dropdown option item -->
<div class="flex items-center gap-3 px-3 py-2">
  <img src="{cover}" class="h-10 w-7 rounded object-cover shadow-sm" alt="">
  <div class="min-w-0 flex-1">
    <p class="truncate text-sm font-medium text-gray-900 dark:text-zinc-100">{title}</p>
    <p class="truncate text-xs text-gray-400 dark:text-zinc-500">{volumes} vol · {status}</p>
  </div>
  <!-- "In library" indicator -->
  <span class="shrink-0 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Ada</span>
</div>
```

### Edit Entry / UpsertEntry Form

Dialog dua-kolom yang muncul setelah user memilih manga — observed dari Kenmei "edit entry" screen:

```html
<div class="flex gap-6">
  <!-- Kiri: cover -->
  <div class="w-28 shrink-0">
    <img src="{cover}" class="w-full rounded object-cover shadow-[0_3px_10px_rgba(0,0,0,0.2)]" alt="">
    <a href="#" class="mt-2 block text-center text-xs text-gray-400 hover:text-gray-600 dark:text-zinc-500">Rate on first chapter</a>
  </div>
  <!-- Kanan: form fields -->
  <div class="flex-1 space-y-4">
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Last Read Chapter</label>
      <input type="text" class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
      <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">Or 311 chapters remaining</p>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Status</label>
      <select class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
        <option>Reading</option>
      </select>
    </div>
    <!-- Notes textarea, Score row, hidden/favourite toggles -->
    <button class="w-full rounded-md bg-gray-900 py-2.5 text-sm font-medium text-white hover:bg-gray-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
      Update Entry
    </button>
  </div>
  <!-- Stats panel (sidebar kanan) -->
  <div class="w-32 shrink-0 space-y-3 text-center">
    <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium dark:bg-zinc-700">Manhwa</span>
    <div>
      <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-zinc-50">8.58</p>
      <p class="text-xs text-gray-400 dark:text-zinc-500">Score</p>
    </div>
    <div>
      <p class="text-sm font-medium text-gray-700 dark:text-zinc-200">Releasing</p>
      <p class="text-xs text-gray-400 dark:text-zinc-500">Status</p>
    </div>
  </div>
</div>
```

> **Catatan**: "Update Entry" button di Kenmei menggunakan **dark/inverse style** (`bg-gray-900 text-white` di light, `bg-zinc-100 text-zinc-900` di dark) — bukan blue primary. Ini hanya untuk primary action di modal, bukan button secara umum.

---

### Dashboard List Row (no-border style)

Kenmei dashboard menggunakan **zero table borders** — hanya divider tipis antar row:

```html
<ul class="divide-y divide-gray-100 dark:divide-zinc-800">
  <li class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-zinc-800/40">
    <!-- Status indicator dot -->
    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
    <!-- Title -->
    <span class="flex-1 truncate text-sm text-gray-900 dark:text-zinc-100">Series Title</span>
    <!-- Status badge -->
    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Reading</span>
    <!-- Chapter info -->
    <span class="w-20 text-right text-xs tabular-nums text-gray-500 dark:text-zinc-400">Ch. 34</span>
    <!-- Timestamp -->
    <span class="w-28 text-right text-xs text-gray-400 dark:text-zinc-500">Updated 4h ago</span>
  </li>
</ul>
```

### Cover Grid (Browse/Library)

Grid cover-only — TIDAK ada card container, hanya cover + judul di bawah (seperti Kenmei Discovery):

```html
<div class="grid grid-cols-5 gap-4">
  <a href="#" class="group">
    <div class="aspect-[2/3] overflow-hidden rounded-md shadow-sm transition-transform group-hover:scale-[1.02]">
      <img src="{cover}" class="h-full w-full object-cover" alt="{title}">
    </div>
    <p class="mt-1.5 truncate text-xs font-medium text-gray-800 dark:text-zinc-200">{title}</p>
  </a>
</div>
```

---

### Manga Panel (setelah Jikan import)

Panel yang muncul setelah user memilih manga dari search. Menampilkan cover + metadata + volume picker:

```html
<div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
  <!-- Manga header -->
  <div class="flex gap-4 p-4">
    <img src="{cover}" class="h-20 w-14 shrink-0 rounded object-cover shadow-sm">
    <div class="min-w-0 flex-1">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-zinc-100">{title}</h4>
      <p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-400">{title_english}</p>
      <div class="mt-2 flex flex-wrap gap-1.5">
        <span class="badge-status">{status}</span>
        <span class="text-xs text-gray-400">{total_volumes} volume</span>
      </div>
    </div>
    <button class="remove-entry text-gray-400 hover:text-red-500">×</button>
  </div>
  <!-- Volume picker grid -->
  <div class="border-t border-gray-100 px-4 py-3 dark:border-zinc-800">
    <div class="flex flex-wrap gap-1.5">
      <!-- Vol chip per volume (toggle selected) -->
    </div>
  </div>
  <!-- Collection details -->
  <div class="border-t border-gray-100 px-4 py-3 dark:border-zinc-800">
    <!-- condition, is_for_loan, price, date, notes -->
  </div>
</div>
```

---

## Layout Admin (TailAdmin)

- **Sidebar**: `bg-[#1C2434]` — biru-abu gelap khas TailAdmin, lebar `w-72`
- **Sidebar nav item** (default): `text-[#DEE4EE] hover:bg-graydark`
- **Sidebar nav item** (active): `bg-graydark`
- **Sidebar category header**: `text-bodydark2 text-[10px] uppercase tracking-widest`
- **Top bar**: `bg-white dark:bg-boxdark border-b border-stroke dark:border-strokedark shadow-1`
- **Top bar height**: `h-[70px]` (sinkron dengan sidebar header)
- **Content area**: `bg-whiten dark:bg-boxdark-2` (`#F1F5F9` / `#1A222C`)

### Nav Bar Pattern (dari Kenmei visual)

Kenmei nav: **logo kiri → nav links tengah → search + icons kanan**. White bg, sangat tipis border bawah:

```html
<nav class="flex h-12 items-center gap-6 border-b border-gray-100 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900">
  <!-- Logo -->
  <a href="#" class="text-sm font-bold text-gray-900 dark:text-zinc-50">MALAS</a>
  <!-- Nav links -->
  <div class="flex items-center gap-1">
    <a href="#" class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50">Dashboard</a>
    <a href="#" class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50">Series</a>
    <a href="#" class="rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-900 dark:bg-zinc-800 dark:text-zinc-50">Collections</a>
  </div>
  <!-- Spacer -->
  <div class="flex-1"></div>
  <!-- Search + icons -->
  <div class="flex items-center gap-2">
    <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
      <svg class="h-3.5 w-3.5 text-gray-400"><!-- search --></svg>
      <input class="w-40 bg-transparent text-sm text-gray-600 outline-none placeholder-gray-400 dark:text-zinc-300 dark:placeholder-zinc-500" placeholder="Quick search...">
      <kbd class="rounded border border-gray-200 px-1 text-[10px] text-gray-400 dark:border-zinc-600">⌘K</kbd>
    </div>
    <button class="rounded-full p-1.5 text-gray-500 hover:bg-gray-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
      <!-- icon avatar / user --></button>
  </div>
</nav>
```

---

## Jikan On-Demand Import Flow

Saat user menambah koleksi, data manga diambil dari Jikan saat itu juga (bukan dari scraper):

```
User ketik judul
  → GET /admin/api/jikan/search?q=xxx
  → Jikan search API + local DB merge (local hasilnya prioritas / ditampilkan duluan)
  → TomSelect dropdown tampil cover + metadata

User pilih result (mal_id sebagai value)
  → POST /admin/api/jikan/import { mal_id }
  → Jika series belum ada di DB: fetch detail dari Jikan, upsert Series + buat Volume 1..N
  → Jika sudah ada: return data lokal langsung (cepat)
  → Return: { series, volumes[] }

User pilih volume-volume yang dimiliki + isi detail
  → POST /admin/collections/bulk { user_id, entries: [...] }
  → Pakai volume_id lokal (sudah tersedia setelah import)
```

**Rate limit**: Jikan public API: 3 req/s, 60 req/min. Cukup untuk admin yang menambah satu-satu.

**Volume creation**: Jikan hanya memberikan jumlah volume (`total_volumes`). Backend membuat record Volume 1..N secara otomatis. Volume tanpa total_volumes (on-going) tidak dibuat otomatis — admin bisa tambah manual via volume management.

---

## Volume Management (Series Show Page)

Admin pilih user → tabel semua volume → per baris dropdown status.

| Status | Badge |
|--------|-------|
| `not_owned` | — |
| `owned` | emerald |
| `loaned` | amber (disabled) |

---

## Referensi Visual

| Site | Digunakan untuk | Key takeaway |
|------|-----------------|-------------|
| TailAdmin Free | Layout frame, komponen admin, color tokens | Sidebar `#1C2434`, card `rounded-sm + shadow-default`, body `#F1F5F9` |
| [Kenmei](https://www.kenmei.co) | UX patterns — data density, manga display | Inter font, gray-800 dark cards, badge map, cover-first lists |
| [MyAnimeList](https://myanimelist.net) | Data density, cover presentation | Blue #2e51a2 primary, high-density table |

**MAL dark mode tokens** (untuk referensi, tidak diikuti 1:1):
- Dark bg: `#121212`, surface: `#272727`/`#353535`, text: `#cacaca`/`#e0e0e0`
- Primary dark: `#4f74c8`, border: `#272727`

---

## Anti-patterns

- Jangan `shadow-xl` atau `shadow-2xl` di card biasa — gunakan `shadow-sm` + border
- Jangan `rounded-xl` atau `rounded-2xl` — max `rounded-lg`
- Jangan inline color hex — semua warna via Tailwind class
- Jangan bulk scrape untuk data yang bisa di-fetch on-demand
- Jangan TomSelect tanpa custom render kalau ada data visual (cover) yang bisa ditampilkan
- Jangan skip `tabular-nums` untuk angka statistik/harga/volume — Kenmei pakai ini konsisten
- Jangan gunakan amber/yellow sebagai primary accent di admin panel (Kenmei pakai ini di dark mode, tapi konteksnya user-facing app, bukan admin)
