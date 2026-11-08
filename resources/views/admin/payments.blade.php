@extends('layouts.workspace')
@section('title', 'APC Payments')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        @foreach(['pending' => 'Menunggu', 'proof_uploaded' => 'Bukti Masuk', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $st => $label)
            @php($row = $summary->get($st))
            <a href="{{ route('admin.payments.index', ['status' => $st]) }}" class="rounded-xl border p-4 {{ $status === $st ? 'border-primary bg-primary-light' : 'border-gray-200 bg-white' }}">
                <p class="text-xl font-bold text-gray-900">{{ $row?->total ?? 0 }}</p>
                <p class="text-[11px] uppercase tracking-wide text-gray-500 mt-1">{{ $label }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $row ? number_format((float) $row->amount, 0, ',', '.') : '0' }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex gap-2 mb-3 text-sm">
        <a href="{{ route('admin.payments.index') }}" class="{{ $status === '' ? 'text-primary font-semibold' : 'text-gray-500 hover:text-gray-700' }}">Semua</a>
        @foreach(['pending', 'proof_uploaded', 'verified', 'rejected'] as $st)
            <a href="{{ route('admin.payments.index', ['status' => $st]) }}" class="{{ $status === $st ? 'text-primary font-semibold' : 'text-gray-500 hover:text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</a>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="table-cards w-full text-sm min-w-[720px]">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Naskah</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Pengirim</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $p)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs" data-label="Invoice">{{ $p->invoice_no }}</td>
                        <td class="px-4 py-3" data-label="Naskah">
                            <a href="{{ route('editor.submissions.show', $p->submission_id) }}" class="text-primary hover:underline text-xs">{{ $p->submission?->reference_no }}</a>
                            <p class="text-xs text-gray-400">{{ $p->submission?->author?->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700" data-label="Nominal">{{ $p->formatAmount() }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs" data-label="Pengirim">{{ $p->payer_name ?? '—' }}</td>
                        <td class="px-4 py-3" data-label="Status">
                            @include('partials.stage-badge', [
                                'stage' => ['pending' => 'submitted', 'proof_uploaded' => 'under_review', 'verified' => 'published', 'rejected' => 'rejected'][$p->status] ?? 'submitted',
                                'label' => ucfirst(str_replace('_', ' ', $p->status)),
                            ])
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap" data-label="Aksi">
                            @if($p->proof_path)
                                <a href="{{ route('admin.payments.proof.download', $p) }}" class="text-xs text-primary hover:underline">Bukti</a>
                            @endif
                            @if(in_array($p->status, ['pending', 'proof_uploaded']))
                                <form method="POST" action="{{ route('admin.payments.verify', $p) }}" class="inline ml-2">
                                    @csrf
                                    <button class="text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg px-3 py-1">Verifikasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payments.reject', $p) }}" class="inline ml-2" onsubmit="var r = prompt('Alasan penolakan:'); if (!r) return false; this.reason.value = r;">
                                    @csrf
                                    <input type="hidden" name="reason">
                                    <button class="text-xs bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-1">Tolak</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada tagihan APC.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</div>
@endsection
