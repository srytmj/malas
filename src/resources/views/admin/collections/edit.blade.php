@extends('admin.layouts.app')
@section('title', 'Edit Koleksi')
@section('heading', 'Edit Koleksi')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.collections.show', $collection) }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke detail
    </a>
</div>

<div class="max-w-md">
    <div class="rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        <div class="mb-5 rounded-md border border-slate-100 bg-slate-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <span class="text-xs text-slate-400 dark:text-zinc-500">User</span>
                    <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $collection->userLibrary->user->name }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 dark:text-zinc-500">Volume</span>
                    <p class="font-medium text-slate-800 dark:text-zinc-200">Vol {{ $collection->volume->volume_number }}</p>
                </div>
                <div class="col-span-2">
                    <span class="text-xs text-slate-400 dark:text-zinc-500">Series</span>
                    <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $collection->userLibrary->series->title_romaji }}</p>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.collections.update', $collection) }}" class="space-y-4">
            @csrf @method('PATCH')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Kondisi <span class="text-red-500">*</span></label>
                <select name="condition" required
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    @foreach(['mint'=>'Mint','very_good'=>'Very Good','good'=>'Good','fair'=>'Fair','poor'=>'Poor'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('condition',$collection->condition)===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-zinc-200">
                    <input type="checkbox" name="is_for_loan" value="1"
                           {{ old('is_for_loan', $collection->is_for_loan) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Bisa dipinjamkan
                </label>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Harga Beli</label>
                <input type="number" name="purchase_price"
                       value="{{ old('purchase_price', $collection->purchase_price) }}" min="0" step="0.01"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Tanggal Beli</label>
                <input type="date" name="purchase_date"
                       value="{{ old('purchase_date', $collection->purchase_date?->format('Y-m-d')) }}"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Catatan</label>
                <textarea name="notes" rows="3"
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">{{ old('notes', $collection->notes) }}</textarea>
            </div>

            <div class="flex gap-3 border-t border-slate-100 pt-4 dark:border-zinc-800">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Simpan
                </button>
                <a href="{{ route('admin.collections.show', $collection) }}"
                   class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
