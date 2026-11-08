@extends('layouts.workspace')
@section('title', __('nav.notifications'))
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">{{ __('nav.notifications') }}</h2>
        @if(auth()->user()->unreadNotificationsCount() > 0)
            <form method="POST" action="{{ route('notifications.read') }}">
                @csrf
                <button class="text-sm text-primary hover:underline">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
        @forelse($notifications as $n)
            <a href="{{ $n->data['url'] ?? route('account.submissions.index') }}" class="flex items-start gap-3 p-4 {{ $n->read_at ? '' : 'bg-primary-light/40' }}">
                <span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ $n->read_at ? 'bg-gray-200' : 'bg-primary' }}"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm {{ $n->read_at ? 'text-gray-600' : 'text-gray-900 font-medium' }}">{{ $n->label() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <div class="p-10 text-center text-gray-400 text-sm">Belum ada notifikasi.</div>
        @endforelse
    </div>
</div>
@endsection
