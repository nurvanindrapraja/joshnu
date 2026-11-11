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

<h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3">Ringkasan</h2>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('editor.submissions.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 hover:border-primary transition">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['manuscripts_total']) }}</p>
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mt-1">Total Naskah</p>
    </a>
    <a href="{{ route('editor.submissions.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 hover:border-primary transition">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['authors_submitted']) }}</p>
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mt-1">Author yang Submit</p>
    </a>
    <a href="{{ $user->hasRole('admin') ? route('admin.users.index', ['tab' => 'authors']) : '#' }}" class="rounded-xl border border-gray-200 bg-white p-4 {{ $user->hasRole('admin') ? 'hover:border-primary' : 'cursor-default' }} transition">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['authors_registered']) }}</p>
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mt-1">Author Terdaftar</p>
    </a>
    <a href="{{ $user->hasRole('admin') ? route('admin.subscribers.index') : '#' }}" class="rounded-xl border border-gray-200 bg-white p-4 {{ $user->hasRole('admin') ? 'hover:border-primary' : 'cursor-default' }} transition">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['subscribers']) }}</p>
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mt-1">Subscriber</p>
    </a>
</div>

<div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Rekap Keuangan (APC)</h2>
    @if($user->hasRole('admin'))<a href="{{ route('admin.payments.index') }}" class="text-xs text-primary hover:underline">Kelola →</a>@endif
</div>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    @foreach([
        ['key' => 'verified', 'label' => 'Verified', 'cls' => 'border-green-200 bg-green-50 text-green-800', 'amountCls' => 'text-green-700'],
        ['key' => 'pending', 'label' => 'Pending', 'cls' => 'border-amber-200 bg-amber-50 text-amber-800', 'amountCls' => 'text-amber-700'],
        ['key' => 'rejected', 'label' => 'Rejected', 'cls' => 'border-red-200 bg-red-50 text-red-800', 'amountCls' => 'text-red-700'],
    ] as $f)
        <a href="{{ $user->hasRole('admin') ? route('admin.payments.index', ['status' => $f['key']]) : '#' }}" class="rounded-xl border p-4 {{ $f['cls'] }} {{ $user->hasRole('admin') ? 'hover:shadow-sm' : 'cursor-default' }} transition">
            <p class="text-xs font-semibold uppercase tracking-wide">{{ $f['label'] }}</p>
            <p class="text-2xl font-bold mt-1">{{ number_format($finance[$f['key']]['count']) }} <span class="text-sm font-medium">invoice</span></p>
            <p class="text-sm font-semibold {{ $f['amountCls'] }} mt-0.5">Rp {{ number_format($finance[$f['key']]['amount'], 0, ',', '.') }}</p>
        </a>
    @endforeach
</div>

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

        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900">Pages Terbaru</h3>
                @if($user->hasRole('admin'))
                    <a href="{{ route('admin.pages.index') }}" class="text-xs text-primary hover:underline">Kelola →</a>
                @endif
            </div>
            <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">
                @forelse($latestPages as $pg)
                    <a href="{{ route('pages.show', $pg->name) }}" class="block p-3 hover:bg-gray-50">
                        <p class="text-sm text-gray-700 truncate">{{ $pg->title }}
                            @unless($pg->is_active)<span class="ml-1 text-[10px] text-gray-400">(nonaktif)</span>@endunless
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">/{{ $pg->name }} • {{ $pg->updated_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <div class="p-6 text-center text-xs text-gray-400">Belum ada page.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
