@extends('layouts.workspace')
@section('title', 'Audit Log')
@section('content')
<div class="max-w-5xl mx-auto">
    <form method="GET" data-ajax-filter class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3 items-end mb-4 rounded-xl border border-gray-200 bg-white p-4">
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Aksi</label><input type="text" name="action" value="{{ $filters['action'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border"></div>
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Entity</label>
            <select name="entity_type" class="w-full rounded-lg border-gray-300 text-sm border">
                <option value="">Semua</option>
                @foreach(['Submission', 'Publication', 'Payment', 'Issue', 'User', 'JournalSetting', 'Review'] as $e)
                    <option value="{{ $e }}" @selected(($filters['entity_type'] ?? '') === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Dari</label><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border"></div>
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">S/d</label><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-lg border-gray-300 text-sm border"></div>
        <div class="sm:col-span-2 lg:col-span-4 xl:col-span-1">
            <a href="{{ route('admin.audit-logs.index') }}" data-ajax-reset class="inline-block w-full text-center border border-gray-300 text-gray-600 hover:text-gray-900 rounded-lg px-3 py-2 text-sm">Reset filter</a>
        </div>
    </form>

    <div data-ajax-results>
    <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="table-cards w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Aksi</th><th class="px-4 py-3">Entity</th><th class="px-4 py-3">User</th><th class="px-4 py-3">IP</th><th class="px-4 py-3">Meta</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap text-xs" data-label="Waktu">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="px-4 py-2 font-mono text-xs" data-label="Aksi">{{ $log->action }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600" data-label="Entity">{{ $log->entity_type }}#{{ $log->entity_id }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600" data-label="User">{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-400" data-label="IP">{{ $log->ip }}</td>
                        <td class="px-4 py-2 text-[10px] text-gray-400 max-w-[240px] truncate" data-label="Meta" data-full>{{ $log->meta ? json_encode($log->meta) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada log.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
