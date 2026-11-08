<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsToAjax;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, Submission $submission): Response
    {
        abort_unless($request->user()->isEditorial(), 403);

        $data = $request->validate([
            'recipient_type' => ['required', 'in:author,editor,reviewer,internal'],
            'recipient_id' => ['nullable', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $message = Message::create([
            'submission_id' => $submission->id,
            'sender_id' => $request->user()->id,
            'recipient_type' => $data['recipient_type'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
            'created_at' => now(),
        ]);

        $this->notifyRecipients($message, $submission, $data);

        return $this->ajaxOrRedirect($request, 'Pesan terkirim.', back());
    }

    /**
     * Komunikasi via aplikasi: in-app notification ke penerima.
     * (Email ke penulis tetap dikirim sebagai best-effort; in-app pasti sampai.)
     */
    private function notifyRecipients(Message $message, Submission $submission, array $data): void
    {
        $ref = $submission->reference_no;
        $title = $message->subject ?: 'Pesan dari redaksi';

        // email ke penulis bila pesan non-internal
        if ($data['recipient_type'] === 'author' && ! $data['is_internal']) {
            $author = $submission->author;
            if ($author) {
                \Illuminate\Support\Facades\Mail::to($author->email)->queue(
                    new \App\Mail\PipelineMail('editor_message', [
                        'subject' => 'Pesan dari redaksi JOSHNU: '.$submission->reference_no,
                        'ref' => $submission->reference_no,
                        'title' => $submission->title,
                        'url' => route('account.submissions.show', $submission),
                        'lines' => [['Pesan' => $data['subject'] ? $data['subject'] : 'Pesan editor', 'Isi' => $data['body']]],
                        'author_email' => $author->email,
                        'author_id' => $author->id,
                    ])
                );
                Notification::send($author->id, 'editor_message', [
                    'submission_ref' => $ref,
                    'title' => $title,
                    'url' => route('account.submissions.show', $submission),
                ]);
            }

            return;
        }

        match ($data['recipient_type']) {
            'reviewer' => $submission->reviews()
                ->whereNotNull('reviewer_id')
                ->whereIn('status', ['invited', 'accepted', 'in_progress', 'overdue'])
                ->get()
                ->map(fn ($r) => $r->reviewer_id)
                ->unique()
                ->each(fn (int $uid) => Notification::send($uid, 'editor_message', [
                    'submission_ref' => $ref,
                    'title' => $title,
                    'url' => route('review.show', $submission),
                ])),
            'editor' => User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('role', ['eic', 'section_editor', 'admin']))
                ->where('id', '!=', $message->sender_id)
                ->get()
                ->each(fn (User $u) => Notification::send($u->id, 'editor_message', [
                    'submission_ref' => $ref,
                    'title' => $title,
                    'url' => route('editor.submissions.show', $submission),
                ])),
            default => null, // internal: hanya catatan, tanpa notifikasi
        };
    }
}
