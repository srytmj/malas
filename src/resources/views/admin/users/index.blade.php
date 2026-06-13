@extends('admin.layouts.app')
@section('title', 'Users')
@section('heading', 'Users')

@section('content')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-2">
        <input type="text" id="dt-search" placeholder="Cari nama / email..."
               class="w-52 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
        <select id="dt-role"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            <option value="">Semua Role</option>
            <option value="user">User</option>
            <option value="super_admin">Super Admin</option>
        </select>
        <select id="dt-status"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            <option value="">Semua Status</option>
            <option value="banned">Banned</option>
            <option value="deleted">Deleted</option>
        </select>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
        Tambah User
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="users-tbody" class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between text-sm text-slate-500 dark:text-zinc-400">
    <span id="dt-info"></span>
    <div class="flex items-center gap-1" id="dt-pages"></div>
</div>

{{-- Ban modal --}}
<div id="banModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-zinc-50">Ban User</h3>
        <form id="banForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Alasan <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required minlength="5" placeholder="Masukkan alasan ban..."
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-400/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-zinc-800">
                <button type="button" onclick="closeBanModal()"
                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                    Ban User
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const DT_URL = '{{ route('admin.users.index') }}';
let dtState  = {draw: 0, start: 0, length: 25, search: '', role: '', status: '', total: 0, filtered: 0};
let dtTimer  = null;

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderRow(u) {
    const opacity    = u.trashed ? 'opacity-60' : '';
    const roleClass  = u.role === 'super_admin'
        ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400'
        : 'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300';
    const roleLabel  = u.role === 'super_admin' ? 'Super Admin' : 'User';
    const statusBadge = u.trashed
        ? '<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-200 text-slate-600 dark:bg-zinc-700 dark:text-zinc-400">Deleted</span>'
        : u.is_banned
            ? '<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">Banned</span>'
            : '<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Aktif</span>';

    let actions = `<a href="${esc(u.show_url)}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Detail</a>`;
    if (u.unban_url) {
        actions += `<form method="POST" action="${esc(u.unban_url)}" class="inline">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <button class="ml-3 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Unban</button></form>`;
    } else if (u.ban_url) {
        actions += `<button onclick="openBanModal('${esc(u.id)}','${esc(u.ban_url)}')"
                    class="ml-3 text-xs font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400">Ban</button>`;
    }
    if (u.delete_url) {
        actions += `<form method="POST" action="${esc(u.delete_url)}" class="inline" onsubmit="return confirm('Hapus user ini?')">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="reason" value="Dihapus oleh admin">
            <button class="ml-3 text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Hapus</button></form>`;
    }

    return `<tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5 ${opacity}">
        <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-zinc-200">${esc(u.name)}</td>
        <td class="px-4 py-3 text-sm text-slate-500 dark:text-zinc-400">${esc(u.email)}</td>
        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${roleClass}">${roleLabel}</span></td>
        <td class="px-4 py-3">${statusBadge}</td>
        <td class="px-4 py-3"><div class="flex items-center justify-end">${actions}</div></td>
    </tr>`;
}

function renderPagination() {
    const info  = document.getElementById('dt-info');
    const pages = document.getElementById('dt-pages');
    const from  = dtState.filtered === 0 ? 0 : dtState.start + 1;
    const to    = Math.min(dtState.start + dtState.length, dtState.filtered);
    info.textContent = `Menampilkan ${from}–${to} dari ${dtState.filtered.toLocaleString('id-ID')} entri` +
        (dtState.filtered !== dtState.total ? ` (difilter dari ${dtState.total.toLocaleString('id-ID')})` : '');

    const total = Math.ceil(dtState.filtered / dtState.length);
    const cur   = Math.floor(dtState.start / dtState.length);
    const btn   = (a) => `px-3 py-1 rounded-md text-xs font-medium transition-colors ${a ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'}`;
    let html = '';
    if (cur > 0) html += `<button onclick="gotoPage(${cur-1})" class="${btn(false)}">Prev</button>`;
    for (let p = Math.max(0,cur-2); p <= Math.min(total-1,cur+2); p++)
        html += `<button onclick="gotoPage(${p})" class="${btn(p===cur)}">${p+1}</button>`;
    if (cur < total-1) html += `<button onclick="gotoPage(${cur+1})" class="${btn(false)}">Next</button>`;
    pages.innerHTML = html;
}

function gotoPage(p) { dtState.start = p * dtState.length; loadData(); }

async function loadData() {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Memuat...</td></tr>`;
    const params = new URLSearchParams({
        draw: ++dtState.draw, start: dtState.start, length: dtState.length,
        'search[value]': dtState.search, role: dtState.role, status: dtState.status,
        'order[0][column]': 0, 'order[0][dir]': 'asc',
    });
    try {
        const res  = await fetch(`${DT_URL}?${params}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const json = await res.json();
        dtState.total    = json.recordsTotal;
        dtState.filtered = json.recordsFiltered;
        tbody.innerHTML  = json.data.length
            ? json.data.map(renderRow).join('')
            : `<tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Tidak ada data.</td></tr>`;
        renderPagination();
    } catch {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-sm text-red-400">Gagal memuat data.</td></tr>`;
    }
}

document.getElementById('dt-search').addEventListener('input', e => {
    clearTimeout(dtTimer);
    dtTimer = setTimeout(() => { dtState.search = e.target.value; dtState.start = 0; loadData(); }, 350);
});
['dt-role','dt-status'].forEach(id => {
    document.getElementById(id).addEventListener('change', e => {
        if (id === 'dt-role')   dtState.role   = e.target.value;
        if (id === 'dt-status') dtState.status = e.target.value;
        dtState.start = 0; loadData();
    });
});

function openBanModal(userId, url) {
    document.getElementById('banForm').action = url;
    document.getElementById('banModal').classList.remove('hidden');
}
function closeBanModal() { document.getElementById('banModal').classList.add('hidden'); }

loadData();
</script>
@endpush

@endsection
