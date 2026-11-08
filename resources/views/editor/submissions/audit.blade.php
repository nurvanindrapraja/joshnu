@extends('layouts.workspace')
@section('title', __('editor.audit').' — '.$submission->reference_no)
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900">{{ __('editor.audit') }} — #{{ $submission->reference_no }}</h2>
        <a href="{{ route('editor.submissions.show', $submission) }}" class="text-sm text-primary hover:underline">← Kembali ke naskah</a>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="table-cards w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Stage</th><th class="px-4 py-3">Aktor</th><th class="px-4 py-3">Meta</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($submission->events->reverse() as $ev)
                    <tr>
                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap" data-label="Waktu">{{ $ev->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2 font-medium text-gray-800" data-label="Event">{{ $ev->event }}</td>
                        <td class="px-4 py-2 text-gray-500" data-label="Stage">{{ $ev->from_stage ? ($ev->from_stage.' → '.$ev->to_stage) : '—' }}</td>
                        <td class="px-4 py-2 text-gray-500" data-label="Aktor">{{ $ev->actor?->name ?? 'Sistem' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-400" data-label="Meta" data-full>{{ $ev->meta ? json_encode($ev->meta) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
