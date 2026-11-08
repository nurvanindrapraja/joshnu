@php
    $colors = [
        'submitted' => 'bg-gray-100 text-gray-700',
        'under_review' => 'bg-blue-50 text-blue-700',
        'decision' => 'bg-purple-50 text-purple-700',
        'revised' => 'bg-amber-50 text-amber-800',
        'rejected' => 'bg-red-50 text-red-700',
        'accepted' => 'bg-green-50 text-green-700',
        'scheduled' => 'bg-teal-50 text-teal-700',
        'published' => 'bg-emerald-100 text-emerald-800',
        'withdrawn' => 'bg-gray-200 text-gray-500',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colors[$stage] ?? 'bg-gray-100 text-gray-700' }}">
    {{ $label ?? ucfirst($stage) }}
</span>
