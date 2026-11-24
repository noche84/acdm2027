<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmSubscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmez votre inscription à la newsletter de l\'ACDM',
            // Les réponses des abonnés arrivent chez l'ACDM, pas dans la boîte d'envoi.
            replyTo: [new Address(config('acdm.contact.email'), 'ACDM')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirm-subscription');
    }
}
