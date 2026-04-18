<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $code
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Grand Lion Hotel confirmation code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-code',
        );
    }
}
