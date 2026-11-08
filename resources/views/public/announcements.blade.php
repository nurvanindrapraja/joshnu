@extends('layouts.app')
@section('title', __('nav.announcements').' — '.\App\Models\JournalSetting::current()->name)
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('nav.announcements') }}</h1>
    <div class="space-y-4">
        @forelse($announcements as $a)
            <a href="{{ route('announcements.show', $a) }}" class="block rounded-xl border border-gray-200 bg-white p-5 hover:border-primary transition">
                <div class="flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $a->type === 'cfp' ? 'bg-amber-50 text-amber-700' : ($a->type === 'maintenance' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') }}">{{ $a->type }}</span>
                            <span class="text-xs text-gray-400">{{ $a->starts_at->format('d M Y') }}@if($a->ends_at) — {{ $a->ends_at->format('d M Y') }}@endif</span>
                        </div>
                        <p class="mt-2 font-semibold text-gray-900">{{ $a->title }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ Str::limit($a->body, 200) }}</p>
                    </div>
                    @if($a->poster_url)
                        <img src="{{ $a->poster_url }}" alt="Poster {{ $a->title }}" class="h-20 w-28 shrink-0 rounded-lg object-cover border border-gray-200">
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">Belum ada pengumuman.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</div>
@endsection
