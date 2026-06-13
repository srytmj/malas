@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

@php
$cards = [
    ['label' => 'Total Series',     'value' => $stats['series'],       'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'accent' => 'text-indigo-600 dark:text-indigo-400', 'ring' => 'bg-indigo-50 dark:bg-indigo-900/20'],
    ['label' => 'Koleksi Volume',   'value' => $stats['collections'],  'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'accent' => 'text-emerald-600 dark:text-emerald-400', 'ring' => 'bg-emerald-50 dark:bg-emerald-900/20'],
    ['label' => 'Library Entries',  'value' => $stats['libraries'],    'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'accent' => 'text-sky-600 dark:text-sky-400', 'ring' => 'bg-sky-50 dark:bg-sky-900/20'],
    ['label' => 'Active Loans',     'value' => $stats['active_loans'], 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', 'accent' => $stats['active_loans'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-gray-400', 'ring' => $stats['active_loans'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-slate-50 dark:bg-gray-800/20'],
    ['label' => 'Users',            'value' => $stats['users'],        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'accent' => 'text-purple-600 dark:text-purple-400', 'ring' => 'bg-purple-50 dark:bg-purple-900/20', 'sub' => $stats['banned'] > 0 ? $stats['banned'].' diblokir' : null],
];
@endphp

{{-- Stat Cards --}}
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    @foreach($cards as $card)
    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium text-slate-500 dark:text-gray-400">{{ $card['label'] }}</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $card['ring'] }}">
                <svg class="h-4 w-4 {{ $card['accent'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold tabular-nums {{ $card['accent'] }}">{{ number_format($card['value']) }}</p>
        @if(!empty($card['sub']))
        <p class="mt-1 text-xs text-red-500 dark:text-red-400">{{ $card['sub'] }}</p>
        @endif
    </div>
    @endforeach
</div>

{{-- Recent Loans --}}
<div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Peminjaman Terbaru</h3>
        <a href="{{ route('admin.loans.index') }}"
           class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
            Lihat semua →
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Peminjam</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Owner</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Jatuh Tempo</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                @forelse($recentLoans as $loan)
                @php
                $badge = match($loan->status) {
                    'active'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'overdue'  => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                    'returned' => 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                    'lost'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    default    => 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400',
                };
                @endphp
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-white">{{ $loan->borrower_name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-gray-400">{{ $loan->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-gray-400">{{ $loan->due_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.loans.show', $loan) }}"
                           class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400 dark:text-gray-400">Belum ada peminjaman.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
