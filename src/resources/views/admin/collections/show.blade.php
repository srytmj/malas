@extends('admin.layouts.app')
@section('title', 'Detail Koleksi')
@section('heading', 'Detail Koleksi')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.collections.index') }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar koleksi
    </a>
</div>

<div class="max-w-xl space-y-5">

    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <p class="text-base font-semibold text-slate-800 dark:text-zinc-50">{{ $collection->userLibrary?->series?->title_romaji }}</p>
                <p class="text-sm text-slate-500 dark:text-zinc-400">
                    Vol {{ $collection->volume?->volume_number }} — {{ $collection->userLibrary?->user?->name }}
                </p>
            </div>
            <a href="{{ route('admin.collections.edit', $collection) }}"
               class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                Edit
            </a>
        </div>

        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Kondisi</dt>
                <dd class="font-medium capitalize text-slate-700 dark:text-zinc-200">{{ str_replace('_', ' ', $collection->condition) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Dipinjamkan</dt>
                <dd>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $collection->is_for_loan
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                            : 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                        {{ $collection->is_for_loan ? 'Ya' : 'Tidak' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Harga Beli</dt>
                <dd class="font-medium text-slate-700 dark:text-zinc-200">
                    {{ $collection->purchase_price ? 'Rp '.number_format($collection->purchase_price,0,',','.') : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Tanggal Beli</dt>
                <dd class="font-medium text-slate-700 dark:text-zinc-200">{{ $collection->purchase_date?->format('d M Y') ?? '—' }}</dd>
            </div>
        </dl>

        @if($collection->notes)
        <div class="mt-4 border-t border-slate-100 pt-4 dark:border-zinc-800">
            <p class="mb-1 text-xs text-slate-400 dark:text-zinc-500">Catatan</p>
            <p class="text-sm text-slate-600 dark:text-zinc-300">{{ $collection->notes }}</p>
        </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Riwayat Peminjaman</h3>
        </div>
        @forelse($collection->loanItems as $item)
        <div class="flex items-center justify-between border-b border-slate-50 px-5 py-3 last:border-0 dark:border-zinc-800">
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-zinc-200">{{ $item->loan?->borrower_name }}</p>
                <p class="text-xs text-slate-400 dark:text-zinc-500">{{ $item->loan?->loan_date?->format('d M Y') }}</p>
            </div>
            <span class="text-xs font-medium {{ $item->isReturned()
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-amber-600 dark:text-amber-400' }}">
                {{ $item->isReturned() ? 'Dikembalikan' : 'Sedang dipinjam' }}
            </span>
        </div>
        @empty
        <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-zinc-500">Belum pernah dipinjamkan.</p>
        @endforelse
    </div>

</div>

@endsection
