@extends('admin.layouts.app')
@section('title', "Edit Volume {$volume->volume_number}")
@section('heading', 'Edit Volume')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.volumes.index', $series) }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar volume
    </a>
</div>

<div class="max-w-lg space-y-5">

    {{-- Series label --}}
    <div class="rounded-sm border border-gray-200 bg-white px-5 py-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-0.5 text-xs text-slate-400 dark:text-zinc-500">Series</p>
        <p class="font-semibold text-slate-800 dark:text-zinc-100">{{ $series->title_romaji }}</p>
    </div>

    {{-- Edit form --}}
    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="mb-4 text-sm font-semibold text-slate-700 dark:text-zinc-200">Detail Volume</h3>

        @if($errors->any())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.volumes.update', [$series, $volume]) }}" class="space-y-4">
            @csrf @method('PATCH')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">
                        Nomor Volume <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="volume_number" step="0.5" min="0" required
                           value="{{ old('volume_number', $volume->volume_number) }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Judul</label>
                    <input type="text" name="title" value="{{ old('title', $volume->title) }}"
                           placeholder="Opsional"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">ISBN</label>
                    <input type="text" name="isbn" value="{{ old('isbn', $volume->isbn) }}"
                           placeholder="978-..."
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Penerbit</label>
                    <input type="text" name="publisher" value="{{ old('publisher', $volume->publisher) }}"
                           placeholder="Opsional"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
            </div>

            <div class="max-w-xs">
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Tanggal Terbit</label>
                <input type="date" name="release_date"
                       value="{{ old('release_date', $volume->release_date?->format('Y-m-d')) }}"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.volumes.index', $series) }}"
                   class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                    Batal
                </a>
            </div>
        </form>
    </div>

    {{-- Danger zone --}}
    <div class="rounded-lg border border-red-200 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30">
        <h3 class="mb-1 text-sm font-semibold text-red-700 dark:text-red-400">Hapus Volume</h3>
        <p class="mb-4 text-xs text-red-500 dark:text-red-500">Tindakan ini tidak bisa dibatalkan dan akan menghapus data koleksi terkait.</p>
        <form method="POST" action="{{ route('admin.volumes.destroy', [$series, $volume]) }}"
              onsubmit="return confirm('Hapus Volume {{ $volume->volume_number }}? Tindakan ini tidak dapat dibatalkan.')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-red-700">
                Hapus Volume {{ $volume->volume_number }}
            </button>
        </form>
    </div>

</div>
@endsection
