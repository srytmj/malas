<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
    @foreach($items as $item)
        @php $malId = (int) $item['mal_id']; $isSelected = in_array($malId, $selected, true); @endphp
        <div
            wire:click="{{ $toggleMethod }}({{ $malId }})"
            wire:key="{{ $prefix }}-{{ $malId }}"
            class="relative cursor-pointer rounded-xl overflow-hidden border-2 transition-all duration-150 select-none
                {{ $isSelected
                    ? 'border-primary-500 ring-2 ring-primary-500/30 shadow-lg'
                    : 'border-gray-200 dark:border-gray-700 hover:border-primary-400 hover:shadow-md' }}"
        >
            @if($isSelected)
            <div class="absolute top-2 right-2 z-10 w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center shadow">
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4 text-white" />
            </div>
            @endif

            <div class="aspect-[2/3] bg-gray-100 dark:bg-gray-800">
                @if($item['images']['jpg']['image_url'] ?? null)
                    <img src="{{ $item['images']['jpg']['image_url'] }}" alt="{{ $item['title'] }}"
                         class="w-full h-full object-cover" loading="lazy" />
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-600">
                        <x-filament::icon icon="heroicon-o-book-open" class="w-10 h-10" />
                    </div>
                @endif
            </div>

            <div class="p-2">
                <p class="text-xs font-semibold leading-tight line-clamp-2 text-gray-900 dark:text-white">
                    {{ $item['title'] }}
                </p>
                <div class="flex items-center gap-1 mt-1 flex-wrap">
                    <span class="text-xs text-gray-400">
                        {{ $item['published']['from'] ? substr($item['published']['from'], 0, 4) : '?' }}
                    </span>
                    @if($item['score'] ?? null)
                        <span class="text-xs text-amber-500">★ {{ number_format($item['score'], 1) }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
