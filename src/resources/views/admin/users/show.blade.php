@extends('admin.layouts.app')
@section('title', $user->name)
@section('heading', 'Detail User')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.users.index') }}"
       class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        ← Kembali ke daftar users
    </a>
</div>

<div class="max-w-2xl space-y-5">

    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-zinc-50">{{ $user->name }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-zinc-400">{{ $user->email }}</p>
            </div>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                {{ $user->isSuperAdmin()
                    ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400'
                    : 'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                {{ $user->isSuperAdmin() ? 'Super Admin' : 'User' }}
            </span>
        </div>

        <p class="text-sm text-slate-500 dark:text-zinc-400">
            Terdaftar: <span class="font-medium text-slate-700 dark:text-zinc-200">{{ $user->created_at->format('d M Y') }}</span>
        </p>

        @if($user->is_banned)
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
            <p class="text-sm font-semibold text-red-700 dark:text-red-400">Akun diblokir</p>
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $user->ban_reason }}</p>
            <p class="mt-1 text-xs text-red-400 dark:text-red-500">{{ $user->banned_at?->format('d M Y H:i') }}</p>
        </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">
                Library
                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">
                    {{ $user->library->count() }} series
                </span>
            </h3>
        </div>
        @forelse($user->library as $lib)
        <div class="flex items-center justify-between border-b border-slate-50 px-5 py-3 last:border-0 dark:border-zinc-800">
            <p class="text-sm font-medium text-slate-700 dark:text-zinc-200">{{ $lib->series?->title_romaji }}</p>
            <p class="text-xs text-slate-400 dark:text-zinc-500">{{ $lib->collections->count() }} vol dimiliki</p>
        </div>
        @empty
        <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-zinc-500">Library kosong.</p>
        @endforelse
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Peminjaman Terbaru</h3>
        </div>
        @forelse($user->loans as $loan)
        <div class="flex items-center justify-between border-b border-slate-50 px-5 py-3 last:border-0 dark:border-zinc-800">
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-zinc-200">{{ $loan->borrower_name }}</p>
                <p class="text-xs text-slate-400 dark:text-zinc-500">{{ $loan->items->count() }} item</p>
            </div>
            <a href="{{ route('admin.loans.show', $loan) }}"
               class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Detail</a>
        </div>
        @empty
        <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada peminjaman.</p>
        @endforelse
    </div>

</div>

@endsection
