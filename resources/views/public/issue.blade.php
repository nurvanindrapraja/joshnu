@extends('layouts.app')
@section('title', $issue->label().' — '.\App\Models\JournalSetting::current()->name)
@section('content')
@php($lang = app()->isLocale('en') ? 'en' : 'id')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <nav class="text-xs text-gray-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-primary">Home</a> /
        <a href="{{ route('issues.index') }}" class="hover:text-primary">Archive</a> /
        <span class="text-gray-700">{{ $issue->label() }}</span>
    </nav>

    <div class="rounded-xl border border-gray-200 bg-white p-6 mb-6 flex flex-wrap items-center gap-4">
        @if($issue->cover_path)
            <img src="{{ asset('storage/'.$issue->cover_path) }}" alt="Cover {{ $issue->label() }}" class="h-28 w-20 object-cover rounded shadow">
        @else
            <div class="h-28 w-20 rounded bg-primary text-white flex flex-col items-center justify-center font-bold">
                <span class="text-lg">V{{ $issue->volume }}</span>
                <span class="text-xs">N{{ $issue->number }}</span>
            </div>
        @endif
        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900">{{ $issue->label() }} @if($issue->title)— "{{ $issue->title }}"@endif</h1>
            <p class="text-sm text-gray-500 mt-1">Terbit: {{ $issue->published_at->format('d M Y') }} • {{ count($issue->publishedPublications) }} artikel</p>
        </div>
        @if($issuePdf)
            <a href="{{ $issuePdf }}" class="inline-flex items-center gap-2 border border-primary text-primary hover:bg-primary-light rounded-lg px-4 py-2 text-sm font-medium">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Issue PDF
            </a>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
        @forelse($issue->publishedPublications as $p)
            <div class="p-5">
                <h2 class="font-bold text-gray-900 leading-snug">
                    <a href="{{ route('articles.show', $p->slug) }}" class="hover:text-primary">{{ $p->titleFor($lang) }}</a>
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $p->authorString() }}</p>
                <p class="text-sm text-gray-500 mt-2">{{ Str::limit($p->abstractFor($lang), 300) }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs">
                    <a href="{{ route('articles.show', $p->slug) }}" class="text-primary font-medium hover:underline">Baca →</a>
                    @if($p->versionOfRecord())
                        <a href="{{ route('articles.download', [$p->slug, $p->versionOfRecord()->id]) }}" class="text-gray-600 hover:text-primary">PDF</a>
                    @endif
                    @if($p->doi)
                        <a href="{{ $p->doiUrl() }}" target="_blank" class="font-mono text-gray-500 hover:text-primary">{{ $p->doi }}</a>
                    @endif
                    <span class="text-gray-400 ml-auto">👁 {{ number_format($p->views) }} • ⬇ {{ number_format($p->downloads) }}</span>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-gray-500">Belum ada artikel terbit pada issue ini.</div>
        @endforelse
    </div>
</div>
@endsection
