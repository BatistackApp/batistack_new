<?php

namespace App\Mail\Laser;

use App\Models\Laser\LaserInvoice;
use App\Services\Core\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaserInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public LaserInvoice $invoice,
        public string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Facture '.$this->invoice->reference);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.laser.invoice',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(DocumentService::getDisk(), $this->pdfPath)
                ->as('facture_'.$this->invoice->reference.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
