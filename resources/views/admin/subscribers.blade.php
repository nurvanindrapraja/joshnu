@extends('layouts.workspace')
@section('title', 'Subscribers')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-3 gap-3 mb-5">
        <a href="{{ route('admin.subscribers.index') }}" class="rounded-xl border p-4 {{ $status === '' ? 'border-primary bg-primary-light' : 'border-gray-200 bg-white' }}">
            <p class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">Semua</p>
        </a>
        <a href="{{ route('admin.subscribers.index', ['status' => 'confirmed']) }}" class="rounded-xl border p-4 {{ $status === 'confirmed' ? 'border-primary bg-primary-light' : 'border-gray-200 bg-white' }}">
            <p class="text-xl font-bold text-gray-900">{{ $stats['confirmed'] }}</p>
            <p class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">Terkonfirmasi</p>
        </a>
        <a href="{{ route('admin.subscribers.index', ['status' => 'pending']) }}" class="rounded-xl border p-4 {{ $status === 'pending' ? 'border-primary bg-primary-light' : 'border-gray-200 bg-white' }}">
            <p class="text-xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
            <p class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">Menunggu Konfirmasi</p>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.subscribers.index') }}" data-ajax-filter class="flex gap-2 mb-3">
        @if($status !== '')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Cari email…"
               class="rounded-lg border-gray-300 text-sm w-full max-w-xs border focus:border-primary focus:ring-primary">
        <button type="button" data-ajax-reset
                class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200">Reset</button>
    </form>

    <div data-ajax-results>

    <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="table-cards w-full text-sm min-w-[560px]">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Berlangganan Sejak</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscribers as $s)
                    <tr>
                        <td class="px-4 py-3 text-gray-400 text-xs" data-label="#">{{ $subscribers->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-gray-700" data-label="Email">{{ $s->email }}</td>
                        <td class="px-4 py-3" data-label="Status">
                            @if($s->is_confirmed)
                                <span class="inline-flex items-center rounded-full bg-green-50 border border-green-200 px-2.5 py-0.5 text-xs font-medium text-green-700">Terkonfirmasi</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs" data-label="Berlangganan Sejak">{{ $s->created_at?->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada pendaftar berlangganan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscribers->hasPages())
        <div class="mt-4 flex items-center justify-between text-sm">
            <p class="text-xs text-gray-400">
                Menampilkan {{ $subscribers->firstItem() }}–{{ $subscribers->lastItem() }} dari {{ $subscribers->total() }}
            </p>
            <nav class="flex gap-1 items-center">
                @if($subscribers->onFirstPage())
                    <span class="px-2.5 py-1 text-gray-300">‹</span>
                @else
                    <a href="{{ $subscribers->previousPageUrl() }}" class="px-2.5 py-1 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-600">‹</a>
                @endif
                <span class="px-2.5 py-1 rounded-lg bg-primary text-white text-xs font-medium">{{ $subscribers->currentPage() }}/{{ $subscribers->lastPage() }}</span>
                @if($subscribers->hasMorePages())
                    <a href="{{ $subscribers->nextPageUrl() }}" class="px-2.5 py-1 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-600">›</a>
                @else
                    <span class="px-2.5 py-1 text-gray-300">›</span>
                @endif
            </nav>
        </div>
    @endif
    </div>
</div>
@endsection
