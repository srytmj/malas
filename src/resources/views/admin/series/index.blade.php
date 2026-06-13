@extends('admin.layouts.app')
@section('title', 'Series')
@section('heading', 'Series')

@section('content')

{{-- Batch action bar --}}
<div id="batch-bar" class="mb-4 hidden items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 dark:border-indigo-800 dark:bg-indigo-950/50">
    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
        <span id="batch-count">0</span> series dipilih
    </span>
    <div class="flex items-center gap-2">
        <button id="btn-batch-clear" type="button"
                class="rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-100 dark:border-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-900">
            Batal Pilih
        </button>
        <button id="btn-batch-delete" type="button"
                class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700">
            Hapus Terpilih
        </button>
    </div>
</div>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
        <input type="text" id="dt-search" placeholder="Cari judul, MAL ID..."
               class="w-60 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
        <select id="dt-status"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            <option value="">Semua Status</option>
            <option value="publishing">Publishing</option>
            <option value="finished">Finished</option>
            <option value="on_hiatus">On Hiatus</option>
            <option value="discontinued">Discontinued</option>
            <option value="not_yet_published">Not Yet Published</option>
        </select>
        <div class="flex items-center gap-1.5">
            <label for="dt-length" class="text-xs text-slate-500 dark:text-zinc-400">Tampilkan</label>
            <select id="dt-length"
                    class="rounded-md border border-slate-300 bg-white px-2 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button id="btn-destroy-all" type="button"
                class="inline-flex items-center rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950/40">
            Hapus Semua
        </button>
        <a href="{{ route('admin.series.create') }}"
           class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
            Tambah Series
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table id="series-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="w-8 px-4 py-3">
                        <input type="checkbox" id="select-all-cb"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               title="Pilih semua">
                    </th>
                    <th class="w-10 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Cover</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Judul</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Status</th>
                    <th class="w-16 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Vol</th>
                    <th class="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Skor</th>
                    <th class="w-24 px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900" id="series-tbody">
                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between text-sm text-slate-500 dark:text-zinc-400" id="dt-pagination-wrap">
    <span id="dt-info"></span>
    <div class="flex items-center gap-1" id="dt-pages"></div>
</div>

@push('scripts')
<script>
const DT_URL            = '{{ route('admin.series.index') }}';
const BATCH_DESTROY_URL = '{{ route('admin.series.batch-destroy') }}';
const DESTROY_ALL_URL   = '{{ route('admin.series.destroy-all') }}';
const CSRF_TOKEN        = document.querySelector('meta[name=csrf-token]')?.content || '';

let dtState = {draw: 0, start: 0, length: 25, search: '', status: '', order: [{column: 1, dir: 'asc'}], total: 0, filtered: 0};
let dtTimer = null;
const selectedIds = new Set();

// SweetAlert2 helper — auto dark mode
function swal(opts) {
    const dark = document.documentElement.classList.contains('dark');
    return Swal.fire({
        confirmButtonColor: '#4f46e5',
        ...(dark ? {
            background: '#18181b',
            color:      '#fafafa',
            customClass: { cancelButton: 'swal-cancel-dark' },
        } : {}),
        ...opts,
    });
}

// Shared confirm + reason flow
async function confirmWithReason(confirmOpts, count) {
    const { isConfirmed } = await swal({
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        ...confirmOpts,
    });
    if (!isConfirmed) return null;

    const { value: reason } = await swal({
        title: 'Alasan penghapusan',
        html: '<p class="text-sm opacity-60 mb-3">Wajib diisi, minimal 5 karakter</p>',
        input: 'text',
        inputPlaceholder: 'Contoh: Data duplikat dari Jikan',
        showCancelButton: true,
        confirmButtonText: 'Hapus Sekarang',
        cancelButtonText: 'Batal',
        inputValidator: v => (!v || v.trim().length < 5) ? 'Alasan minimal 5 karakter.' : null,
    });
    return reason?.trim() ?? null;
}

function updateBatchBar() {
    const bar   = document.getElementById('batch-bar');
    const cnt   = document.getElementById('batch-count');
    const hdrCb = document.getElementById('select-all-cb');
    if (selectedIds.size > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
        cnt.textContent = selectedIds.size;
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }
    const rowCbs     = [...document.querySelectorAll('.row-check')];
    const checkedCnt = rowCbs.filter(cb => cb.checked).length;
    if (hdrCb && rowCbs.length > 0) {
        hdrCb.checked       = checkedCnt === rowCbs.length;
        hdrCb.indeterminate = checkedCnt > 0 && checkedCnt < rowCbs.length;
    }
}

