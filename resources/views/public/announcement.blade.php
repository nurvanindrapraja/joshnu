@extends('layouts.app')
@section('title', $announcement->title.' — '.\App\Models\JournalSetting::current()->name)
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <nav class="text-xs text-gray-500 mb-4">
        <a href="{{ route('announcements.index') }}" class="hover:text-primary">← Semua Pengumuman</a>
    </nav>
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        @if($announcement->poster_url)
            <img src="{{ $announcement->poster_url }}" alt="Poster {{ $announcement->title }}" class="mb-5 w-full max-h-96 rounded-lg object-cover border border-gray-200">
        @endif
        <div class="flex items-center gap-2 mb-3">
            <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ $announcement->type }}</span>
            <span class="text-xs text-gray-400">{{ $announcement->starts_at->format('d M Y') }}</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900">{{ $announcement->title }}</h1>
        <div class="mt-4 text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $announcement->body }}</div>
    </div>
</div>
@endsection
