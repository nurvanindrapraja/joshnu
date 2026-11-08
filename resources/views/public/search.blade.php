@extends('layouts.app')
@section('title', __('nav.search').' — '.\App\Models\JournalSetting::current()->name)
@section('content')
@php($lang = app()->isLocale('en') ? 'en' : 'id')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Pencarian</h1>

    <form method="GET" action="{{ route('articles.index') }}" data-ajax-filter class="rounded-xl border border-gray-200 bg-white p-5 mb-6 grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Keyword (judul/abstrak)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="mis. taman kota, kesehatan mental…" class="w-full rounded-lg border-gray-300 text-sm border p-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Penulis</label>
            <input type="text" name="author" value="{{ $filters['author'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border p-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tahun dari</label>
            <input type="number" name="year_from" value="{{ $filters['year_from'] ?? '' }}" min="2000" max="2100" class="w-full rounded-lg border-gray-300 text-sm border p-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tahun s/d</label>
            <input type="number" name="year_to" value="{{ $filters['year_to'] ?? '' }}" min="2000" max="2100" class="w-full rounded-lg border-gray-300 text-sm border p-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Section</label>
            <select name="section" class="w-full rounded-lg border-gray-300 text-sm border p-2">
                <option value="">Semua</option>
                @foreach($sections as $sec)
                    <option value="{{ $sec->id }}" @selected(($filters['section'] ?? '') == $sec->id)>{{ $sec->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3 flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Urutkan</label>
                <select name="sort" class="w-full rounded-lg border-gray-300 text-sm border p-2">
                    <option value="relevance" @selected(($filters['sort'] ?? '') === 'relevance')">Relevansi</option>
                    <option value="date_desc" @selected(($filters['sort'] ?? '') === 'date_desc')">Terbaru</option>
                    <option value="date_asc" @selected(($filters['sort'] ?? '') === 'date_asc')">Terlama</option>
                    <option value="views" @selected(($filters['sort'] ?? '') === 'views')">Terbanyak dibaca</option>
                </select>
            </div>
            <a href="{{ route('articles.index') }}" data-ajax-reset class="border border-gray-300 text-gray-600 hover:text-primary rounded-lg px-4 py-2 text-sm text-center">{{ __('common.reset') }}</a>
        </div>
    </form>

    <div data-ajax-results>
    @if(! empty($filters['q']) || ! empty($filters['author']))
        <p class="text-sm text-gray-600 mb-4">{{ $results->total() }} {{ __('common.results') }} {{ __('common.for') }} "{{ $filters['q'] ?? $filters['author'] }}"</p>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
        @forelse($results as $p)
            @php($m = $p->metadataFor($lang))
            <div class="p-5">
                <h2 class="font-bold text-gray-900">
                    <a href="{{ route('articles.show', $p->slug) }}" class="hover:text-primary">{{ $m?->title ?? $p->titleFor($lang) }}</a>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $p->authorString() }}
                    @if($p->issue) • {{ $p->issue->label() }}@endif
                    @if($p->section) • {{ $p->section->name }}@endif
                </p>
                <p class="text-sm text-gray-500 mt-2">{{ Str::limit($m?->abstract ?? '', 300) }}</p>
                <div class="mt-3 flex items-center gap-4 text-xs">
                    <a href="{{ route('articles.show', $p->slug) }}" class="text-primary font-medium hover:underline">Baca →</a>
                    @if($p->versionOfRecord())
                        <a href="{{ route('articles.download', [$p->slug, $p->versionOfRecord()->id]) }}" class="text-gray-600 hover:text-primary">PDF</a>
                    @endif
                    @if($p->doi)<span class="font-mono text-gray-400">{{ $p->doi }}</span>@endif
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-gray-500">
                Tidak ada artikel yang cocok. @if(! empty($filters['q']) || ! empty($filters['author'])) Coba kata kunci lain. @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $results->links() }}</div>
    </div>
</div>
@endsection
