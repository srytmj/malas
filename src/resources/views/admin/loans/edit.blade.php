@extends('admin.layouts.app')
@section('title', 'Edit Peminjaman')
@section('heading', 'Edit Peminjaman')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.loans.show', $loan) }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke detail
    </a>
</div>

<div class="max-w-md">
    <div class="rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        <div class="mb-5 rounded-md border border-slate-100 bg-slate-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-slate-400 dark:text-zinc-500">Owner</p>
            <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $loan->user?->name }}</p>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-zinc-500">{{ $loan->items->count() }} volume dipinjam</p>
        </div>

        @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.loans.update', $loan) }}" class="space-y-4">
            @csrf @method('PATCH')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">
                    Nama Peminjam <span class="text-red-500">*</span>
                </label>
                <input type="text" name="borrower_name" value="{{ old('borrower_name', $loan->borrower_name) }}" required
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Kontak Peminjam</label>
                <input type="text" name="borrower_contact" value="{{ old('borrower_contact', $loan->borrower_contact) }}"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Jatuh Tempo</label>
                <input type="date" name="due_date" value="{{ old('due_date', $loan->due_date?->format('Y-m-d')) }}"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Catatan</label>
                <textarea name="notes" rows="3"
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">{{ old('notes', $loan->notes) }}</textarea>
            </div>

            <div class="flex gap-3 border-t border-slate-100 pt-4 dark:border-zinc-800">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Simpan
                </button>
                <a href="{{ route('admin.loans.show', $loan) }}"
                   class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
