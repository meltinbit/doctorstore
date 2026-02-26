<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScanAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{store: string, issues: int, quality_score: int|null, scan_id: int, store_id: int}>  $storeReports
     */
    public function __construct(
        public User $user,
        public array $storeReports
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Metafield Alert — '.now()->toDateString(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.scan-alert',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
