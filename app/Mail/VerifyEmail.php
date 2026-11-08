<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifikasi Email — JOSHNU');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verify-email',
            with: [
                'user' => $this->user,
                'url' => $this->verificationUrl(),
            ],
        );
    }

    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addDays(2),
            ['id' => $this->user->getAuthIdentifier(), 'hash' => sha1($this->user->getEmailForVerification())]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