function toggleSelect(cb) {
    if (cb.checked) selectedIds.add(cb.dataset.id);
    else            selectedIds.delete(cb.dataset.id);
    updateBatchBar();
}

function statusBadge(status) {
    const map = {
        publishing:        'bg-emerald-100 text-emerald-700',
        finished:          'bg-slate-100 text-slate-600',
        on_hiatus:         'bg-amber-100 text-amber-700',
        discontinued:      'bg-red-100 text-red-600',
        not_yet_published: 'bg-purple-100 text-purple-700',
    };
    const cls   = map[status] || 'bg-slate-100 text-slate-500';
    const label = (status || '').replace(/_/g, ' ');
    return `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${cls}">${label}</span>`;
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderRow(s) {
    const checked = selectedIds.has(s.id) ? 'checked' : '';
    const cover   = s.cover_url
        ? `<img src="${escHtml(s.cover_url)}" class="h-12 w-8 rounded object-cover shadow-sm">`
        : `<div class="h-12 w-8 rounded bg-slate-100 dark:bg-zinc-700"></div>`;
    const title   = `<p class="text-sm font-medium text-slate-800 dark:text-zinc-200">${escHtml(s.title_romaji)}</p>
        ${s.title_english ? `<p class="text-xs text-slate-400 dark:text-zinc-500">${escHtml(s.title_english)}</p>` : ''}
        ${s.mal_id ? `<p class="text-xs text-slate-300 dark:text-zinc-600">MAL #${s.mal_id}</p>` : ''}`;
    const actions = `<div class="flex items-center justify-end gap-3">
        <a href="${escHtml(s.show_url)}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Detail</a>
        <a href="${escHtml(s.edit_url)}" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400">Edit</a>
        <form method="POST" action="${escHtml(s.delete_url)}" data-confirm="Hapus &quot;${escHtml(s.title_romaji)}&quot;?">
            <input type="hidden" name="_token" value="${escHtml(s.delete_token)}">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="reason" value="Dihapus oleh admin">
            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Hapus</button>
        </form>
    </div>`;

    return `<tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5" data-id="${escHtml(s.id)}">
        <td class="w-8 px-4 py-3">
            <input type="checkbox" class="row-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                   data-id="${escHtml(s.id)}" onchange="toggleSelect(this)" ${checked}>
        </td>
        <td class="px-4 py-3">${cover}</td>
        <td class="px-4 py-3">${title}</td>
        <td class="px-4 py-3">${statusBadge(s.status)}</td>
        <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-zinc-400">${s.total_volumes ?? '—'}</td>
        <td class="px-4 py-3 text-sm text-slate-500 dark:text-zinc-400">${s.score ?? '—'}</td>
        <td class="px-4 py-3">${actions}</td>
    </tr>`;
}

function renderPagination() {
    const info  = document.getElementById('dt-info');
    const pages = document.getElementById('dt-pages');
    const from  = dtState.total === 0 ? 0 : dtState.start + 1;
    const to    = Math.min(dtState.start + dtState.length, dtState.filtered);
    info.textContent = `Menampilkan ${from}–${to} dari ${dtState.filtered.toLocaleString('id-ID')} entri` +
        (dtState.filtered !== dtState.total ? ` (difilter dari ${dtState.total.toLocaleString('id-ID')})` : '');

    const totalPages = Math.ceil(dtState.filtered / dtState.length);
    const current    = Math.floor(dtState.start / dtState.length);
    const btn = (a) => `px-3 py-1 rounded-md text-xs font-medium transition-colors ${a ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'}`;
    let html = '';
    if (current > 0) html += `<button onclick="gotoPage(${current-1})" class="${btn(false)}">Prev</button>`;
    for (let p = Math.max(0,current-2); p <= Math.min(totalPages-1,current+2); p++)
        html += `<button onclick="gotoPage(${p})" class="${btn(p===current)}">${p+1}</button>`;
    if (current < totalPages-1) html += `<button onclick="gotoPage(${current+1})" class="${btn(false)}">Next</button>`;
    pages.innerHTML = html;
}

function gotoPage(p) { dtState.start = p * dtState.length; loadData(); }

async function loadData() {
    const tbody = document.getElementById('series-tbody');
    tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Memuat...</td></tr>`;

    const params = new URLSearchParams({
        draw: ++dtState.draw, start: dtState.start, length: dtState.length,
        'search[value]': dtState.search,
        'order[0][column]': dtState.order[0].column,
        'order[0][dir]':    dtState.order[0].dir,
    });
    if (dtState.status) params.set('status_filter', dtState.status);

    try {
        const res  = await fetch(`${DT_URL}?${params}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const json = await res.json();
        dtState.total    = json.recordsTotal;
        dtState.filtered = json.recordsFiltered;
        tbody.innerHTML  = json.data.length
            ? json.data.map(renderRow).join('')
            : `<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Tidak ada data.</td></tr>`;
        renderPagination();
        updateBatchBar();
    } catch {
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-red-400">Gagal memuat data.</td></tr>`;
    }
}

