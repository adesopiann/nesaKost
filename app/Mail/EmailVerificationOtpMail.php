<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $otp)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode Verifikasi Email eKost');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-otp',
            with: ['otp' => $this->otp, 'expiresInMinutes' => 5],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
