@extends('admin.layouts.app')
@section('title', $series->title_romaji)
@section('heading', 'Detail Series')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.series.index') }}"
       class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
        Kembali
    </a>
    <a href="{{ route('admin.series.edit', $series) }}"
       class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
        Edit Series
    </a>
    <a href="{{ route('admin.volumes.index', $series) }}"
       class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
        Kelola Volume
    </a>
</div>

<div class="space-y-5 max-w-5xl">

    {{-- Series Info --}}
    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex gap-5">
            <div class="shrink-0">
                @if($series->cover_image_path)
                    <img src="{{ Storage::url($series->cover_image_path) }}"
                         class="h-44 w-28 rounded-lg object-cover shadow">
                @else
                    <div class="flex h-44 w-28 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400 dark:bg-zinc-800 dark:text-zinc-500">
                        Tanpa Cover
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold text-slate-800 dark:text-zinc-50 mb-1">{{ $series->title_romaji }}</h2>
                @if($series->title_english)
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mb-0.5">{{ $series->title_english }}</p>
                @endif
                @if($series->title_japanese)
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mb-3">{{ $series->title_japanese }}</p>
                @endif

                @php
                $statusBadge = match($series->status) {
                    'publishing'        => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'finished'          => 'bg-slate-100 text-slate-600 dark:bg-zinc-700 dark:text-zinc-300',
                    'on_hiatus'         => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    'discontinued'      => 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
                    'not_yet_published' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
                    default             => 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400',
                };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium mb-4 {{ $statusBadge }}">
                    {{ str_replace('_', ' ', ucfirst($series->status ?? '—')) }}
                </span>

                <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">MAL ID</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->mal_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">Total Volume</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->total_volumes ?? '?' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">Skor</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->score ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">Rank</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->rank ? '#'.$series->rank : '—' }}</p>
                    </div>
                    @if($series->published_from)
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">Terbit Mulai</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->published_from->format('M Y') }}</p>
                    </div>
                    @endif
                    @if($series->published_to)
                    <div>
                        <p class="text-xs text-slate-400 dark:text-zinc-500">Terbit Selesai</p>
                        <p class="font-medium text-slate-800 dark:text-zinc-200">{{ $series->published_to->format('M Y') }}</p>
                    </div>
                    @endif
                </div>

                @if($series->genres)
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($series->genres as $genre)
                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-xs text-indigo-600 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-400">
                        {{ $genre }}
                    </span>
                    @endforeach
                </div>
                @endif

                @if($series->authors)
                <p class="mt-2 text-xs text-slate-400 dark:text-zinc-500">Pengarang: {{ implode(', ', $series->authors) }}</p>
                @endif

                @if($series->synopsis)
                <p class="mt-3 text-sm text-slate-500 dark:text-zinc-400 line-clamp-3">{{ $series->synopsis }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Volume registry --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">
                Volume Terdaftar
                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-zinc-700 dark:text-zinc-400">
                    {{ $series->volumes->count() }}
                </span>
            </h3>
            <a href="{{ route('admin.volumes.index', $series) }}"
               class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                + Tambah / Kelola
            </a>
        </div>
        @if($series->volumes->isEmpty())
        <div class="px-5 py-8 text-center">
            <p class="text-sm text-slate-400 dark:text-zinc-500 mb-3">Belum ada volume fisik terdaftar.</p>
            <a href="{{ route('admin.volumes.index', $series) }}"
               class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                Tambah Volume
            </a>
        </div>
        @else
        <div class="flex flex-wrap gap-2 p-4">
            @foreach($series->volumes->sortBy('volume_number') as $volume)
            <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                Vol {{ $volume->volume_number }}
                @if($volume->title)
                <span class="ml-1.5 hidden text-xs text-slate-400 dark:text-zinc-500 sm:inline">{{ Str::limit($volume->title, 18) }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Volume management per user --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900"
         x-data="volumeManager()"
         x-init="initUserSelect()">

        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-200">Koleksi Volume per Anggota</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-zinc-500">Pilih anggota untuk mengelola koleksi volume fisik mereka.</p>
        </div>

        <div class="p-5">

            {{-- User picker --}}
            <div class="mb-5 max-w-sm">
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-zinc-200">Anggota</label>
                <select id="vm-user-picker" placeholder="Cari nama atau email...">
                    <option value="">— Pilih anggota —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Loading --}}
            <div x-show="loading" class="py-12 text-center">
                <svg class="mx-auto h-5 w-5 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="mt-2 text-xs text-slate-400 dark:text-zinc-500">Memuat data koleksi...</p>
            </div>

            {{-- Empty state (no user selected) --}}
            <div x-show="!userId && !loading"
                 class="rounded-md border border-dashed border-slate-200 py-10 text-center dark:border-zinc-700">
                <svg class="mx-auto mb-2 h-8 w-8 text-slate-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-slate-400 dark:text-zinc-500">Pilih anggota untuk melihat dan mengelola volume koleksi mereka.</p>
            </div>

            {{-- Main content (user selected) --}}
            <div x-show="userId && !loading" x-cloak>

                {{-- Summary stats --}}
                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-500 dark:text-zinc-400">
                            <span class="font-semibold text-slate-700 dark:text-zinc-200" x-text="ownedCount()"></span> dimiliki
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <span class="text-xs text-slate-500 dark:text-zinc-400">
                            <span class="font-semibold text-slate-700 dark:text-zinc-200" x-text="loanedCount()"></span> dipinjam
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-zinc-600"></span>
                        <span class="text-xs text-slate-500 dark:text-zinc-400">
                            <span class="font-semibold text-slate-700 dark:text-zinc-200" x-text="notOwnedCount()"></span> belum dimiliki
                        </span>
                    </div>
                </div>

                {{-- Feedback banner --}}
                <div x-show="feedback.message"
                     :class="feedback.type === 'error'
                         ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300'
                         : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'"
                     class="mb-4 rounded-md border px-4 py-3 text-sm"
                     x-text="feedback.message"
                     x-cloak></div>

                {{-- Volume table --}}
                <div x-show="volumes.length > 0">
                    <div class="overflow-hidden rounded-sm border border-gray-200 dark:border-gray-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-200dark">
                                <thead class="bg-gray-50 dark:bg-gray-dark">
                                    <tr>
                                        <th class="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Vol</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Judul / ISBN</th>
                                        <th class="w-44 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Status</th>
                                        <th class="w-32 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Kondisi</th>
                                        <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Dipinjamkan</th>
                                        <th class="w-20 px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, idx) in volumes" :key="v.id">
                                        <template x-if="true">
                                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-200dark dark:bg-gray-900">
                                                {{-- Main row --}}
                                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                                                    :class="v.showForm ? 'bg-indigo-50/30 dark:bg-indigo-950/10' : ''">
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex h-8 w-10 items-center justify-center rounded-md bg-indigo-50 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400"
                                                              x-text="v.volume_number"></span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <p class="text-sm font-medium text-slate-800 dark:text-zinc-200"
                                                           x-text="v.title || `Volume ${v.volume_number}`"></p>
                                                        <p x-show="v.isbn" class="mt-0.5 font-mono text-xs text-slate-400 dark:text-zinc-500" x-text="v.isbn"></p>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        {{-- Loaned: badge + disabled select --}}
                                                        <template x-if="v.status === 'loaned'">
                                                            <div class="flex flex-col gap-1">
                                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                                                    Sedang Dipinjam
                                                                </span>
                                                                <span class="text-[10px] text-slate-400 dark:text-zinc-500">Tidak dapat diubah</span>
                                                            </div>
                                                        </template>
                                                        {{-- Not loaned: editable dropdown --}}
                                                        <template x-if="v.status !== 'loaned'">
                                                            <select x-model="v.displayStatus"
                                                                    @change="handleStatusChange(idx)"
                                                                    :disabled="v.saving"
                                                                    class="w-full rounded-md border border-slate-300 bg-white py-1.5 pl-2 pr-7 text-xs text-slate-800 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                                <option value="not_owned">Tidak Dimiliki</option>
                                                                <option value="owned">Dimiliki</option>
                                                            </select>
                                                        </template>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-sm capitalize text-slate-500 dark:text-zinc-400"
                                                              x-text="v.collection?.condition?.replace('_',' ') || '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <template x-if="v.collection !== null">
                                                            <span :class="v.collection.is_for_loan
                                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                                                    : 'bg-slate-100 text-slate-500 dark:bg-zinc-700 dark:text-zinc-400'"
                                                                  class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                                  x-text="v.collection.is_for_loan ? 'Ya' : 'Tidak'"></span>
                                                        </template>
                                                        <template x-if="v.collection === null">
                                                            <span class="text-xs text-slate-300 dark:text-zinc-600">—</span>
                                                        </template>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <template x-if="v.saving">
                                                            <svg class="inline h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                            </svg>
                                                        </template>
                                                        <template x-if="!v.saving && v.status !== 'not_owned'">
                                                            <button type="button" @click="toggleForm(idx)"
                                                                    class="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                    x-text="v.showForm ? 'Tutup' : 'Detail'"></button>
                                                        </template>
                                                    </td>
                                                </tr>

                                                {{-- Inline form (add or edit) --}}
                                                <tr x-show="v.showForm">
                                                    <td colspan="6" class="bg-white px-4 pb-4 pt-0 dark:bg-gray-900">
                                                        <div class="rounded-md border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/20">
                                                            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                                                Detail Koleksi — Vol <span x-text="v.volume_number"></span>
                                                            </p>

                                                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                                <div>
                                                                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-300">
                                                                        Kondisi <span class="text-red-500">*</span>
                                                                    </label>
                                                                    <select x-model="v.form.condition"
                                                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                                        <option value="mint">Mint</option>
                                                                        <option value="very_good">Very Good</option>
                                                                        <option value="good">Good</option>
                                                                        <option value="fair">Fair</option>
                                                                        <option value="poor">Poor</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-300">Harga Beli</label>
                                                                    <input type="number" x-model="v.form.purchase_price"
                                                                           min="0" step="1000" placeholder="0"
                                                                           class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                                </div>
                                                                <div>
                                                                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-300">Tanggal Beli</label>
                                                                    <input type="date" x-model="v.form.purchase_date"
                                                                           class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                                </div>
                                                                <div class="flex items-end pb-1.5">
                                                                    <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-700 dark:text-zinc-300">
                                                                        <input type="checkbox" x-model="v.form.is_for_loan"
                                                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                                        Bisa dipinjamkan
                                                                    </label>
                                                                </div>
                                                                <div class="sm:col-span-4">
                                                                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-zinc-300">Catatan</label>
                                                                    <input type="text" x-model="v.form.notes"
                                                                           placeholder="Opsional..."
                                                                           class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                                </div>
                                                            </div>

                                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                                <template x-if="v.status === 'not_owned'">
                                                                    <button type="button" @click="addToCollection(idx)"
                                                                            :disabled="v.saving"
                                                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                                                                        Tambah ke Koleksi
                                                                    </button>
                                                                </template>
                                                                <template x-if="v.status === 'owned'">
                                                                    <button type="button" @click="updateCollection(idx)"
                                                                            :disabled="v.saving"
                                                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                                                                        Simpan Perubahan
                                                                    </button>
                                                                </template>
                                                                <button type="button" @click="cancelForm(idx)"
                                                                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                                                    Batal
                                                                </button>
                                                                <template x-if="v.status === 'owned' && v.collection">
                                                                    <button type="button" @click="confirmRemove(idx)"
                                                                            class="ml-auto text-xs font-medium text-red-500 transition-colors hover:text-red-600 dark:text-red-400">
                                                                        Hapus dari koleksi
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- No volumes --}}
                <div x-show="volumes.length === 0"
                     class="rounded-md border border-dashed border-slate-200 py-10 text-center dark:border-zinc-700">
                    <p class="text-sm text-slate-400 dark:text-zinc-500">Belum ada volume terdaftar untuk series ini.</p>
                    <a href="{{ route('admin.volumes.index', $series) }}"
                       class="mt-3 inline-flex text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        Tambah volume pertama →
                    </a>
                </div>

            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
