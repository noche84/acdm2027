<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Newsletter $newsletter,
        public ?Subscriber $subscriber = null,
        public bool $isTest = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->isTest ? '[TEST] ' : '').$this->newsletter->subject,
            // Les réponses des abonnés arrivent chez l'ACDM, pas dans la boîte d'envoi.
            replyTo: [new Address(config('acdm.contact.email'), 'ACDM')],
        );
    }

    public function headers(): Headers
    {
        if (! $this->subscriber) {
            return new Headers;
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<'.route('newsletter.unsubscribe.oneclick', $this->subscriber->token).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
            with: [
                'events' => $this->newsletter->upcomingEvents(),
                'unsubscribeUrl' => $this->subscriber?->unsubscribe_url ?? route('newsletter.form'),
            ],
        );
    }
}
