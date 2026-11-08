@extends('layouts.app')
@section('title', __('nav.archive').' — '.\App\Models\JournalSetting::current()->name)
@section('content')
@php($lang = app()->isLocale('en') ? 'en' : 'id')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('nav.archive') }}</h1>

    <div class="space-y-6">
        @forelse($issues as $group => $groupIssues)
            @foreach($groupIssues as $issue)
                <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <a href="{{ route('issues.show', $issue->slug) }}" class="flex flex-wrap items-center gap-3 px-5 py-4 hover:bg-gray-50">
                        <div class="h-16 w-12 rounded bg-primary-light flex items-center justify-center text-primary font-bold text-xs">{{ $issue->volume }}<br>{{ $issue->number }}</div>
                        <div class="flex-1 min-w-[200px]">
                            <p class="font-bold text-gray-900">{{ $issue->label() }} @if($issue->title)<span class="font-normal text-gray-600">— "{{ $issue->title }}"</span>@endif</p>
                            <p class="text-xs text-gray-500 mt-0.5">Terbit: {{ $issue->published_at->format('d M Y') }} • {{ $issue->publishedPublications->count() }} artikel</p>
                        </div>
                        <span class="text-sm text-primary font-medium">Detail →</span>
                    </a>
                    <div class="border-t border-gray-100 divide-y divide-gray-100">
                        @foreach($issue->publishedPublications as $p)
                            <div class="px-5 py-3 flex gap-3">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('articles.show', $p->slug) }}" class="font-medium text-sm text-gray-900 hover:text-primary">{{ $p->titleFor($lang) }}</a>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $p->authorString() }} • {{ Str::limit($p->abstractFor($lang), 120) }}</p>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-400 shrink-0">
                                    @if($p->versionOfRecord())
                                        <a href="{{ route('articles.download', [$p->slug, $p->versionOfRecord()->id]) }}" class="hover:text-primary font-medium">PDF</a>
                                    @endif
                                    <span class="flex items-center gap-1">👁 {{ number_format($p->views) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                Belum ada issue terbit.
            </div>
        @endforelse
    </div>
</div>
@endsection
