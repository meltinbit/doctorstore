<?php

namespace App\Mail;

use App\Models\Scan;
use App\Models\ShopifyStore;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreScanSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopifyStore $store,
        public Scan $scan
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scan Summary — '.$this->store->shop_name.' — '.now()->toDateString(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.store-scan-summary',
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
