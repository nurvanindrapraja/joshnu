<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($setting ?? \App\Models\JournalSetting::current())->name)</title>
    <meta name="description" content="@yield('meta_description', $setting?->tagline ?? 'Journal of Sustainable Health and New Urbanism')">
    <link rel="canonical" href="{{ url()->current() }}">
    @php($js = $setting ?? \App\Models\JournalSetting::current())
    @if($js->theme['logo'] ?? null)
        <link rel="icon" type="image/png" href="{{ asset('storage/'.$js->theme['logo']) }}">
    @endif
    <style>
        :root {
            --primary: {{ $js->primaryColor() }};
            --primary-dark: {{ $js->theme['colors']['primary_dark'] ?? '#145244' }};
            --primary-light: {{ $js->theme['colors']['primary_light'] ?? '#e6f2ef' }};
            --accent: {{ $js->theme['colors']['accent'] ?? '#f59e0b' }};
            --bg: #f5f6f8;
            --surface: #ffffff;
            --sidebar: #0f172a;
        }
        .bg-primary { background-color: var(--primary); }
        .bg-primary-dark { background-color: var(--primary-dark); }
        .bg-primary-light { background-color: var(--primary-light); }
        .text-primary { color: var(--primary); }
        .border-primary { border-color: var(--primary); }
        .ring-primary { --tw-ring-color: var(--primary); }
        [x-cloak] { display: none !important; }
        html { scroll-padding-top: 120px; }
        @media (max-width: 640px) { html { scroll-padding-top: 80px; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--bg)] text-gray-900 font-sans antialiased min-h-screen flex flex-col">
