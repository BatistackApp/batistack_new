<?php

namespace App\Mail\Commerce;

use App\Models\Tiers\ThirdParty;
use App\Services\Core\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerStatementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ThirdParty $client,
        public string $pdfPath,
        public string $periodLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Releve de factures - {$this->client->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commerce.customer-statement',
            with: [
                'client' => $this->client,
                'periodLabel' => $this->periodLabel,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(DocumentService::getDisk(), $this->pdfPath)
                ->as('releve_factures_'.$this->client->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
