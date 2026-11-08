<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusChanged;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\SubmissionEvent;
use App\Services\MailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function __construct(private MailNotifier $mailer)
    {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $payments = Payment::with('submission.author')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = Payment::selectRaw('status, count(*) as total, coalesce(sum(amount),0) as amount')->groupBy('status')->get()
            ->keyBy('status');

        return view('admin.payments', compact('payments', 'status', 'summary'));
    }

    public function downloadProof(Payment $payment): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($payment->proof_path, 404);

        AuditLog::log(auth()->id(), 'payment.proof_download', 'Payment', $payment->id);

        return response()->download(Storage::disk('local')->path($payment->proof_path), 'bukti-'.$payment->invoice_no);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $payment->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $submission = $payment->submission;
        SubmissionEvent::log($submission, 'payment_verified', ['invoice' => $payment->invoice_no], $request->user()->id);
        AuditLog::log($request->user()->id, 'payment.verify', 'Payment', $payment->id);
        event(new PaymentStatusChanged($submission, $payment, 'verified', $request->user()->id));

        $this->mailer->notify('payment_verified', [
            'ref' => $submission->reference_no,
            'invoice' => $payment->invoice_no,
            'amount' => $payment->formatAmount(),
            'author_email' => $submission->author?->email,
            'author_id' => $submission->author_id,
            'url' => route('account.submissions.payment', $submission),
        ]);

        return back()->with('status', 'Pembayaran '.$payment->invoice_no.' terverifikasi.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $payment->update([
            'status' => 'rejected',
            'rejected_reason' => $data['reason'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $submission = $payment->submission;
        AuditLog::log($request->user()->id, 'payment.reject', 'Payment', $payment->id, ['reason' => $data['reason']]);
        event(new PaymentStatusChanged($submission, $payment, 'rejected', $request->user()->id));

        $this->mailer->notify('payment_rejected', [
            'ref' => $submission->reference_no,
            'invoice' => $payment->invoice_no,
            'reason' => $data['reason'],
            'author_email' => $submission->author?->email,
            'author_id' => $submission->author_id,
            'url' => route('account.submissions.payment', $submission),
        ]);

        return back()->with('status', 'Bukti pembayaran ditolak.');
    }
}
