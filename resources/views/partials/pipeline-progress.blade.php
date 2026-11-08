@php
    $pipeline = \App\Models\Submission::PIPELINE;
    $currentIdx = array_search($submission->stage, $pipeline, true);
    $isTerminal = in_array($submission->stage, ['rejected', 'withdrawn'], true);
    $eventDates = $submission->events
        ->where('event', 'stage_changed')
        ->groupBy('to_stage')
        ->map(fn ($g) => $g->first()->created_at);
@endphp
<div class="flex items-start justify-between overflow-x-auto pb-2">
    @foreach($pipeline as $i => $st)
        @php($done = $currentIdx !== false && $i <= $currentIdx)
        @php($isRejectedHere = $isTerminal && $i === 0)
        <div class="flex flex-col items-center min-w-[86px] flex-1">
            <div class="flex items-center w-full">
                <div class="h-0.5 flex-1 {{ $i > 0 ? ($done || ($currentIdx !== false && $i < count($pipeline)) ? 'bg-primary' : 'bg-gray-200') : 'bg-transparent' }}"></div>
                <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                    {{ $done ? 'bg-primary text-white' : ($isRejectedHere && $i === 4 ? '' : 'bg-gray-200 text-gray-500') }}">
                    @if($done)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </div>
                <div class="h-0.5 flex-1 {{ $i < count($pipeline) - 1 ? ($done ? 'bg-primary' : 'bg-gray-200') : 'bg-transparent' }}"></div>
            </div>
            <p class="mt-1.5 text-[11px] font-medium text-center {{ $done ? 'text-gray-900' : 'text-gray-400' }}">{{ \App\Models\Submission::STAGE_LABELS[$st] }}</p>
            @if($eventDates->has($st) && $st !== 'submitted')
                <p class="text-[10px] text-gray-400">{{ $eventDates[$st]->format('d M Y') }}</p>
            @elseif($st === 'submitted')
                <p class="text-[10px] text-gray-400">{{ optional($submission->submitted_at)->format('d M Y') }}</p>
            @endif
        </div>
    @endforeach
</div>
@if($isTerminal)
    <div class="mt-2">
        @include('partials.stage-badge', ['stage' => $submission->stage])
        <span class="ml-2 text-xs text-gray-500">{{ optional($submission->events->where('event', 'stage_changed')->where('to_stage', $submission->stage)->first()->created_at)->format('d M Y') }}</span>
    </div>
@endif
