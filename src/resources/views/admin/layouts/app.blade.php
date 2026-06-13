<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — MALAS Admin</title>

    {{-- Prevent dark mode flash --}}
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark', 'bg-gray-900');
            }
        })();
    </script>

    {{-- Alpine stores: register before Alpine.start() in app.js --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const saved = localStorage.getItem('theme');
                    const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    this.mode = saved || system;
                    this.apply();
                },
                mode: 'light',
                toggle() {
                    this.mode = this.mode === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.mode);
                    this.apply();
                },
                apply() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.mode === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                },
                toggleMobile() {
                    this.isMobileOpen = !this.isMobileOpen;
                },
                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-init="
        $store.sidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) {
                $store.sidebar.setMobileOpen(false);
                $store.sidebar.isExpanded = false;
            } else {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = true;
            }
        });
    "
    class="antialiased min-h-screen">

<div class="min-h-screen xl:flex">

    {{-- Mobile backdrop --}}
    <div x-show="$store.sidebar.isMobileOpen"
         @click="$store.sidebar.setMobileOpen(false)"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-gray-900/50 xl:hidden"
         style="display:none"></div>

    {{-- ===================== SIDEBAR ===================== --}}
    <aside id="sidebar"
        class="fixed top-0 left-0 h-screen flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out z-99999 dark:bg-gray-900 dark:border-gray-800"
        :class="{
            'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
            'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            'translate-x-0': $store.sidebar.isMobileOpen,
            '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
        }"
        @mouseenter="$store.sidebar.setHovered(true)"
        @mouseleave="$store.sidebar.setHovered(false)">

        {{-- Logo --}}
        <div class="flex items-center px-5 pt-8 pb-7"
            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white shadow-theme-xs">M</div>
                <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" style="display:none">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white tracking-tight">MALAS Admin</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 leading-none mt-0.5">Manga Admin System</span>
                </div>
            </a>
            {{-- Mobile close --}}
            <button @click="$store.sidebar.setMobileOpen(false)"
                class="ml-auto text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 xl:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nav --}}
        <div class="flex flex-col overflow-y-auto no-scrollbar px-5 flex-1">
            <nav class="mb-6">
                @php
                $navGroups = [
                    ['title' => 'Menu', 'items' => [
                        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Dashboard',
                         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ]],
                    ['title' => 'Konten', 'items' => [
                        ['route' => 'admin.series.index', 'match' => 'admin.series.*', 'label' => 'Series',
                         'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['route' => 'admin.collections.index', 'match' => 'admin.collections.*', 'label' => 'Koleksi',
                         'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['route' => 'admin.loans.index', 'match' => 'admin.loans.*', 'label' => 'Peminjaman',
                         'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                    ]],
                    ['title' => 'Data', 'items' => [
                        ['route' => 'admin.jikan.index', 'match' => 'admin.jikan.*', 'label' => 'Jikan Scraper',
                         'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                         'badge' => 'global-scrape-indicator'],
                    ]],
                    ['title' => 'Sistem', 'items' => [
                        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => 'Users',
                         'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'admin.activity-log.index', 'match' => 'admin.activity-log.*', 'label' => 'Activity Log',
                         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ]],
                ];
                @endphp

                <div class="flex flex-col gap-4">
                    @foreach($navGroups as $group)
                    <div>
                        <h2 class="mb-3 text-xs uppercase leading-5 text-gray-400 flex"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start'">
                            <template x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span>{{ $group['title'] }}</span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <span class="text-gray-300 dark:text-gray-700">···</span>
                            </template>
                        </h2>
                        <ul class="flex flex-col gap-1">
                            @foreach($group['items'] as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="menu-item group {{ $active ? 'menu-item-active' : 'menu-item-inactive' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'xl:justify-start'">
                                    <span class="{{ $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          class="flex items-center gap-2" style="display:none">
                                        {{ $item['label'] }}
                                        @if(!empty($item['badge']))
                                        <span id="{{ $item['badge'] }}" class="hidden h-2 w-2 rounded-full bg-brand-400 ring-2 ring-brand-400/30 animate-pulse"></span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
            </nav>
        </div>

        {{-- User footer --}}
        <div class="border-t border-gray-200 dark:border-gray-800 px-5 py-4">
            <div class="flex items-center gap-3"
                :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                     class="min-w-0 flex-1" style="display:none">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">Super Admin</p>
                </div>
                <form method="POST" action="{{ route('logout') }}"
                      x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                      style="display:none">
                    @csrf
                    <button type="submit"
                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200"
                            title="Keluar">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ===================== MAIN ===================== --}}
    <div class="flex-1 transition-all duration-300 ease-in-out"
        :class="{
            'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
            'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            'ml-0': $store.sidebar.isMobileOpen
        }">

        {{-- Header --}}
        <header class="sticky top-0 z-99 flex w-full bg-white border-b border-gray-200 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between w-full gap-3 px-4 py-3 lg:px-6">

                {{-- Desktop sidebar toggle --}}
                <button class="hidden xl:flex items-center justify-center w-10 h-10 text-gray-500 border border-gray-200 rounded-lg dark:border-gray-800 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                    :class="{ 'bg-gray-100 dark:bg-white/[0.03]': !$store.sidebar.isExpanded }"
                    @click="$store.sidebar.toggleExpanded()">
                    <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                            fill="currentColor"/>
                    </svg>
                </button>

                {{-- Mobile sidebar toggle --}}
                <button class="flex xl:hidden items-center justify-center w-10 h-10 text-gray-500 rounded-lg dark:text-gray-400"
                    @click="$store.sidebar.toggleMobile()">
                    <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                            fill="currentColor"/>
                    </svg>
                </button>

                {{-- Page title / breadcrumb --}}
                <div class="flex flex-col justify-center">
                    @hasSection('breadcrumb')
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-500 dark:hover:text-gray-300">Dashboard</a>
                            @yield('breadcrumb')
                        </div>
                        <h1 class="text-sm font-semibold text-gray-800 dark:text-white leading-tight">@yield('heading', 'Dashboard')</h1>
                    @else
                        <h1 class="text-sm font-semibold text-gray-800 dark:text-white">@yield('heading', 'Dashboard')</h1>
                    @endif
                </div>

                <div class="flex-1"></div>

                {{-- Dark mode toggle --}}
                <button @click="$store.theme.toggle()"
                        class="relative flex items-center justify-center text-gray-500 border border-gray-200 rounded-full hover:bg-gray-100 hover:text-gray-700 h-10 w-10 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                        :title="$store.theme.mode === 'dark' ? 'Beralih ke Light Mode' : 'Beralih ke Dark Mode'">
                    <svg x-show="$store.theme.mode === 'dark'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg x-show="$store.theme.mode !== 'dark'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success') || session('error') || $errors->any())
        <div class="space-y-2 px-4 pt-4 lg:px-6">
            @if(session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-800/50 dark:bg-success-500/10 dark:text-success-400">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="flex items-start gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-800/50 dark:bg-error-500/10 dark:text-error-400">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif
            @if($errors->any())
            <div class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-800/50 dark:bg-error-500/10 dark:text-error-400">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        {{-- Page content --}}
        <main class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Jikan scrape indicator --}}
<script>
(function () {
    async function checkScrape() {
        try {
            const r = await fetch('{{ route('admin.jikan.status') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json();
            const el = document.getElementById('global-scrape-indicator');
            if (el) el.classList.toggle('hidden', !(d.active && ['pending','running'].includes(d.active.status)));
        } catch {}
        setTimeout(checkScrape, 15000);
    }
    checkScrape();
})();
</script>

@stack('scripts')
</body>
</html>
