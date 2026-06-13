@extends('admin.layouts.app')
@section('title', 'Koleksi')
@section('heading', 'Koleksi')

@section('content')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari user / series..."
               class="w-56 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
        <button type="submit"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
            Cari
        </button>
        @if(request('search'))
        <a href="{{ route('admin.collections.index') }}"
           class="inline-flex items-center rounded-md border border-slate-200 px-4 py-2 text-sm text-slate-500 transition-colors hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
            Reset
        </a>
        @endif
    </form>
    <a href="{{ route('admin.collections.create') }}"
       class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
        Tambah Koleksi
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Series</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Volume</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Kondisi</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Dipinjamkan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                @forelse($collections as $col)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5 {{ $col->trashed() ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-zinc-300">{{ $col->userLibrary?->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-zinc-200">{{ $col->userLibrary?->series?->title_romaji ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-zinc-400">Vol {{ $col->volume?->volume_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm capitalize text-slate-500 dark:text-zinc-400">{{ str_replace('_', ' ', $col->condition) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $col->is_for_loan
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                : 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                            {{ $col->is_for_loan ? 'Ya' : 'Tidak' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.collections.show', $col) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Detail</a>
                            @if(!$col->trashed())
                            <a href="{{ route('admin.collections.edit', $col) }}"
                               class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">Edit</a>
                            <form method="POST" action="{{ route('admin.collections.destroy', $col) }}"
                                  onsubmit="return confirm('Hapus koleksi ini?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="reason" value="Dihapus oleh admin">
                                <button class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada koleksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $collections->withQueryString()->links() }}</div>

@endsection
