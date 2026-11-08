@extends('layouts.workspace')
@section('title', __('editor.submissions'))
@section('content')
<div class="rounded-xl border border-gray-200 bg-white p-4 mb-4">
    <form method="GET" data-ajax-filter class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Stage</label>
            <select name="stage" class="w-full rounded-lg border-gray-300 text-sm border">
                <option value="">Semua</option>
                @foreach(\App\Models\Submission::STAGE_LABELS as $val => $label)
                    <option value="{{ $val }}" @selected(($filters['stage'] ?? '') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Section</label>
            <select name="section" class="w-full rounded-lg border-gray-300 text-sm border">
                <option value="">Semua</option>
                @foreach($sections as $sec)
                    <option value="{{ $sec->id }}" @selected((string) ($filters['section'] ?? '') === (string) $sec->id)>{{ $sec->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Penulis</label>
            <input type="text" name="author" value="{{ $filters['author'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Kata kunci / ref</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Dari</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">S/d</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <div class="sm:col-span-2 md:col-span-3 xl:col-span-6">
            <a href="{{ route('editor.submissions.index') }}" data-ajax-reset class="inline-block border border-gray-300 text-gray-600 hover:text-gray-900 rounded-lg px-4 py-2 text-sm">Reset filter</a>
        </div>
    </form>
</div>

<div data-ajax-results>
<div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="table-cards table-aksi-compact w-full text-sm min-w-[760px]">
        <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-4 py-3">Ref</th>
                <th class="px-4 py-3">Judul</th>
                <th class="px-4 py-3">Penulis</th>
                <th class="px-4 py-3">Stage</th>
                <th class="px-4 py-3">Umur</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($submissions as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500" data-label="Ref">{{ $s->reference_no }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900" data-label="Judul" data-full>{{ Str::limit($s->title, 55) }}</td>
                    <td class="px-4 py-3 text-gray-600" data-label="Penulis">{{ $s->author?->name }}</td>
                    <td class="px-4 py-3" data-label="Stage">@include('partials.stage-badge', ['stage' => $s->stage])</td>
                    <td class="px-4 py-3 text-gray-500" data-label="Umur">{{ $s->daysInStage() }} hari</td>
                    <td class="px-4 py-3 text-right" data-label="Aksi">
                        <a href="{{ route('editor.submissions.show', $s) }}" class="inline-block bg-green-600 hover:bg-green-700 text-white rounded-lg px-3 py-1.5 text-xs font-medium">Buka</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada naskah.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $submissions->links() }}</div>
</div>
@endsection
