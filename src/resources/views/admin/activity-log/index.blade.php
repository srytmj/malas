@extends('admin.layouts.app')
@section('title', 'Activity Log')
@section('heading', 'Activity Log')

@section('content')

<div class="mb-5">
    <form method="GET" class="flex gap-2">
        <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter action..."
               class="w-56 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500">
        <button type="submit"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
            Filter
        </button>
        @if(request('action'))
        <a href="{{ route('admin.activity-log.index') }}"
           class="inline-flex items-center rounded-md border border-slate-200 px-4 py-2 text-sm text-slate-500 transition-colors hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
            Reset
        </a>
        @endif
    </form>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
            <thead class="bg-gray-50 dark:bg-gray-dark">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Waktu</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Entity</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                @forelse($logs as $log)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 dark:text-zinc-500">
                        {{ $log->created_at->format('d M Y H:i') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-zinc-300">{{ $log->user?->name ?? 'System' }}</td>
                    <td class="px-4 py-3">
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700 dark:bg-zinc-700 dark:text-zinc-300">
                            {{ $log->action }}
                        </code>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400 dark:text-zinc-500">
                        {{ $log->entity_type }}{{ $log->entity_id ? ' #'.substr($log->entity_id, 0, 8).'…' : '' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400 dark:text-zinc-500">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada log.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>

@endsection
