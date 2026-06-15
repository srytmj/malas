<x-filament-panels::page>
    {{-- User header --}}
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-lg">
                {{ strtoupper(substr($record->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->email }}</p>
            </div>
            <div class="ml-auto flex items-center gap-6 text-center">
                <div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $record->ownedCollections()->count() }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Series</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ $record->ownedCollections()->with('ownedVolumes')->get()->sum(fn($c) => $c->ownedVolumes->count()) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Volume</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                        Rp {{ number_format($record->ownedCollections()->with(['series', 'ownedVolumes'])->get()->sum(fn($c) => $c->getEstimatedWorth()), 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Est. Worth</p>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Collections table --}}
    <x-filament::section>
        <x-slot name="heading">Daftar Koleksi</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
