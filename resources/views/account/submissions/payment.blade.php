@extends('layouts.workspace')
@section('title', __('account.payment').' — '.$submission->reference_no)
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900">Pembayaran APC</h2>
                <p class="text-xs font-mono text-gray-400 mt-0.5">{{ $submission->reference_no }}</p>
            </div>
            @if($payment)
                @include('partials.stage-badge', [
                    'stage' => ['pending' => 'submitted', 'proof_uploaded' => 'under_review', 'verified' => 'published', 'rejected' => 'rejected', 'paid' => 'published'][$payment->status] ?? 'submitted',
                    'label' => strtoupper($payment->status),
                ])
            @endif
        </div>

        @if(! $setting->apc_enabled)
            <p class="mt-4 text-sm text-gray-600">Jurnal ini saat ini tidak memungut APC (no charge).</p>
        @elseif(! $payment)
            <p class="mt-4 text-sm text-gray-600">Tagihan APC akan diterbitkan setelah naskah Anda <strong>diterima</strong> (accepted).</p>
        @else
            <div class="mt-5 grid sm:grid-cols-3 gap-4 text-sm">
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-400 uppercase font-semibold">Invoice</p><p class="font-mono mt-1">{{ $payment->invoice_no }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-400 uppercase font-semibold">Nominal</p><p class="mt-1 font-medium">{{ $payment->formatAmount() }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-400 uppercase font-semibold">Batas bayar</p><p class="mt-1">{{ optional($payment->due_at)->format('d M Y') ?? '—' }}</p></div>
            </div>

            @if(!empty($setting->apc_bank))
                <div class="mt-4 rounded-lg border border-gray-200 p-4 text-sm">
                    <p class="font-semibold text-gray-700 mb-2">Transfer bank:</p>
                    @foreach($setting->apc_bank as $bank)
                        <p class="text-gray-700">{{ $bank['bank'] }}: <span class="font-mono font-medium">{{ $bank['no'] }}</span> a.n. {{ $bank['name'] }}</p>
                    @endforeach
                    <p class="text-xs text-gray-400 mt-2">Mohon cantumkan nomor invoice pada catatan transfer.</p>
                </div>
            @endif

            @if(in_array($payment->status, ['pending', 'rejected']))
                @if($payment->status === 'rejected')
                    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        Bukti sebelumnya ditolak: {{ $payment->rejected_reason }}. Silakan kirim ulang.
                    </div>
                @endif
                <form method="POST" action="{{ route('account.submissions.payment.proof', $submission) }}" enctype="multipart/form-data" class="mt-4 space-y-3" x-data="{
                        proofStatus: '',
                        async onProofChange(e) {
                            const input = e.target;
                            const f = input.files[0];
                            if (!f) { proofStatus = ''; return; }
                            if (window.mayCompressImage && window.mayCompressImage(f)) {
                                proofStatus = 'Mengompres gambar…';
                                try {
                                    await window.processImageFile(input, f);
                                    proofStatus = 'Gambar >2 MB dikompres menjadi ' + Math.round(input.files[0].size / 1024) + ' KB.';
                                } catch (err) {
                                    proofStatus = 'Kompresi gagal, file asli tetap dipakai.';
                                }
                            } else {
                                proofStatus = '';
                            }
                        }
                    }">
                    @csrf
                    <div>
                        <x-input-label for="payer_name">Nama pengirim transfer</x-input-label>
                        <input id="payer_name" type="text" name="payer_name" value="{{ old('payer_name', $payment->payer_name) }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">
                        <x-input-error class="mt-1" :messages="$errors->get('payer_name')" />
                    </div>
                    <div>
                        <x-input-label for="file">Bukti transfer (PDF/JPG/PNG, max 5 MB)</x-input-label>
                        <input id="file" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" required x-on:change="onProofChange($event)" class="mt-1 block w-full text-sm">
                        <p class="mt-1 text-xs text-amber-600" x-show="proofStatus" x-text="proofStatus"></p>
                        <x-input-error class="mt-1" :messages="$errors->get('file')" />
                    </div>
                    <div>
                        <x-input-label for="note">Catatan (opsional)</x-input-label>
                        <input id="note" type="text" name="note" value="{{ old('note') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div class="flex justify-end">
                        <button class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-5 py-2">Kirim Bukti</button>
                    </div>
                </form>
            @else
                <div class="mt-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">
                    Pembayaran terverifikasi {{ optional($payment->verified_at)->format('d M Y H:i') }}. Terima kasih!
                    @if($payment->proof_path)
                        <a href="{{ route('account.submissions.payment.proof.download', $submission) }}" class="ml-2 underline">Lihat bukti</a>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