@php($s = $setting ?? \App\Models\JournalSetting::current())
@php($mainNav = [
    ['route' => 'home', 'label' => __('nav.home'), 'active' => request()->routeIs('home')],
    ['route' => 'about', 'label' => __('nav.about'), 'active' => request()->routeIs('about')],
    ['route' => 'issues.current', 'label' => __('nav.current_issue'), 'active' => request()->routeIs('issues.current')],
    ['route' => 'issues.index', 'label' => __('nav.archive'), 'active' => request()->routeIs('issues.*') && !request()->routeIs('issues.current')],
    ['route' => 'announcements.index', 'label' => __('nav.announcements'), 'active' => request()->routeIs('announcements.*')],
    ['route' => 'articles.index', 'label' => __('nav.search'), 'active' => request()->routeIs('articles.index')],
])
<header class="bg-white sticky top-0 z-40 border-b border-gray-200" x-data="{ mobileNav: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @if($s->theme['logo'] ?? null)
                    <img src="{{ asset('storage/'.$s->theme['logo']) }}" alt="{{ $s->short_name }}" class="h-10 w-10 object-contain">
                @else
                    <span class="h-10 w-10 rounded-lg bg-primary text-white flex items-center justify-center font-bold">{{ substr($s->short_name, 0, 2) }}</span>
                @endif
                <span class="flex items-start gap-3 min-w-0">
                    <span class="block font-bold leading-tight">{{ $s->name }}</span>
                    @if($s->issn_online)<span class="shrink-0 whitespace-nowrap text-[10px] font-medium text-gray-500 leading-tight mt-0.5">ISSN e-{{ $s->issn_online }}</span>@endif
                </span>
            </a>
            <div class="flex items-center gap-2">
                <div class="hidden sm:flex items-center rounded-lg border border-gray-200 overflow-hidden text-sm">
                    <a href="{{ route('locale.set', 'id') }}" class="px-2 py-1 {{ app()->isLocale('id') ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">ID</a>
                    <a href="{{ route('locale.set', 'en') }}" class="px-2 py-1 {{ app()->isLocale('en') ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">EN</a>
                </div>
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-500 hover:text-primary" title="{{ __('nav.notifications') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                        @if(auth()->user()->unreadNotificationsCount() > 0)
                            <span class="absolute top-0.5 right-0.5 h-4 min-w-4 px-1 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center">{{ auth()->user()->unreadNotificationsCount() }}</span>
                        @endif
                    </a>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 text-sm text-gray-700 hover:text-primary px-2 py-1">
                            <span class="h-8 w-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-56 rounded-lg border border-gray-200 bg-white shadow-lg py-1 z-50">
                            <x-dropdown-link :href="route('profile.edit')">{{ __('nav.profile') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('account.submissions.index')">{{ __('nav.my_submissions') }}</x-dropdown-link>
                            @if(auth()->user()->hasRole('reviewer'))<x-dropdown-link :href="route('review.index')">{{ __('nav.review') }}</x-dropdown-link>@endif
                            @if(auth()->user()->isEditorial())<x-dropdown-link :href="route('editor.dashboard')">{{ __('nav.editor') }}</x-dropdown-link>@endif
                            @if(auth()->user()->isProduction())<x-dropdown-link :href="route('production.index')">{{ __('nav.production') }}</x-dropdown-link>@endif
                            @if(auth()->user()->hasRole('admin'))<x-dropdown-link :href="route('admin.settings.edit')">{{ __('nav.admin') }}</x-dropdown-link>@endif
                            <button type="button" x-on:click="$dispatch('open-modal', 'confirm-logout')" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">{{ __('nav.logout') }}</button>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:block text-sm text-gray-600 hover:text-primary px-2">{{ __('nav.login') }}</a>
                    <a href="{{ route('register') }}" class="hidden sm:block text-sm bg-primary text-white rounded-lg px-3 py-1.5 hover:bg-primary-dark">{{ __('nav.register') }}</a>
                @endauth
                {{-- Hamburger: hanya di mobile --}}
                <button @click="mobileNav = true" class="sm:hidden p-2 -mr-2 text-gray-600 hover:text-primary" aria-label="Buka menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>
        </div>
        {{-- Nav horizontal: hanya di desktop --}}
        <nav class="hidden sm:flex gap-1 -mb-px overflow-x-auto text-sm pb-0">
            @foreach($mainNav as $item)
                <a href="{{ route($item['route']) }}" class="px-3 py-2.5 border-b-2 {{ $item['active'] ? 'border-primary text-primary font-medium' : 'border-transparent text-gray-600 hover:text-primary' }}">{{ $item['label'] }}</a>
            @endforeach
            @auth
                <a href="{{ route('account.submissions.create') }}" class="ml-auto px-3 py-1.5 self-center bg-primary text-white rounded-lg hover:bg-primary-dark text-sm font-medium shadow-sm">{{ __('nav.submit_manuscript') }}</a>
            @endauth
        </nav>
    </div>

    {{-- Mobile: backdrop overlay --}}
    <div x-cloak x-show="mobileNav" @click="mobileNav = false"
         x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/40 sm:hidden"></div>

    {{-- Mobile: left menu (overlay, slide dari kiri) --}}
    <aside x-cloak x-show="mobileNav"
           x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-300 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 w-72 max-w-[80vw] bg-white shadow-xl sm:hidden flex flex-col overflow-y-auto">
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-200 shrink-0">
            <a href="{{ route('home') }}" @click="mobileNav = false" class="flex items-center gap-2">
                @if($s->theme['logo'] ?? null)
                    <img src="{{ asset('storage/'.$s->theme['logo']) }}" alt="{{ $s->short_name }}" class="h-9 w-9 object-contain">
                @else
                    <span class="h-9 w-9 rounded-lg bg-primary text-white flex items-center justify-center font-bold text-sm">{{ substr($s->short_name, 0, 2) }}</span>
                @endif
                <span class="font-bold leading-tight">{{ $s->short_name }}</span>
            </a>
            <button @click="mobileNav = false" class="p-2 text-gray-500 hover:text-gray-800" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <nav class="px-3 py-4 space-y-1">
            @foreach($mainNav as $item)
                <a href="{{ route($item['route']) }}" @click="mobileNav = false"
                   class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm {{ $item['active'] ? 'bg-primary-light text-primary font-semibold' : 'text-gray-700 hover:bg-gray-50 hover:text-primary' }}">
                    {{ $item['label'] }}
                    @if($item['active'])
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-gray-200 px-3 py-4 space-y-3">
            @auth
                <a href="{{ route('account.submissions.create') }}" @click="mobileNav = false" class="block text-center bg-primary hover:bg-primary-dark text-white rounded-lg px-3 py-2.5 text-sm font-medium shadow-sm">{{ __('nav.submit_manuscript') }}</a>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <a href="{{ route('notifications.index') }}" @click="mobileNav = false" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">{{ __('nav.notifications') }}</a>
                    <a href="{{ route('profile.edit') }}" @click="mobileNav = false" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">{{ __('nav.profile') }}</a>
                    <a href="{{ route('account.submissions.index') }}" @click="mobileNav = false" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">{{ __('nav.my_submissions') }}</a>
                    <button type="button" x-on:click="$dispatch('open-modal', 'confirm-logout'); mobileNav = false" class="px-3 py-2 rounded-lg bg-gray-100 text-red-600 hover:bg-gray-200 text-left">{{ __('nav.logout') }}</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <a href="{{ route('login') }}" @click="mobileNav = false" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-center">{{ __('nav.login') }}</a>
                    <a href="{{ route('register') }}" @click="mobileNav = false" class="px-3 py-2 rounded-lg bg-primary text-white hover:bg-primary-dark text-center">{{ __('nav.register') }}</a>
                </div>
            @endauth
            <div class="flex items-center justify-center gap-1 rounded-lg border border-gray-200 overflow-hidden text-sm w-fit mx-auto">
                <a href="{{ route('locale.set', 'id') }}" class="px-2.5 py-1 {{ app()->isLocale('id') ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">ID</a>
                <a href="{{ route('locale.set', 'en') }}" class="px-2.5 py-1 {{ app()->isLocale('en') ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">EN</a>
            </div>
        </div>
    </aside>
</header>

<main class="flex-1">
    @if (session('status') || session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-4">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
        </div>
    @endif
    @yield('content')
</main>

<footer class="bg-[var(--sidebar)] text-gray-300 mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid gap-8 md:grid-cols-4 text-sm">
        <div>
            <p class="font-bold text-white text-base">{{ $s->short_name }}</p>
            <p class="mt-2 text-gray-400">{{ $s->tagline }}</p>
            @if($s->issn_online)<p class="mt-2 text-xs text-gray-500">ISSN e-{{ $s->issn_online }} @if($s->issn_print) | l-{{ $s->issn_print }}@endif</p>@endif
        </div>
        <div>
            <p class="font-semibold text-white mb-2">{{ __('footer.policies') }}</p>
            <ul class="space-y-1 text-gray-400">
                <li><a class="hover:text-white" href="{{ route('about') }}#ethics">Ethics</a></li>
                <li><a class="hover:text-white" href="{{ route('about') }}#open-access">Open Access</a></li>
                <li><a class="hover:text-white" href="{{ route('about') }}#apc">APC</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white mb-2">{{ __('footer.contact') }}</p>
            <ul class="space-y-1 text-gray-400">
                @if($s->contact_email)<li>{{ $s->contact_email }}</li>@endif
                @if($s->address)<li>{{ $s->address }}</li>@endif
                <li><a class="hover:text-white" href="{{ route('sitemap') }}">{{ __('footer.sitemap') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white mb-2">{{ __('home.subscribe_title') }}</p>
            <form method="POST" action="{{ route('subscribe.store') }}" data-ajax data-no-refresh class="flex gap-2">
                @csrf
                <input type="email" name="email" required placeholder="email@instansi.ac.id" class="flex-1 min-w-0 rounded-lg bg-slate-800 border border-slate-700 px-3 py-1.5 text-sm placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-primary">
                <button class="bg-primary hover:bg-primary-dark text-white text-sm rounded-lg px-3">{{ __('home.subscribe_btn') }}</button>
            </form>
            <p class="mt-3 text-xs text-gray-500">© {{ now()->year }} {{ $s->name }}. <a href="https://creativecommons.org/licenses/by/4.0/" class="hover:text-white">CC BY 4.0</a></p>
        </div>
    </div>
</footer>
<x-modal name="confirm-logout" maxWidth="sm" focusable>
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Konfirmasi {{ __('nav.logout') }}</h2>
        <p class="mt-2 text-sm text-gray-600">Yakin ingin keluar dari akun ini?</p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-logout')">Batal</x-secondary-button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-danger-button>{{ __('nav.logout') }}</x-danger-button>
            </form>
        </div>
    </div>
</x-modal>
</body>
</html>