// Event delegation for form submits in tbody (single-row delete with Swal confirm)
document.getElementById('series-tbody').addEventListener('submit', function (e) {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    swal({
        title: form.dataset.confirm,
        text: 'Tindakan ini tidak bisa dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
    }).then(r => { if (r.isConfirmed) form.submit(); });
});

document.getElementById('dt-search').addEventListener('input', e => {
    clearTimeout(dtTimer);
    dtTimer = setTimeout(() => { dtState.search = e.target.value; dtState.start = 0; loadData(); }, 350);
});
document.getElementById('dt-status').addEventListener('change', e => {
    dtState.status = e.target.value; dtState.start = 0; loadData();
});
document.getElementById('dt-length').addEventListener('change', e => {
    dtState.length = parseInt(e.target.value); dtState.start = 0; loadData();
});
document.getElementById('select-all-cb').addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = this.checked;
        if (this.checked) selectedIds.add(cb.dataset.id);
        else              selectedIds.delete(cb.dataset.id);
    });
    updateBatchBar();
});
document.getElementById('btn-batch-clear').addEventListener('click', () => {
    selectedIds.clear();
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    const hdrCb = document.getElementById('select-all-cb');
    hdrCb.checked = false; hdrCb.indeterminate = false;
    updateBatchBar();
});

document.getElementById('btn-batch-delete').addEventListener('click', async () => {
    if (!selectedIds.size) return;
    const reason = await confirmWithReason({
        title: `Hapus ${selectedIds.size} series?`,
        text:  'Tindakan ini tidak bisa dibatalkan.',
    });
    if (!reason) return;
    try {
        const res  = await fetch(BATCH_DESTROY_URL, {
            method:  'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest'},
            body:    JSON.stringify({ids: [...selectedIds], reason}),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Terjadi kesalahan.');
        swal({title: 'Berhasil', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false});
        selectedIds.clear();
        updateBatchBar();
        loadData();
    } catch (err) {
        swal({title: 'Gagal', text: err.message, icon: 'error'});
    }
});

document.getElementById('btn-destroy-all').addEventListener('click', async () => {
    const isFiltered = dtState.search || dtState.status;
    const scope      = isFiltered
        ? `${dtState.filtered.toLocaleString('id-ID')} series yang sesuai filter`
        : `semua ${dtState.total.toLocaleString('id-ID')} series`;
    const reason = await confirmWithReason({
        title:             `Hapus ${scope}?`,
        html:              '<p class="text-sm opacity-60">Termasuk semua volume dan data koleksi terkait. Tidak bisa dibatalkan!</p>',
        icon:              'warning',
        confirmButtonColor: '#dc2626',
    });
    if (!reason) return;

    const btn = document.getElementById('btn-destroy-all');
    btn.disabled    = true;
    btn.textContent = 'Menghapus...';
    try {
        const res  = await fetch(DESTROY_ALL_URL, {
            method:  'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest'},
            body:    JSON.stringify({reason, search: dtState.search, status_filter: dtState.status}),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Terjadi kesalahan.');
        swal({title: 'Berhasil', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false});
        selectedIds.clear();
        updateBatchBar();
        loadData();
    } catch (err) {
        swal({title: 'Gagal', text: err.message, icon: 'error'});
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Hapus Semua';
    }
});

loadData();
</script>
@endpush

@endsection
