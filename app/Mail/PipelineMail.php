<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email template tunggal (queued) untuk seluruh event pipeline.
 * $key: salah satu event key pada MailNotifier (mis. 'decision_accept').
 * $ctx: array context (ref, title, url, lines, cta_label, ...).
 */
class PipelineMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $key,
        public array $ctx,
        public string $lang = 'id',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->ctx['subject'] ?? 'Pemberitahuan JOSHNU',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pipeline',
            with: ['key' => $this->key, 'ctx' => $this->ctx, 'locale' => $this->lang],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
