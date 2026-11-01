<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $date,
        public readonly array $tasks,
        public readonly array $habits,
        public readonly array $attentionItems
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ringkasan harian Anda');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.daily-digest');
    }
}
