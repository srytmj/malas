<x-filament-panels::page>
    {{-- Indeterminate loading bar — no native Filament equivalent --}}
    <style>@keyframes jikan-bar{0%{left:-45%;width:45%}50%{left:55%;width:45%}100%{left:100%;width:45%}}</style>
    <div wire:loading.delay wire:target="search,searchLoadMore,scrape,scrapeFetchNext"
         class="fixed top-0 inset-x-0 z-[9999] h-[3px] overflow-hidden pointer-events-none bg-primary-100 dark:bg-primary-950">
        <div class="absolute h-full bg-primary-500 rounded-full"
             style="animation:jikan-bar 1.2s ease-in-out infinite"></div>
    </div>
    {{ $this->content }}
</x-filament-panels::page>
