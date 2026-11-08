@extends('layouts.workspace')
@section('title', __('editor.dashboard'))
@section('content')
@if($slaAlerts->count())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-800">⚠ {{ __('editor.sla_alert') }} — {{ $slaAlerts->count() }} naskah ≥ 30 hari di tahap aktif</p>
        <ul class="mt-2 space-y-1">
            @foreach($slaAlerts->take(3) as $s)
                <li class="text-xs text-amber-800">
                    <a href="{{ route('editor.submissions.show', $s) }}" class="hover:underline">#{{ $s->reference_no }} — {{ Str::limit($s->title, 60) }} ({{ $s->daysInStage() }} hari di {{ $s->stageLabel() }})</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3">{{ __('editor.queue') }}</h2>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @foreach(['submitted', 'under_review', 'decision', 'revised', 'accepted', 'scheduled'] as $st)
        <a href="{{ route('editor.dashboard', ['stage' => $st]) }}"
           class="rounded-xl border p-4 text-center transition {{ $activeStage === $st ? 'border-primary bg-primary-light' : 'border-gray-200 bg-white hover:border-primary' }}">
            <p class="text-2xl font-bold text-gray-900">{{ $counts[$st] }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mt-1">{{ \App\Models\Submission::STAGE_LABELS[$st] }}</p>
        </a>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-900">Queue — {{ \App\Models\Submission::STAGE_LABELS[$activeStage] }}</h3>
            <a href="{{ route('editor.submissions.index', ['stage' => $activeStage]) }}" class="text-xs text-primary hover:underline">Semua →</a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
            @forelse($queue as $s)
                <div class="p-4 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('editor.submissions.show', $s) }}" class="font-medium text-sm text-gray-900 hover:text-primary">{{ Str::limit($s->title, 70) }}</a>
                        <p class="text-xs text-gray-500 mt-0.5">
                            #{{ $s->reference_no }} • {{ $s->author?->name }} • {{ $s->section?->name }}
                            <span class="{{ $s->daysInStage() >= 30 ? 'text-amber-600 font-semibold' : 'text-gray-400' }}">{{ $s->daysInStage() }} hari</span>
                            @if($s->stage === 'under_review')
                                • {{ $s->reviews->count() }} review
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('editor.submissions.show', $s) }}" class="text-xs bg-primary text-white rounded-lg px-3 py-1.5 hover:bg-primary-dark shrink-0">Buka</a>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-400">Kosong. 🎉</div>
            @endforelse
        </div>
    </div>

    <div>
        <h3 class="font-bold text-gray-900 mb-3">Pesan Terbaru</h3>
        <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
            @forelse($myMessages as $m)
                <a href="{{ route('editor.submissions.show', $m->submission_id) }}#pesan" class="block p-3 hover:bg-gray-50">
                    <p class="text-xs text-gray-400">#{{ $m->submission->reference_no ?? '' }} • {{ $m->created_at->diffForHumans() }}</p>
                    <p class="text-sm text-gray-700 truncate">{{ $m->subject ?: Str::limit($m->body, 60) }}</p>
                </a>
            @empty
                <div class="p-6 text-center text-xs text-gray-400">Belum ada pesan.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
