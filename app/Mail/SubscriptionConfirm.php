<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Subscriber;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirm extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Konfirmasi langganan alert JOSHNU');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.subscription-confirm',
            with: [
                'subscriber' => $this->subscriber,
                'url' => route('subscribe.confirm', ['token' => $this->subscriber->token]),
            ],
        );
    }
}
