@extends('admin.layouts.app')
@section('title', 'Peminjaman')
@section('heading', 'Peminjaman')

@section('content')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari peminjam / owner..."
               class="w-52 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
        <select name="status"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            <option value="">Semua Status</option>
            @foreach(['active'=>'Active','overdue'=>'Overdue','returned'=>'Returned','lost'=>'Lost'] as $v=>$l)
            <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
            Filter
        </button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.loans.index') }}"
           class="inline-flex items-center rounded-md border border-slate-200 px-4 py-2 text-sm text-slate-500 transition-colors hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
            Reset
        </a>
        @endif
    </form>
    <a href="{{ route('admin.loans.create') }}"
       class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
        Buat Peminjaman
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Peminjam</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Owner</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Jatuh Tempo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                @forelse($loans as $loan)
                @php
                $badge = match($loan->status) {
                    'active'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'overdue'  => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                    'returned' => 'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300',
                    'lost'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    default    => 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400',
                };
                @endphp
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-zinc-200">{{ $loan->borrower_name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-zinc-400">{{ $loan->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500 dark:text-zinc-400">{{ $loan->items->count() }}</td>
                    <td class="px-4 py-3 text-sm {{ $loan->isOverdue() ? 'font-semibold text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-zinc-400' }}">
                        {{ $loan->due_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.loans.show', $loan) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Detail</a>
                            <a href="{{ route('admin.loans.edit', $loan) }}"
                               class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">Edit</a>
                            @if(in_array($loan->status, ['active','overdue']))
                            <form method="POST" action="{{ route('admin.loans.return', $loan) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Return</button>
                            </form>
                            <form method="POST" action="{{ route('admin.loans.lost', $loan) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400">Lost</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada peminjaman.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $loans->withQueryString()->links() }}</div>

@endsection
