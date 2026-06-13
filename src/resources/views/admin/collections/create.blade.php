@extends('admin.layouts.app')
@section('title', 'Tambah Koleksi')
@section('heading', 'Tambah Koleksi')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.collections.index') }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar koleksi
    </a>
</div>

<div class="max-w-2xl" id="app-root" x-data="collectionForm()" x-init="init()">

    {{-- Step 1: User picker --}}
    <div class="mb-4 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-500">Anggota</p>
        </div>
        <div class="px-5 py-4">
            <select id="user-select" placeholder="Cari nama atau email...">
                @foreach(\App\Modules\Core\Models\User::orderBy('name')->get() as $u)
                <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                @endforeach
            </select>
            <p x-show="!userId" class="mt-2 text-xs text-slate-400 dark:text-zinc-500">
                Pilih anggota untuk mulai menambah koleksi.
            </p>
        </div>
    </div>

    {{-- Step 2: Manga search --}}
    <div x-show="userId" x-cloak class="mb-4">
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-500">Cari Manga</p>
            </div>
            <div class="px-5 py-4">
                <select id="manga-search" placeholder="Ketik judul manga..."></select>
                <p class="mt-2 text-xs text-slate-400 dark:text-zinc-500">
                    Cari langsung dari Jikan (MyAnimeList). Manga yang sudah ada ditampilkan pertama.
                </p>
            </div>
        </div>
    </div>

    {{-- Step 3: Manga entries --}}
    <div class="space-y-3">
        <template x-for="(entry, idx) in entries" :key="entry.uid">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

                {{-- Manga header --}}
                <div class="flex items-start gap-4 p-4">
                    <div class="shrink-0">
                        <img x-show="entry.series.cover_url" :src="entry.series.cover_url" :alt="entry.series.title_romaji"
                             class="h-20 w-14 rounded object-cover shadow-sm">
                        <div x-show="!entry.series.cover_url"
                             class="flex h-20 w-14 items-center justify-center rounded bg-slate-100 dark:bg-zinc-800">
                            <svg class="h-6 w-6 text-slate-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-zinc-100" x-text="entry.series.title_romaji"></h4>
                        <p x-show="entry.series.title_english" class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400" x-text="entry.series.title_english"></p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span x-show="entry.series.status"
                                  :class="{
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400': ['publishing','Publishing'].includes(entry.series.status),
                                    'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300': ['finished','Finished'].includes(entry.series.status),
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': ['on_hiatus','On Hiatus'].includes(entry.series.status),
                                    'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400': !['publishing','finished','on_hiatus','Publishing','Finished','On Hiatus'].includes(entry.series.status),
                                  }"
                                  class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                  x-text="(entry.series.status || '').replace(/_/g,' ')">
                            </span>
                            <span x-show="entry.series.total_volumes" class="text-xs text-slate-400 dark:text-zinc-500">
                                <span x-text="entry.series.total_volumes"></span> vol
                            </span>
                        </div>
                    </div>

                    <button type="button" @click="removeEntry(idx)"
                            class="shrink-0 rounded p-1 text-slate-300 transition-colors hover:text-red-500 dark:text-zinc-600 dark:hover:text-red-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Loading --}}
                <div x-show="entry.loading" class="border-t border-slate-100 px-5 py-4 dark:border-zinc-800">
                    <div class="flex items-center gap-2 text-sm text-slate-400 dark:text-zinc-500">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Mengambil data dari Jikan...
                    </div>
                </div>

                {{-- Volume picker --}}
                <div x-show="!entry.loading && entry.volumes.length > 0"
                     class="border-t border-slate-100 px-4 py-3 dark:border-zinc-800">
                    <div class="mb-2.5 flex items-center justify-between">
                        <p class="text-xs font-medium text-slate-600 dark:text-zinc-400">
                            Pilih volume
                            <span x-show="entry.selectedIds.length > 0" class="ml-1 text-slate-400 dark:text-zinc-500">
                                (<span x-text="entry.selectedIds.length"></span> dipilih)
                            </span>
                        </p>
                        <div class="flex gap-3 text-xs">
                            <button type="button" @click="selectAll(idx)"
                                    class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Pilih semua</button>
                            <button type="button" @click="deselectAll(idx)"
                                    class="text-slate-400 hover:text-slate-600 dark:text-zinc-500">Hapus pilihan</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="vol in entry.volumes" :key="vol.id">
                            <button type="button"
                                    @click="toggleVolume(idx, vol.id)"
                                    :class="entry.selectedIds.includes(String(vol.id))
                                        ? 'border-indigo-600 bg-indigo-600 text-white dark:border-indigo-500 dark:bg-indigo-500'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:border-indigo-600'"
                                    class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors">
                                Vol <span x-text="vol.volume_number"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- No volumes --}}
                <div x-show="!entry.loading && entry.volumes.length === 0 && entry.series.id"
                     class="border-t border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        Jumlah volume belum diketahui (manga ongoing). Tambah volume manual via halaman series.
                    </p>
                </div>

                {{-- Collection details --}}
                <div x-show="!entry.loading && entry.selectedIds.length > 0"
                     class="border-t border-slate-100 bg-slate-50/50 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-800/20">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-600">Detail koleksi</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-400">Kondisi <span class="text-red-500">*</span></label>
                            <select x-model="entry.condition"
                                    class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                <option value="mint">Mint</option>
                                <option value="very_good">Very Good</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-700 dark:text-zinc-300">
                                <input type="checkbox" x-model="entry.is_for_loan"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Bisa dipinjam
                            </label>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-400">Harga beli</label>
                            <input type="number" x-model="entry.purchase_price" min="0" step="1000" placeholder="0"
                                   class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-400">Tanggal beli</label>
                            <input type="date" x-model="entry.purchase_date"
                                   class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-400">Catatan</label>
                        <textarea x-model="entry.notes" rows="2"
                                  class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                    </div>
                </div>

            </div>
        </template>
    </div>

    {{-- Feedback --}}
    <div x-show="feedback.message" x-cloak class="mt-4">
        <div :class="feedback.type === 'error'
                ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'"
             class="rounded-md border px-4 py-3 text-sm"
             x-text="feedback.message">
        </div>
    </div>

    {{-- Submit --}}
    <div class="mt-5 flex gap-3" x-show="userId" x-cloak>
        <button type="button" @click="submitAll()"
                :disabled="submitting || totalVolumes === 0"
                :class="submitting || totalVolumes === 0 ? 'cursor-not-allowed opacity-40' : 'hover:bg-indigo-700 dark:hover:bg-indigo-600'"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors dark:bg-indigo-500">
            <span x-show="!submitting">
                Simpan (<span x-text="totalVolumes"></span> volume)
            </span>
            <span x-show="submitting">Menyimpan...</span>
        </button>
        <a href="{{ route('admin.collections.index') }}"
           class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
            Batal
        </a>
    </div>

