@extends('layouts.workspace')
@section('title', __('review.queue'))
@section('content')
<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-gray-900 mb-5">{{ __('review.queue') }}</h2>

    <div class="space-y-3">
        @forelse($reviews as $r)
            @php($s = $r->submission)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <a href="{{ route('review.show', $s) }}" class="font-semibold text-gray-900 hover:text-primary">{{ $r->blindTitle() }}</a>
                        <p class="text-xs text-gray-500 mt-0.5">
                            #{{ $s->reference_no }} • ronde {{ $r->round }} • {{ $s->section?->name }}
                        </p>
                    </div>
                    <div class="text-right text-xs">
                        @if(in_array($r->status, ['invited', 'accepted', 'in_progress', 'overdue']))
                            <p class="font-medium {{ $r->isOverdue() ? 'text-red-600' : 'text-gray-600' }}">
                                @if($r->isOverdue())
                                    Overdue — due {{ $r->due_at->format('d M Y') }}
                                @elseif($r->due_at)
                                    Due {{ $r->due_at->format('d M Y') }} ({{ $r->daysLeft() >= 0 ? $r->daysLeft().' hari lagi' : 'lewat '.$r->daysLeft().' hari' }})
                                @else
                                    Tanpa tenggat
                                @endif
                            </p>
                        @else
                            <p class="text-gray-500">{{ ucfirst($r->status) }}</p>
                        @endif
                    </div>
                    <a href="{{ route('review.show', $s) }}" class="text-sm bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary-dark font-medium shrink-0">
                        {{ $r->status === 'submitted' ? 'Lihat' : 'Review →' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                Tidak ada naskah yang sedang Anda review.
            </div>
        @endforelse
    </div>
</div>
@endsection
