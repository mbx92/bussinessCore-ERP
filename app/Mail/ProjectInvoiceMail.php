<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $invoice,
        public readonly array $project,
        public readonly string $recipientName,
        public readonly string $pdfBinary,
        public readonly string $pdfFileName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice '.$this->invoice['number'].' - '.$this->project['name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-invoice',
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $this->pdfBinary,
                $this->pdfFileName,
            )->withMime('application/pdf'),
        ];
    }
}