</div>

@push('scripts')
<script>
const JIKAN_SEARCH_URL = '{{ route('admin.api.jikan.search') }}';
const JIKAN_IMPORT_URL = '{{ route('admin.api.jikan.import') }}';
const BULK_URL         = '{{ route('admin.collections.bulk') }}';
const INDEX_URL        = '{{ route('admin.collections.index') }}';
const CSRF_TOKEN       = document.querySelector('meta[name=csrf-token]')?.content || '';

let _uid = 0;

function collectionForm() {
    return {
        userId:     '',
        entries:    [],
        submitting: false,
        feedback:   { message: '', type: '' },

        get totalVolumes() {
            return this.entries.reduce((s, e) => s + e.selectedIds.length, 0);
        },

        init() {
            window._cApp = this;
            this.$nextTick(() => this.initUserSelect());
        },

        initUserSelect() {
            const el = document.getElementById('user-select');
            if (!el || el.tomselect) return;
            new TomSelect(el, {
                maxOptions: 100,
                onChange: (val) => {
                    this.userId = val;
                    this.$nextTick(() => this.initMangaSearch());
                },
            });
        },

        initMangaSearch() {
            const el = document.getElementById('manga-search');
            if (!el || el.tomselect) return;
            const app = window._cApp;

            new TomSelect(el, {
                valueField:   'mal_id',
                labelField:   'title',
                searchField:  ['title', 'title_english'],
                placeholder:  'Ketik judul manga...',
                create:       false,
                maxOptions:   20,
                loadThrottle: 400,
                load(query, callback) {
                    if (query.length < 2) return callback();
                    fetch(`${JIKAN_SEARCH_URL}?q=${encodeURIComponent(query)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    }).then(r => r.json()).then(callback).catch(() => callback());
                },
                render: {
                    option(item, esc) {
                        const cover = item.cover
                            ? `<img src="${esc(item.cover)}" class="h-10 w-7 shrink-0 rounded object-cover shadow-sm" alt="" onerror="this.style.display='none'">`
                            : `<div class="h-10 w-7 shrink-0 rounded bg-slate-100"></div>`;
                        const vol   = item.volumes ? `${item.volumes} vol` : 'vol ?';
                        const inLib = item.in_library
                            ? `<span style="margin-left:auto;flex-shrink:0;border-radius:9999px;background:#dbeafe;padding:1px 6px;font-size:10px;font-weight:600;color:#1d4ed8">Ada</span>`
                            : '';
                        return `<div style="display:flex;align-items:center;gap:10px;padding:4px 0">${cover}<div style="min-width:0;flex:1"><div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(item.title)}</div><div style="font-size:11px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${vol} · ${esc(item.status || '—')}</div></div>${inLib}</div>`;
                    },
                    item(item, esc) {
                        return `<div>${esc(item.title)}</div>`;
                    },
                    no_results() {
                        return `<div class="px-3 py-2 text-sm text-slate-400">Tidak ditemukan.</div>`;
                    },
                    loading() {
                        return `<div class="px-3 py-2 text-sm text-slate-400">Mencari di Jikan...</div>`;
                    },
                },
                onChange(malId) {
                    if (!malId) return;
                    app.addEntry(parseInt(malId, 10));
                    setTimeout(() => { this.clear(true); this.clearOptions(); }, 50);
                },
            });
        },

        async addEntry(malId) {
            if (this.entries.some(e => e.malId === malId)) return;

            const uid   = ++_uid;
            const entry = {
                uid, malId,
                loading:        true,
                series:         { id: '', mal_id: malId, title_romaji: 'Memuat...', title_english: '', cover_url: null, status: '', total_volumes: null },
                volumes:        [],
                selectedIds:    [],
                condition:      'good',
                is_for_loan:    true,
                purchase_price: '',
                purchase_date:  '',
                notes:          '',
            };
            this.entries.push(entry);

            try {
                const res  = await fetch(JIKAN_IMPORT_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                    body:    JSON.stringify({ mal_id: malId }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Gagal import');

                const i = this.entries.findIndex(e => e.uid === uid);
                if (i !== -1) {
                    this.entries[i].series  = data.series;
                    this.entries[i].volumes = data.volumes || [];
                    this.entries[i].loading = false;
                }
            } catch (err) {
                const i = this.entries.findIndex(e => e.uid === uid);
                if (i !== -1) {
                    this.entries[i].loading = false;
                    this.entries[i].series.title_romaji = 'Gagal memuat';
                }
                this.feedback = { message: `Import gagal: ${err.message}`, type: 'error' };
            }
        },

        removeEntry(idx) { this.entries.splice(idx, 1); },

        toggleVolume(idx, volId) {
            const id  = String(volId);
            const sel = this.entries[idx].selectedIds;
            const i   = sel.indexOf(id);
            if (i === -1) sel.push(id);
            else          sel.splice(i, 1);
        },

        selectAll(idx)   { this.entries[idx].selectedIds = this.entries[idx].volumes.map(v => String(v.id)); },
        deselectAll(idx) { this.entries[idx].selectedIds = []; },

        async submitAll() {
            if (!this.userId || this.totalVolumes === 0) return;

            const validEntries = this.entries.filter(e => e.series.id && e.selectedIds.length > 0);
            if (!validEntries.length) {
                this.feedback = { message: 'Pilih setidaknya 1 volume.', type: 'error' };
                return;
            }

            this.submitting = true;
            this.feedback   = { message: '', type: '' };

            try {
                const res  = await fetch(BULK_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                    body:    JSON.stringify({
                        user_id: this.userId,
                        entries: validEntries.map(e => ({
                            series_id:      e.series.id,
                            volume_ids:     e.selectedIds,
                            condition:      e.condition,
                            is_for_loan:    e.is_for_loan,
                            purchase_price: e.purchase_price || null,
                            purchase_date:  e.purchase_date  || null,
                            notes:          e.notes          || null,
                        })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Terjadi kesalahan.');
                this.feedback = { message: data.message, type: 'success' };
                setTimeout(() => { window.location.href = INDEX_URL; }, 1200);
            } catch (err) {
                this.feedback = { message: err.message, type: 'error' };
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush

@endsection
