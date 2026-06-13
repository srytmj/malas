@extends('admin.layouts.app')
@section('title', 'Jikan Scraper')
@section('heading', 'Jikan Scraper')

@section('content')

{{-- Header bar --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500 dark:text-zinc-400">
        Total series: <span class="font-semibold text-slate-800 dark:text-zinc-200" id="total-count">{{ number_format($totalSeries) }}</span>
        @if($queuedCount > 0)
        <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
            {{ $queuedCount }} antri
        </span>
        @endif
    </p>
    <div class="flex gap-2">
        @if($active)
        <form method="POST" action="{{ route('admin.jikan.cancel', $active) }}">
            @csrf
            <button class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm transition-colors hover:bg-red-50 dark:border-red-700 dark:bg-gray-900 dark:text-red-400 dark:hover:bg-red-950/30">
                Batalkan Scrape
            </button>
        </form>
        @else
        <button onclick="document.getElementById('scrape-now-modal').classList.remove('hidden')"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
            Scrap Now
        </button>
        @endif
    </div>
</div>

{{-- Active session progress --}}
<div id="active-session" class="{{ $active ? '' : 'hidden' }} mb-6 rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <span id="status-dot" class="inline-block h-2.5 w-2.5 animate-pulse rounded-full bg-indigo-500"></span>
            <span class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Scrape Berjalan</span>
        </div>
        <span class="text-xs text-slate-400 dark:text-zinc-500">
            Mulai: <span id="started-at">{{ $active?->started_at?->format('H:i:s') ?? '—' }}</span>
        </span>
    </div>

    <div class="mb-4">
        <div class="mb-1 flex justify-between text-xs text-slate-500 dark:text-zinc-400">
            <span>Halaman <span id="current-page">{{ $active?->current_page ?? 0 }}</span> / <span id="total-pages">{{ $active?->total_pages ?? '?' }}</span></span>
            <span id="percent-text">{{ $active?->progressPercent() ?? 0 }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-zinc-700">
            <div id="progress-bar"
                 class="h-2 rounded-full bg-indigo-500 transition-all duration-500"
                 style="width: {{ $active?->progressPercent() ?? 0 }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <p class="mb-0.5 text-xs text-slate-400 dark:text-zinc-500">Diproses</p>
            <p class="text-xl font-bold text-slate-800 dark:text-zinc-100" id="processed-count">{{ number_format($active?->processed_count ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-center dark:border-emerald-800 dark:bg-emerald-950/30">
            <p class="mb-0.5 text-xs text-slate-400 dark:text-zinc-500">Baru</p>
            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400" id="new-count">{{ number_format($active?->new_count ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-center dark:border-indigo-800 dark:bg-indigo-950/30">
            <p class="mb-0.5 text-xs text-slate-400 dark:text-zinc-500">Diperbarui</p>
            <p class="text-xl font-bold text-indigo-700 dark:text-indigo-400" id="updated-count">{{ number_format($active?->updated_count ?? 0) }}</p>
        </div>
    </div>

    <div id="error-box" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400"></div>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

    {{-- Left: Recent series + schedule manager --}}
    <div class="space-y-5 lg:col-span-2">

        {{-- Schedule Manager --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900"
             x-data="scheduleManager()"
             x-init="initSortable()">

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Jadwal Otomatis</h3>
                <button @click="openAdd()"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    + Tambah Jadwal
                </button>
            </div>

            @if($schedules->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada jadwal. Tambah jadwal untuk scrape otomatis.</p>
            @else
            <div id="schedule-sortable" class="divide-y divide-slate-50 dark:divide-zinc-800">
                @foreach($schedules as $s)
                <div class="flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-gray-50 dark:hover:bg-white/5" data-id="{{ $s->id }}">
                    <span class="drag-handle cursor-grab select-none text-lg leading-none text-slate-300 hover:text-slate-400 dark:text-zinc-600" title="Drag to reorder">&#8942;&#8942;</span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-slate-800 dark:text-zinc-200">{{ $s->name }}</span>
                            @if($s->is_active)
                            <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Aktif</span>
                            @else
                            <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">Nonaktif</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400 dark:text-zinc-500">
                            {{ sprintf('%02d:%02d', $s->hour, $s->minute) }}
                            &bull; max {{ $s->max_pages }} hal.
                            &bull; {{ $s->yearLabel() }}
                            @if($s->last_run_at)
                            &bull; terakhir {{ $s->last_run_at->format('d M H:i') }}
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <button @click="openEdit(@js($s))"
                                class="text-xs font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">Edit</button>
                        <form method="POST" action="{{ route('admin.jikan.schedule.destroy', $s) }}"
                              onsubmit="return confirm('Hapus jadwal {{ addslashes($s->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="text-xs font-medium text-red-500 transition-colors hover:text-red-600 dark:text-red-400">Hapus</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div class="border-t border-slate-100 bg-slate-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                <p class="text-xs text-slate-400 dark:text-zinc-500">Jadwal berjalan berurutan (antri). Drag untuk ubah urutan.</p>
            </div>

            {{-- Add/Edit Schedule Modal --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6"
                 @click.self="showModal = false">
                <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="mb-5 text-base font-semibold text-slate-800 dark:text-zinc-100" x-text="editingId ? 'Edit Jadwal' : 'Tambah Jadwal'"></h3>

                    <form @submit.prevent="submitSchedule()" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Nama Jadwal <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.name" required maxlength="100" placeholder="Contoh: Manga 2012"
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Jam (0–23)</label>
                                <input type="number" x-model.number="form.hour" min="0" max="23" required
                                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Menit (0–59)</label>
                                <input type="number" x-model.number="form.minute" min="0" max="59" required
                                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Max halaman per run</label>
                            <input type="number" x-model.number="form.max_pages" min="1" max="2000" required
                                   class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <p class="mt-1 text-xs text-slate-400 dark:text-zinc-500">1 halaman = 25 manga, ~1 detik/halaman</p>
                        </div>

                        {{-- Year filter --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Filter Tahun <span class="font-normal text-slate-400 dark:text-zinc-600">(opsional)</span></label>
                            <div class="flex items-center gap-2">

                                {{-- Start year picker --}}
                                <div class="relative flex-1">
                                    <button type="button"
                                            @click="startYearOpen = !startYearOpen; endYearOpen = false"
                                            class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800">
                                        <span :class="form.start_year ? 'text-slate-800 dark:text-zinc-100' : 'text-slate-400 dark:text-zinc-500'"
                                              x-text="form.start_year || 'Dari tahun'"></span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="startYearOpen" x-cloak @click.outside="startYearOpen = false"
                                         class="absolute left-0 top-full z-50 mt-1 w-52 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
                                        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                                            <button type="button" @click="startYearDecade -= 10"
                                                    class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                  x-text="startYearDecade + '–' + (startYearDecade + 9)"></span>
                                            <button type="button" @click="startYearDecade += 10"
                                                    class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1 p-2">
                                            <template x-for="y in Array.from({length:10},(_,i)=>startYearDecade+i)" :key="y">
                                                <button type="button" @click="form.start_year = y; startYearOpen = false"
                                                        :class="form.start_year === y ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                                        class="rounded-md py-1.5 text-xs font-medium transition-colors"
                                                        x-text="y"></button>
                                            </template>
                                        </div>
                                        <div class="border-t border-slate-100 px-3 py-2 dark:border-zinc-800">
                                            <button type="button" @click="form.start_year = null; startYearOpen = false"
                                                    class="text-xs font-medium text-red-500 transition-colors hover:text-red-600 dark:text-red-400">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <span class="shrink-0 text-slate-400 dark:text-zinc-600">—</span>

                                {{-- End year picker --}}
                                <div class="relative flex-1">
                                    <button type="button"
                                            @click="endYearOpen = !endYearOpen; startYearOpen = false"
                                            class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800">
                                        <span :class="form.end_year ? 'text-slate-800 dark:text-zinc-100' : 'text-slate-400 dark:text-zinc-500'"
                                              x-text="form.end_year || 'Sampai tahun'"></span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="endYearOpen" x-cloak @click.outside="endYearOpen = false"
                                         class="absolute right-0 top-full z-50 mt-1 w-52 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
                                        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                                            <button type="button" @click="endYearDecade -= 10"
                                                    class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                  x-text="endYearDecade + '–' + (endYearDecade + 9)"></span>
                                            <button type="button" @click="endYearDecade += 10"
                                                    class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1 p-2">
                                            <template x-for="y in Array.from({length:10},(_,i)=>endYearDecade+i)" :key="y">
                                                <button type="button" @click="form.end_year = y; endYearOpen = false"
                                                        :class="form.end_year === y ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                                        class="rounded-md py-1.5 text-xs font-medium transition-colors"
                                                        x-text="y"></button>
                                            </template>
                                        </div>
                                        <div class="border-t border-slate-100 px-3 py-2 dark:border-zinc-800">
                                            <button type="button" @click="form.end_year = null; endYearOpen = false"
                                                    class="text-xs font-medium text-red-500 transition-colors hover:text-red-600 dark:text-red-400">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-400 dark:text-zinc-500">Kosongkan untuk scrape semua tahun.</p>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-zinc-200">
                            <input type="checkbox" x-model="form.is_active"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Aktifkan jadwal
                        </label>

                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-zinc-800">
                            <button type="button" @click="showModal = false"
                                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Recent series --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Series Terbaru / Diperbarui</h3>
                <span class="text-xs text-slate-400 dark:text-zinc-500" id="last-refresh">—</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
                    <thead class="bg-gray-50 dark:bg-gray-dark">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Judul</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Status</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Vol</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Skor</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Sync</th>
                        </tr>
                    </thead>
                    <tbody id="recent-series-tbody" class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-zinc-500">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Right: Session history --}}
    <div class="space-y-5">

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Riwayat Scrape</h3>
            </div>
            @forelse($sessions as $s)
            @php
            $sc = match($s->status) {
                'completed' => 'text-emerald-600 dark:text-emerald-400',
                'failed'    => 'text-red-500 dark:text-red-400',
                'running'   => 'text-indigo-500 dark:text-indigo-400',
                'queued'    => 'text-amber-500 dark:text-amber-400',
                default     => 'text-slate-400 dark:text-zinc-500',
            };
            @endphp
            <div class="border-b border-slate-50 px-5 py-3 text-xs last:border-0 dark:border-zinc-800">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium capitalize {{ $sc }}">{{ $s->status }}</span>
                    <span class="shrink-0 text-slate-400 dark:text-zinc-500">{{ $s->schedule?->name ?? $s->triggered_by }}</span>
                </div>
                <p class="mt-0.5 text-slate-400 dark:text-zinc-500">
                    {{ $s->created_at->format('d M H:i') }}
                    @if($s->status === 'completed')
                     · +{{ $s->new_count }} baru · {{ $s->updated_count }} diperbarui
                    @elseif($s->status === 'failed')
                     · {{ Str::limit($s->error_message, 40) }}
                    @elseif(in_array($s->status, ['pending', 'running']))
                     · hal {{ $s->current_page }}/{{ $s->total_pages ?: '?' }}
                    @endif
                    @if($s->start_year || $s->end_year)
                    <span class="ml-1 text-slate-300 dark:text-zinc-600">
                        ({{ $s->start_year ?? '*' }}–{{ $s->end_year ?? 'now' }})
                    </span>
                    @endif
                </p>
            </div>
            @empty
            <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada riwayat.</p>
            @endforelse
        </div>

        <div class="rounded-sm border border-gray-200 bg-gray-50 px-5 py-4 text-xs text-slate-400 dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400">
            <p class="mb-1 font-medium text-slate-600 dark:text-zinc-300">Jalankan scheduler:</p>
            <code class="font-mono text-slate-600 dark:text-zinc-300">php artisan schedule:work</code>
            <p class="mt-2">Jadwal di-check setiap menit.</p>
        </div>
    </div>
</div>

{{-- Scrap Now Modal --}}
<div id="scrape-now-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 px-4">
    <div class="w-full max-w-sm rounded-sm border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="mb-5 text-base font-semibold text-slate-800 dark:text-zinc-100">Scrap Now</h3>
        <form method="POST" action="{{ route('admin.jikan.scrape-now') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Max halaman</label>
                <input type="number" name="max_pages" value="200" min="1" max="2000"
                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-zinc-400">Filter Tahun <span class="font-normal text-slate-400 dark:text-zinc-600">(opsional)</span></label>
                <div class="flex items-center gap-2"
                     x-data="{
                        sy: null, ey: null,
                        syOpen: false, eyOpen: false,
                        syDec: {{ floor(date('Y') / 10) * 10 }},
                        eyDec: {{ floor(date('Y') / 10) * 10 }},
                     }">

                    {{-- Start year --}}
                    <div class="relative flex-1">
                        <button type="button"
                                @click="syOpen = !syOpen; eyOpen = false"
                                class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span :class="sy ? 'text-slate-800 dark:text-zinc-100' : 'text-slate-400 dark:text-zinc-500'" x-text="sy || 'Dari'"></span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <div x-show="syOpen" x-cloak @click.outside="syOpen = false"
                             class="absolute left-0 top-full z-50 mt-1 w-52 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                                <button type="button" @click="syDec -= 10" class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300" x-text="syDec + '–' + (syDec+9)"></span>
                                <button type="button" @click="syDec += 10" class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-1 p-2">
                                <template x-for="y in Array.from({length:10},(_,i)=>syDec+i)" :key="y">
                                    <button type="button" @click="sy = y; syOpen = false"
                                            :class="sy === y ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                            class="rounded-md py-1.5 text-xs font-medium transition-colors" x-text="y"></button>
                                </template>
                            </div>
                            <div class="border-t border-slate-100 px-3 py-2 dark:border-zinc-800">
                                <button type="button" @click="sy = null; syOpen = false"
                                        class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Hapus</button>
                            </div>
                        </div>
                        <input type="hidden" name="start_year" :value="sy || ''">
                    </div>

                    <span class="shrink-0 text-slate-400 dark:text-zinc-600">—</span>

                    {{-- End year --}}
                    <div class="relative flex-1">
                        <button type="button"
                                @click="eyOpen = !eyOpen; syOpen = false"
                                class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span :class="ey ? 'text-slate-800 dark:text-zinc-100' : 'text-slate-400 dark:text-zinc-500'" x-text="ey || 'Sampai'"></span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <div x-show="eyOpen" x-cloak @click.outside="eyOpen = false"
                             class="absolute right-0 top-full z-50 mt-1 w-52 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                                <button type="button" @click="eyDec -= 10" class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300" x-text="eyDec + '–' + (eyDec+9)"></span>
                                <button type="button" @click="eyDec += 10" class="rounded p-1 text-slate-500 transition-colors hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-1 p-2">
                                <template x-for="y in Array.from({length:10},(_,i)=>eyDec+i)" :key="y">
                                    <button type="button" @click="ey = y; eyOpen = false"
                                            :class="ey === y ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                            class="rounded-md py-1.5 text-xs font-medium transition-colors" x-text="y"></button>
                                </template>
                            </div>
                            <div class="border-t border-slate-100 px-3 py-2 dark:border-zinc-800">
                                <button type="button" @click="ey = null; eyOpen = false"
                                        class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Hapus</button>
                            </div>
                        </div>
                        <input type="hidden" name="end_year" :value="ey || ''">
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-400 dark:text-zinc-500">Kosongkan untuk scrape semua tahun.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-zinc-800">
                <button type="button" onclick="document.getElementById('scrape-now-modal').classList.add('hidden')"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Mulai Scrape
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const STATUS_URL  = '{{ route('admin.jikan.status') }}';
const REORDER_URL = '{{ route('admin.jikan.schedule.reorder') }}';
const CSRF_TOKEN  = document.querySelector('meta[name=csrf-token]')?.content || '';
let isActive = {{ $active ? 'true' : 'false' }};
let pollTimer = null;

function fmt(n) { return Number(n).toLocaleString('id-ID'); }

function renderTable(series) {
    const tbody = document.getElementById('recent-series-tbody');
    if (!series || !series.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-zinc-500">Belum ada data.</td></tr>';
        return;
    }
    const statusClass = {
        publishing:        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        finished:          'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300',
        on_hiatus:         'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        discontinued:      'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
        not_yet_published: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
    };
    tbody.innerHTML = series.map(s => {
        const t  = s.last_synced_at ? new Date(s.last_synced_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';
        const bc = statusClass[s.status] || 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400';
        return `<tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
            <td class="px-4 py-2.5">
                <p class="max-w-[200px] truncate text-sm font-medium text-slate-800 dark:text-zinc-200">${s.title_romaji}</p>
                ${s.title_english ? `<p class="max-w-[200px] truncate text-xs text-slate-400 dark:text-zinc-500">${s.title_english}</p>` : ''}
            </td>
            <td class="px-4 py-2.5"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${bc}">${(s.status||'').replace(/_/g,' ')}</span></td>
            <td class="px-4 py-2.5 text-center text-sm text-slate-500 dark:text-zinc-400">${s.total_volumes||'—'}</td>
            <td class="px-4 py-2.5 text-sm text-slate-500 dark:text-zinc-400">${s.score||'—'}</td>
            <td class="px-4 py-2.5 text-xs text-slate-400 dark:text-zinc-500">${t}</td>
        </tr>`;
    }).join('');
}

function updateProgress(active) {
    const section = document.getElementById('active-session');
    if (!active || !['pending','running'].includes(active.status)) {
        if (isActive) window.location.reload();
        isActive = false;
        section.classList.add('hidden');
        return;
    }
    isActive = true;
    section.classList.remove('hidden');
    const pct = active.total_pages > 0 ? Math.round(active.current_page / active.total_pages * 100) : 0;
    document.getElementById('current-page').textContent    = active.current_page;
    document.getElementById('total-pages').textContent     = active.total_pages || '?';
    document.getElementById('percent-text').textContent    = pct + '%';
    document.getElementById('progress-bar').style.width    = pct + '%';
    document.getElementById('processed-count').textContent = fmt(active.processed_count);
    document.getElementById('new-count').textContent       = fmt(active.new_count);
    document.getElementById('updated-count').textContent   = fmt(active.updated_count);
    const dot = document.getElementById('status-dot');
    dot.className = active.status === 'failed'
        ? 'inline-block h-2.5 w-2.5 rounded-full bg-red-500'
        : 'inline-block h-2.5 w-2.5 rounded-full bg-indigo-500 animate-pulse';
    const errBox = document.getElementById('error-box');
    if (active.error_message) { errBox.textContent = active.error_message; errBox.classList.remove('hidden'); }
    else errBox.classList.add('hidden');
}

async function poll() {
    try {
        const res = await fetch(STATUS_URL, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const data = await res.json();
        updateProgress(data.active);
        renderTable(data.recentSeries);
        const tc = document.getElementById('total-count');
        if (tc) tc.textContent = fmt(data.totalSeries);
        const rf = document.getElementById('last-refresh');
        if (rf) rf.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
    } catch {}
    pollTimer = setTimeout(poll, isActive ? 2000 : 10000);
}

poll();
window.addEventListener('beforeunload', () => clearTimeout(pollTimer));

function scheduleManager() {
    const curDecade = Math.floor(new Date().getFullYear() / 10) * 10;
    return {
        showModal:       false,
        editingId:       null,
        form:            {name: '', is_active: true, hour: 3, minute: 0, max_pages: 200, start_year: null, end_year: null},
        startYearOpen:   false,
        endYearOpen:     false,
        startYearDecade: curDecade,
        endYearDecade:   curDecade,

        openAdd() {
            this.editingId = null;
            this.form = {name: '', is_active: true, hour: 3, minute: 0, max_pages: 200, start_year: null, end_year: null};
            this.startYearDecade = curDecade;
            this.endYearDecade   = curDecade;
            this.startYearOpen   = false;
            this.endYearOpen     = false;
            this.showModal = true;
        },

        openEdit(schedule) {
            this.editingId = schedule.id;
            this.form = {
                name:       schedule.name,
                is_active:  schedule.is_active,
                hour:       schedule.hour,
                minute:     schedule.minute,
                max_pages:  schedule.max_pages,
                start_year: schedule.start_year || null,
                end_year:   schedule.end_year   || null,
            };
            this.startYearDecade = schedule.start_year ? Math.floor(schedule.start_year / 10) * 10 : curDecade;
            this.endYearDecade   = schedule.end_year   ? Math.floor(schedule.end_year   / 10) * 10 : curDecade;
            this.startYearOpen   = false;
            this.endYearOpen     = false;
            this.showModal = true;
        },

        async submitSchedule() {
            const url    = this.editingId ? `/admin/jikan/schedules/${this.editingId}` : '/admin/jikan/schedules';
            const method = this.editingId ? 'PATCH' : 'POST';
            const body   = new URLSearchParams({
                _token:     CSRF_TOKEN,
                name:       this.form.name,
                is_active:  this.form.is_active ? '1' : '0',
                hour:       this.form.hour,
                minute:     this.form.minute,
                max_pages:  this.form.max_pages,
                start_year: this.form.start_year || '',
                end_year:   this.form.end_year   || '',
            });
            if (method === 'PATCH') body.append('_method', 'PATCH');
            await fetch(url, {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
            window.location.reload();
        },

        initSortable() {
            const el = document.getElementById('schedule-sortable');
            if (!el || typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                handle:     '.drag-handle',
                animation:  150,
                ghostClass: 'bg-indigo-50',
                onEnd: () => {
                    const order = [...el.querySelectorAll('[data-id]')].map(el => el.dataset.id);
                    fetch(REORDER_URL, {
                        method:  'POST',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest'},
                        body:    JSON.stringify({order}),
                    });
                },
            });
        },
    };
}
</script>
@endpush

@endsection
