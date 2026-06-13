@extends('admin.layouts.app')
@section('title', 'Detail Peminjaman')
@section('heading', 'Detail Peminjaman')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.loans.index') }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar peminjaman
    </a>
</div>

@php
$badge = match($loan->status) {
    'active'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
    'overdue'  => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    'returned' => 'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300',
    'lost'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
    default    => 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400',
};
@endphp

<div class="max-w-2xl space-y-5">

    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-zinc-50">{{ $loan->borrower_name }}</h2>
                @if($loan->borrower_contact)
                <p class="mt-0.5 text-sm text-slate-400 dark:text-zinc-500">{{ $loan->borrower_contact }}</p>
                @endif
            </div>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">
                {{ ucfirst($loan->status) }}
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Owner</dt>
                <dd class="font-medium text-slate-700 dark:text-zinc-200">{{ $loan->user?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Tanggal Pinjam</dt>
                <dd class="font-medium text-slate-700 dark:text-zinc-200">{{ $loan->loan_date?->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Jatuh Tempo</dt>
                <dd class="font-medium {{ $loan->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-zinc-200' }}">
                    {{ $loan->due_date?->format('d M Y') ?? '—' }}
                </dd>
            </div>
            @if($loan->return_date)
            <div>
                <dt class="text-xs text-slate-400 dark:text-zinc-500">Dikembalikan</dt>
                <dd class="font-medium text-slate-700 dark:text-zinc-200">{{ $loan->return_date->format('d M Y') }}</dd>
            </div>
            @endif
        </dl>

        @if($loan->notes)
        <p class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-500 dark:border-zinc-800 dark:text-zinc-400">{{ $loan->notes }}</p>
        @endif

        <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-5 dark:border-zinc-800">
            <a href="{{ route('admin.loans.edit', $loan) }}"
               class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                Edit
            </a>
            @if(in_array($loan->status, ['active','overdue']))
            <form method="POST" action="{{ route('admin.loans.return', $loan) }}">
                @csrf @method('PATCH')
                <button class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700">
                    Tandai Dikembalikan
                </button>
            </form>
            <form method="POST" action="{{ route('admin.loans.lost', $loan) }}">
                @csrf @method('PATCH')
                <button class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-amber-700">
                    Tandai Hilang
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">
                Item
                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">
                    {{ $loan->items->count() }}
                </span>
            </h3>
        </div>
        @foreach($loan->items as $item)
        <div class="flex items-center justify-between border-b border-slate-50 px-5 py-3 last:border-0 dark:border-zinc-800">
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-zinc-200">
                    {{ $item->userCollection?->volume?->series?->title_romaji ?? '—' }}
                </p>
                <p class="text-xs text-slate-400 dark:text-zinc-500">
                    Vol {{ $item->userCollection?->volume?->volume_number }}
                </p>
            </div>
            <span class="text-xs font-medium {{ $item->isReturned()
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-amber-600 dark:text-amber-400' }}">
                {{ $item->isReturned() ? 'Dikembalikan '.$item->returned_at?->format('d M Y') : 'Sedang dipinjam' }}
            </span>
        </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Log Event</h3>
        </div>
        @forelse($loan->events as $event)
        <div class="flex items-center justify-between border-b border-slate-50 px-5 py-3 last:border-0 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $event->event_type }}</span>
                @if($event->metadata['note'] ?? null)
                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ $event->metadata['note'] }}</span>
                @endif
            </div>
            <span class="text-xs text-slate-400 dark:text-zinc-500">{{ $event->created_at->format('d M Y H:i') }}</span>
        </div>
        @empty
        <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada event.</p>
        @endforelse
    </div>

</div>

@endsection
