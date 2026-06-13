@extends('admin.layouts.app')
@section('title', 'Buat Peminjaman')
@section('heading', 'Buat Peminjaman')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.loans.index') }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar peminjaman
    </a>
</div>

<div class="max-w-2xl">
    <div class="space-y-6 rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        {{-- Step 1 --}}
        <div>
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-zinc-500">Langkah 1</p>
            <h3 class="mb-4 text-sm font-semibold text-slate-700 dark:text-zinc-200">Pilih Pemilik Koleksi</h3>
            <form method="GET" action="{{ route('admin.loans.create') }}" class="flex gap-2">
                <select name="user_id"
                        class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Pilih user</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ optional($selectedUser)->id === $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 dark:bg-zinc-600 dark:hover:bg-zinc-500">
                    Load
                </button>
            </form>
        </div>

        @if($selectedUser)
        <div class="border-t border-slate-100 pt-6 dark:border-zinc-800">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-zinc-500">Langkah 2</p>
            <h3 class="mb-4 text-sm font-semibold text-slate-700 dark:text-zinc-200">Detail Peminjaman</h3>

            @if($availableCollections->isEmpty())
            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-400 mb-1">Tidak ada volume tersedia</p>
                <p class="text-xs text-amber-600 dark:text-amber-500">
                    {{ $selectedUser->name }} tidak punya volume yang ditandai sebagai "bisa dipinjam",
                    atau semua volume sedang dipinjam.
                </p>
            </div>
            @else
            @if($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif
            <form method="POST" action="{{ route('admin.loans.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">
                            Nama Peminjam <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="borrower_name" value="{{ old('borrower_name') }}" required
                               class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Kontak Peminjam</label>
                        <input type="text" name="borrower_contact" value="{{ old('borrower_contact') }}"
                               placeholder="No. HP / email / dll"
                               class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">
                            Tanggal Pinjam <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="loan_date" value="{{ old('loan_date', date('Y-m-d')) }}" required
                               class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Jatuh Tempo</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                               class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-zinc-200">
                            Volume yang Dipinjam <span class="text-red-500">*</span>
                        </label>
                        <div class="max-h-52 overflow-y-auto rounded-sm border border-gray-200 divide-y divide-gray-200 dark:border-gray-800 dark:divide-gray-200dark">
                            @foreach($availableCollections as $col)
                            <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800">
                                <input type="checkbox" name="collection_ids[]" value="{{ $col->id }}"
                                       {{ in_array($col->id, old('collection_ids', [])) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="flex-1 text-sm">
                                    <span class="font-medium text-slate-700 dark:text-zinc-200">{{ $col->userLibrary->series->title_romaji }}</span>
                                    <span class="ml-1.5 text-slate-400 dark:text-zinc-500">Vol {{ $col->volume->volume_number }}</span>
                                </span>
                                <span class="text-xs capitalize text-slate-400 dark:text-zinc-500">{{ $col->condition }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Catatan</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex gap-3 border-t border-slate-100 pt-4 dark:border-zinc-800">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Buat Peminjaman
                    </button>
                    <a href="{{ route('admin.loans.index') }}"
                       class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        Batal
                    </a>
                </div>
            </form>
            @endif
        </div>
        @endif

    </div>
</div>

@endsection
