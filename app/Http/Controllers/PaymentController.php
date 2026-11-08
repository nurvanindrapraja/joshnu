<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusChanged;
use App\Models\AuditLog;
use App\Models\JournalSetting;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Services\MailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private MailNotifier $mailer)
    {
    }

    public function show(Request $request, Submission $submission): View
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        $payment = $submission->payment()->first();
        $setting = JournalSetting::current();

        return view('account.submissions.payment', [
            'submission' => $submission,
            'payment' => $payment,
            'setting' => $setting,
        ]);
    }

    public function storeProof(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);

        $payment = $submission->payment()->firstOrFail();
        abort_unless(in_array($payment->status, ['pending', 'rejected'], true), 422, 'Tagihan sudah diproses.');

        $data = $request->validate([
            'payer_name' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,png,jpg,jpeg'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->store('payments', 'local');

        $payment->update([
            'status' => 'proof_uploaded',
            'payer_name' => $data['payer_name'],
            'proof_path' => $path,
            'rejected_reason' => null,
        ]);

        SubmissionEvent::log($submission, 'payment_proof_uploaded', ['invoice' => $payment->invoice_no], $request->user()->id);
        AuditLog::log($request->user()->id, 'payment.proof_uploaded', 'Payment', $payment->id);
        event(new PaymentStatusChanged($submission, $payment, 'proof_uploaded', $request->user()->id));

        $this->mailer->notify('payment_proof_received', [
            'ref' => $submission->reference_no,
            'invoice' => $payment->invoice_no,
            'amount' => $payment->formatAmount(),
            'payer' => $data['payer_name'],
            'url' => route('admin.payments.index'),
        ]);

        return redirect()->route('account.submissions.payment', $submission)
            ->with('status', 'Bukti pembayaran terkirim. Menunggu verifikasi redaksi.');
    }

    public function downloadProof(Request $request, Submission $submission): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        $payment = $submission->payment()->firstOrFail();
        abort_unless($payment->proof_path, 404);

        return response()->download(Storage::disk('local')->path($payment->proof_path), 'bukti-'.$payment->invoice_no);
    }
}
