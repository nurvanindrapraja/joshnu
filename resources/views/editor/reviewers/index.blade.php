@extends('layouts.workspace')
@section('title', __('editor.reviewers'))
@section('content')
<div class="max-w-4xl mx-auto">
    <form method="GET" data-ajax-filter class="flex flex-col sm:flex-row sm:items-end gap-2 mb-4">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Cari</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, email, institusi, keahlian…" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <a href="{{ route('editor.reviewers.index') }}" data-ajax-reset class="border border-gray-300 text-gray-600 hover:text-gray-900 rounded-lg px-4 py-2 text-sm text-center">Reset</a>
    </form>

    <div data-ajax-results>
    <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="table-cards w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Reviewer</th>
                    <th class="px-4 py-3">Keahlian / Institusi</th>
                    <th class="px-4 py-3">Review selesai</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reviewers as $u)
                    <tr>
                        <td class="px-4 py-3" data-label="Reviewer">
                            <p class="font-medium text-gray-900">{{ $u->name }}</p>
                            <p class="text-xs text-gray-400">{{ $u->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs" data-label="Keahlian / Institusi">{{ $u->expertise ?: '—' }}<br>{{ $u->institution ?: '' }}</td>
                        <td class="px-4 py-3 text-gray-600" data-label="Review selesai">{{ $u->completed_reviews_count }}</td>
                        <td class="px-4 py-3" data-label="Status">
                            @if($u->is_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-700 bg-green-50 rounded-full px-2.5 py-0.5"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-full px-2.5 py-0.5"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Tidak ada reviewer yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reviewers->links() }}</div>
    </div>
    <p class="mt-4 text-xs text-gray-400">Assign reviewer langsung dari halaman detail naskah (tombol "Assign Reviewer +").</p>
</div>
@endsection