const SERIES_ID      = '{{ $series->id }}';
const VOL_STATUS_URL = '{{ route('admin.api.series.volume-status', $series) }}';
const BULK_URL       = '{{ route('admin.collections.bulk') }}';
const CSRF_TOKEN     = document.querySelector('meta[name=csrf-token]')?.content ?? '';

function defaultForm(col) {
    return {
        condition:      col?.condition      ?? 'good',
        is_for_loan:    col?.is_for_loan    ?? true,
        purchase_price: col?.purchase_price ?? '',
        purchase_date:  col?.purchase_date  ?? '',
        notes:          col?.notes          ?? '',
    };
}

function volumeManager() {
    return {
        userId:   '',
        volumes:  [],
        loading:  false,
        feedback: { message: '', type: '' },

        initUserSelect() {
            window._vmApp = this;
            const el = document.getElementById('vm-user-picker');
            if (!el) return;
            new TomSelect(el, {
                maxOptions: 200,
                onChange: (val) => {
                    this.userId   = val;
                    this.volumes  = [];
                    this.feedback = { message: '', type: '' };
                    if (val) this.loadVolumes(val);
                },
            });
        },

        async loadVolumes(uid) {
            this.loading = true;
            try {
                const res  = await fetch(`${VOL_STATUS_URL}?user_id=${uid}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.volumes = (data.volumes ?? []).map(v => ({
                    ...v,
                    displayStatus: v.status,
                    showForm:      false,
                    saving:        false,
                    form:          defaultForm(v.collection),
                }));
            } catch {}
            this.loading = false;
        },

        ownedCount()    { return this.volumes.filter(v => v.status === 'owned').length; },
        loanedCount()   { return this.volumes.filter(v => v.status === 'loaned').length; },
        notOwnedCount() { return this.volumes.filter(v => v.status === 'not_owned').length; },

        handleStatusChange(idx) {
            const v = this.volumes[idx];
            if (v.displayStatus === v.status) return;

            if (v.displayStatus === 'owned') {
                // Show inline add form
                this.volumes[idx].showForm = true;
                this.volumes[idx].form     = defaultForm(null);
            } else if (v.displayStatus === 'not_owned') {
                // Confirm removal
                this.confirmRemove(idx);
            }
        },

        toggleForm(idx) {
            const v = this.volumes[idx];
            this.volumes[idx].showForm = !v.showForm;
            if (this.volumes[idx].showForm) {
                this.volumes[idx].form = defaultForm(v.collection);
            }
        },

        cancelForm(idx) {
            this.volumes[idx].showForm      = false;
            this.volumes[idx].displayStatus = this.volumes[idx].status;
        },

        confirmRemove(idx) {
            const v = this.volumes[idx];
            if (!confirm(`Hapus Vol ${v.volume_number} dari koleksi anggota ini?`)) {
                this.volumes[idx].displayStatus = this.volumes[idx].status;
                return;
            }
            this.removeFromCollection(idx);
        },

        async addToCollection(idx) {
            const v = this.volumes[idx];
            this.volumes[idx].saving = true;
            this.feedback = { message: '', type: '' };
            try {
                const res = await fetch(BULK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        user_id: this.userId,
                        entries: [{
                            series_id:      SERIES_ID,
                            volume_ids:     [v.id],
                            condition:      v.form.condition,
                            is_for_loan:    v.form.is_for_loan,
                            purchase_price: v.form.purchase_price || null,
                            purchase_date:  v.form.purchase_date  || null,
                            notes:          v.form.notes          || null,
                        }],
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message ?? 'Gagal menyimpan.');
                this.feedback = { message: `Vol ${v.volume_number} berhasil ditambahkan ke koleksi.`, type: 'success' };
                await this.loadVolumes(this.userId);
            } catch (err) {
                this.feedback = { message: err.message, type: 'error' };
                this.volumes[idx].saving = false;
            }
        },

        async updateCollection(idx) {
            const v = this.volumes[idx];
            if (!v.collection?.id) return;
            this.volumes[idx].saving = true;
            this.feedback = { message: '', type: '' };
            try {
                const body = new URLSearchParams({
                    _token:         CSRF_TOKEN,
                    _method:        'PATCH',
                    condition:      v.form.condition,
                    is_for_loan:    v.form.is_for_loan ? '1' : '0',
                    purchase_price: v.form.purchase_price ?? '',
                    purchase_date:  v.form.purchase_date  ?? '',
                    notes:          v.form.notes          ?? '',
                });
                const res = await fetch(`/admin/collections/${v.collection.id}`, {
                    method:  'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body,
                });
                if (!res.ok) {
                    const d = await res.json();
                    throw new Error(d.message ?? 'Gagal menyimpan.');
                }
                this.feedback = { message: `Vol ${v.volume_number} berhasil diperbarui.`, type: 'success' };
                await this.loadVolumes(this.userId);
            } catch (err) {
                this.feedback = { message: err.message, type: 'error' };
                this.volumes[idx].saving = false;
            }
        },

        async removeFromCollection(idx) {
            const v = this.volumes[idx];
            if (!v.collection?.id) {
                // Not in DB yet — just revert display
                this.volumes[idx].displayStatus = 'not_owned';
                this.volumes[idx].showForm      = false;
                return;
            }
            this.volumes[idx].saving = true;
            this.feedback = { message: '', type: '' };
            try {
                const reason = encodeURIComponent('Dihapus oleh admin dari halaman series');
                const res = await fetch(`/admin/collections/${v.collection.id}?reason=${reason}`, {
                    method:  'DELETE',
                    headers: {
                        'X-CSRF-TOKEN':     CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                    },
                });
                if (!res.ok) throw new Error('Gagal menghapus.');
                this.feedback = { message: `Vol ${v.volume_number} dihapus dari koleksi.`, type: 'success' };
                await this.loadVolumes(this.userId);
            } catch (err) {
                this.feedback = { message: err.message, type: 'error' };
                this.volumes[idx].displayStatus = this.volumes[idx].status;
                this.volumes[idx].saving        = false;
            }
        },
    };
}
</script>
@endpush

@endsection
