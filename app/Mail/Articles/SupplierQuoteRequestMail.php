<?php

namespace App\Mail\Articles;

use App\Models\Articles\Item;
use App\Models\Core\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierQuoteRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Item $item) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $companyName = Company::first()?->name ?? 'Notre Entreprise';

        return new Envelope(
            subject: "Demande de prix / Disponibilité - {$companyName} - Réf: {$this->item->reference}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.articles.quote_request',
            with: [
                'company' => Company::first(),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
