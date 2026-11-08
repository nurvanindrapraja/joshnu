@extends('layouts.app')
@section('title', $setting->name.' — '.($setting->tagline ?? 'e-Journal'))
@section('content')
<div class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-16">
        <div class="grid md:grid-cols-3 gap-8 items-center">
            <div class="md:col-span-2">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight">{{ $setting->name }}</h1>
                <p class="mt-3 text-gray-600 max-w-2xl">{{ $setting->tagline }}</p>
                @if($setting->text('scope'))<p class="mt-2 text-sm text-gray-500 max-w-2xl">{{ Str::limit($setting->text('scope'), 300) }}</p>@endif
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('account.submissions.create') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg px-5 py-2.5 shadow-sm">{{ __('home.hero_submit') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg px-5 py-2.5 shadow-sm">{{ __('home.hero_submit') }}</a>
                    @endauth
                    <a href="{{ route('issues.current') }}" class="inline-flex items-center gap-2 border border-gray-300 hover:border-primary hover:text-primary text-gray-700 font-medium rounded-lg px-5 py-2.5">{{ __('home.hero_browse') }}</a>
                </div>
            </div>
            <div class="hidden md:block rounded-xl bg-primary-light p-5">
                <p class="text-sm font-semibold text-gray-800">{{ __('home.about_journal') }}</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li>• Open Access — CC BY 4.0</li>
                    <li>• Peer review {{ $setting->review_mode === 'double_blind' ? 'double-blind' : $setting->review_mode }}</li>
                    <li>• Terindeks Crossref @if($setting->doi_prefix) & SINTA @endif</li>
                    @if($setting->issn_online)<li>• ISSN e-{{ $setting->issn_online }}</li>@endif
                </ul>
                <a href="{{ route('about') }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">{{ __('home.read_more') }} →</a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid md:grid-cols-2 gap-8">
    <section>
        <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('home.announcements') }}</h2>
        <div class="space-y-3">
            @forelse($announcements as $a)
                <a href="{{ route('announcements.show', $a) }}" class="block rounded-xl border border-gray-200 bg-white p-4 hover:border-primary transition">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $a->type === 'cfp' ? 'bg-amber-50 text-amber-700' : ($a->type === 'maintenance' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') }}">{{ $a->type }}</span>
                        <span class="text-xs text-gray-400">{{ $a->starts_at->format('d M Y') }}</span>
                    </div>
                    <p class="mt-2 font-medium text-gray-900">{{ $a->title }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-500">Belum ada pengumuman.</p>
            @endforelse
        </div>
        <a href="{{ route('announcements.index') }}" class="mt-3 inline-block text-sm text-primary hover:underline">{{ __('home.all_announcements') }} →</a>
    </section>

    <section>
        <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('home.about_journal') }}</h2>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-600">{{ Str::limit($setting->text('description') ?? $setting->text('scope'), 400) }}</p>
            <a href="{{ route('about') }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">{{ __('home.read_more') }} →</a>
        </div>
    </section>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">{{ __('home.latest_articles') }}</h2>
        <a href="{{ route('articles.index') }}" class="text-sm text-primary hover:underline">{{ __('home.view_all') }} →</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($latestArticles as $p)
            @include('partials.article-card', ['publication' => $p])
        @empty
            <p class="text-sm text-gray-500 col-span-full">Artikel akan tampil di sini setelah issue pertama terbit.</p>
        @endforelse
    </div>
</div>
@endsection
