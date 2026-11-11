<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') — {{ ($setting ?? \App\Models\JournalSetting::current())->short_name }}</title>
    <style>
        :root {
            --primary: {{ ($setting ?? \App\Models\JournalSetting::current())->primaryColor() ?? '#1b6e5a' }};
            --primary-light: {{ ($setting ?? \App\Models\JournalSetting::current())->theme['colors']['primary_light'] ?? '#e6f2ef' }};
            --sidebar: #0f172a;
        }
        .bg-primary { background-color: var(--primary); }
        .bg-primary-light { background-color: var(--primary-light); }
        .text-primary { color: var(--primary); }
        .bg-sidebar { background-color: var(--sidebar); }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 antialiased min-h-screen">
@php($s = $setting ?? \App\Models\JournalSetting::current())
@php($u = auth()->user())
@php($nav = [
    'editor' => ['label' => __('nav.editor'), 'items' => [
        ['route' => 'editor.dashboard', 'label' => __('editor.dashboard'), 'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
        ['route' => 'editor.submissions.index', 'label' => __('editor.submissions'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
        ['route' => 'editor.reviewers.index', 'label' => __('editor.reviewers'), 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['route' => 'editor.issues.index', 'label' => __('editor.issues'), 'icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
        ['route' => 'editor.announcements.index', 'label' => __('editor.announcements'), 'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z'],
    ]],
    'production' => ['label' => __('nav.production'), 'items' => [
        ['route' => 'production.index', 'label' => __('production.queue'), 'icon' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
    ]],
    'reviewer' => ['label' => __('nav.review'), 'items' => [
        ['route' => 'review.index', 'label' => __('review.queue'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z'],
    ]],
    'author' => ['label' => __('account.submissions'), 'items' => [
        ['route' => 'account.submissions.index', 'label' => __('nav.my_submissions'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
        ['route' => 'account.submissions.create', 'label' => __('account.new_submission'), 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
    ]],
    'admin' => ['label' => __('nav.admin'), 'items' => [
        ['route' => 'admin.sections.index', 'label' => __('nav.sections'), 'icon' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z'],
        ['route' => 'admin.users.index', 'label' => 'Users & Roles', 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['route' => 'admin.payments.index', 'label' => 'APC Payments', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 3h6m3-6h-3m3 3h-3m3 3h-3m-3.75 3h7.5a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['route' => 'admin.subscribers.index', 'label' => 'Subscribers', 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
        ['route' => 'admin.pages.index', 'label' => 'Pages', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
        ['route' => 'admin.reports.index', 'label' => 'Reports & Analytics', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
        ['route' => 'admin.audit-logs.index', 'label' => 'Audit Log', 'icon' => 'M9 12.75 7.5 15m0 0-1.5 2.25M9 12.75H5.136a4.128 4.128 0 0 0-4.109 5.308A6.75 6.75 0 0 0 9.75 20.625H12m-3-2.625V15m3 7.625V9a3 3 0 0 0-3-3h-3.75c-1.056 0-2.056.47-2.735 1.285A2.25 2.25 0 0 1 4.5 4.5h3.25a3.75 3.75 0 0 1 3.75 3.75v11.875'],
        ['route' => 'admin.settings.edit', 'label' => 'Journal Settings', 'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
    ]],
])
@php($visibleNav = collect($nav)->filter(function ($group) use ($u) {
    if ($group['label'] === __('nav.editor')) return $u->isEditorial();
    if ($group['label'] === __('nav.production')) return $u->isProduction();
    if ($group['label'] === __('nav.review')) return $u->hasRole('reviewer') || $u->isEditorial();
    if ($group['label'] === __('nav.admin')) return $u->hasRole('admin');
    return true; // author
})->values())
<div class="min-h-screen lg:flex" x-data="{ sidebarOpen: false }">
    <!-- Mobile overlay backdrop -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

    <!-- Sidebar (off-canvas drawer on mobile) -->
    <aside class="bg-sidebar text-white z-50 fixed inset-y-0 left-0 w-64 transform transition-transform duration-200 ease-in-out" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="h-16 flex items-center gap-2 px-4 border-b border-slate-700/60">
            <a href="{{ route('home') }}" class="flex items-center gap-2" @click="sidebarOpen = false">
                @if($s->theme['logo'] ?? null)
                    <img src="{{ asset('storage/'.$s->theme['logo']) }}" alt="{{ $s->short_name }}" class="h-8 w-8 object-contain">
                @else
                    <span class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center font-bold text-sm">{{ substr($s->short_name, 0, 2) }}</span>
                @endif
                <span class="font-bold text-sm">{{ $s->short_name }} <span class="text-slate-400 font-normal">Workspace</span></span>
            </a>
            <button class="ml-auto p-1 lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <nav class="py-4 space-y-4 overflow-y-auto h-[calc(100vh-4rem)]">
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800" @click="sidebarOpen = false">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                {{ __('nav.home') }}
            </a>
            @foreach($visibleNav as $group)
                <div>
                    <p class="px-4 text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-1">{{ $group['label'] }}</p>
                    @foreach($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-4 py-2 text-sm {{ request()->routeIs($item['route']) ? 'bg-slate-800 text-white border-l-2 border-primary' : 'text-slate-300 hover:bg-slate-800 border-l-2 border-transparent' }}" @click="sidebarOpen = false">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <!-- Main -->
    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2">
                <button class="p-2 -ml-2 text-gray-600 hover:text-gray-900 lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
                <h1 class="font-bold text-lg text-gray-800">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-500 hover:text-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                    @if($u->unreadNotificationsCount() > 0)
                        <span class="absolute top-0.5 right-0.5 h-4 min-w-4 px-1 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center">{{ $u->unreadNotificationsCount() }}</span>
                    @endif
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false" class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-primary">
                        <span class="hidden sm:inline-flex h-7 w-7 rounded-full bg-primary-light text-primary text-xs font-bold items-center justify-center">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                        <span>{{ $u->name }}</span>
                        <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white py-2 shadow-lg z-50">
                        <p class="px-4 py-1.5 text-xs text-gray-400 border-b border-gray-100 mb-1">{{ $u->name }}</p>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            {{ __('nav.profile') }}
                        </a>
                        <a href="{{ route('account.submissions.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            {{ __('nav.my_submissions') }}
                        </a>
                        <div class="my-1 border-t border-gray-100"></div>
                        <button type="button" x-on:click="$dispatch('open-modal', 'confirm-logout')" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                            {{ __('nav.logout') }}
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('status') || session('error'))
                @if (session('status'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
                @endif
            @endif
            @yield('content')
        </main>
    </div>
</div>
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
