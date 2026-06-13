@extends('admin.layouts.app')
@section('title', 'Edit Series')
@section('heading', 'Edit Series')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.series.show', $series) }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke detail
    </a>
</div>

<div class="max-w-2xl">
    <div class="rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        <div class="mb-5 flex items-start gap-4 border-b border-slate-100 pb-5 dark:border-zinc-800">
            @if($series->cover_image_path)
            <img src="{{ Storage::url($series->cover_image_path) }}"
                 class="h-20 w-14 rounded-lg object-cover shadow">
            @endif
            <div>
                <h2 class="text-base font-semibold text-slate-800 dark:text-zinc-50">{{ $series->title_romaji }}</h2>
                @if($series->mal_id)
                <p class="mt-0.5 text-xs text-slate-400 dark:text-zinc-500">MAL ID: {{ $series->mal_id }}</p>
                @endif
            </div>
        </div>

        @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.series.update', $series) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Judul Romaji <span class="text-red-500">*</span></label>
                    <input type="text" name="title_romaji" value="{{ old('title_romaji', $series->title_romaji) }}" required
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Judul English</label>
                    <input type="text" name="title_english" value="{{ old('title_english', $series->title_english) }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Judul Japanese</label>
                    <input type="text" name="title_japanese" value="{{ old('title_japanese', $series->title_japanese) }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Status <span class="text-red-500">*</span></label>
                    <select name="status" required
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        @foreach(['publishing'=>'Publishing','finished'=>'Finished','on_hiatus'=>'On Hiatus','discontinued'=>'Discontinued','not_yet_published'=>'Not Yet Published'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('status',$series->status)===$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Total Volume</label>
                    <input type="number" name="total_volumes" value="{{ old('total_volumes', $series->total_volumes) }}" min="1"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Terbit Mulai</label>
                    <input type="date" name="published_from" value="{{ old('published_from', $series->published_from?->format('Y-m-d')) }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Terbit Selesai</label>
                    <input type="date" name="published_to" value="{{ old('published_to', $series->published_to?->format('Y-m-d')) }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Skor (0–10)</label>
                    <input type="number" name="score" value="{{ old('score', $series->score) }}" min="0" max="10" step="0.01"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Rank MAL</label>
                    <input type="number" name="rank" value="{{ old('rank', $series->rank) }}" min="1"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Synopsis</label>
                    <textarea name="synopsis" rows="4"
                              class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">{{ old('synopsis', $series->synopsis) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Ganti Cover</label>
                    <input type="file" name="cover" accept="image/*"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-600 hover:file:bg-slate-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-300">
                    <p class="mt-1 text-xs text-slate-400 dark:text-zinc-500">Kosongkan jika tidak ingin mengganti. Maks. 2 MB.</p>
                </div>
            </div>

            <div class="flex gap-3 border-t border-slate-100 pt-4 dark:border-zinc-800">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.series.show', $series) }}"
                   class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
