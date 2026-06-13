@extends('admin.layouts.app')
@section('title', 'Volume — ' . $series->title_romaji)
@section('heading', 'Kelola Volume')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.series.show', $series) }}"
       class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
        ← {{ $series->title_romaji }}
    </a>
</div>

{{-- Add volume form --}}
<div class="mb-6 rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <h3 class="mb-4 text-sm font-semibold text-slate-700 dark:text-zinc-200">Tambah Volume Baru</h3>
    <form method="POST" action="{{ route('admin.volumes.store', $series) }}" class="space-y-3">
        @csrf
        @if($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">No. Volume <span class="text-red-500">*</span></label>
                <input type="number" name="volume_number" value="{{ old('volume_number') }}" min="0.5" step="0.5" required placeholder="1"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Judul (opsional)</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="Contoh: The Beginning"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">ISBN</label>
                <input type="text" name="isbn" value="{{ old('isbn') }}" maxlength="50" placeholder="978-..."
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Tanggal Terbit</label>
                <input type="date" name="release_date" value="{{ old('release_date') }}"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
        </div>

        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Penerbit</label>
                <input type="text" name="publisher" value="{{ old('publisher') }}" maxlength="100" placeholder="Elex Media Komputindo"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div class="shrink-0">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Tambah
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Volume list --}}
@if($volumes->isEmpty())
<div class="rounded-lg border border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900">
    <p class="text-sm text-slate-400 dark:text-zinc-500">Belum ada volume untuk series ini.</p>
</div>
@else
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Vol</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Judul</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">ISBN</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Penerbit</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Terbit</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                @foreach($volumes as $volume)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="px-4 py-3">
                        <span class="inline-flex h-8 w-10 items-center justify-center rounded-md bg-indigo-50 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400">
                            {{ $volume->volume_number }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-800 dark:text-zinc-200">{{ $volume->title ?: '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-zinc-400">{{ $volume->isbn ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-zinc-400">{{ $volume->publisher ?: '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-zinc-400">
                        {{ $volume->release_date ? $volume->release_date->format('d M Y') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.volumes.edit', [$series, $volume]) }}"
                               class="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</a>
                            <form method="POST" action="{{ route('admin.volumes.destroy', [$series, $volume]) }}"
                                  onsubmit="return confirm('Hapus Volume {{ $volume->volume_number }}?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-500 transition-colors hover:text-red-600 dark:text-red-400">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
