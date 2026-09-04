<?php

namespace App\Mail\Core;

use App\Models\Core\SignatureSigner;
use App\Services\Core\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MultiSignatureRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SignatureSigner $signer,
        public ?string $documentPath = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Demande de Signature Numérique — '.$this->signer->signature->signable_type,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.core.multi-signature-requested',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->documentPath) {
            return [
                Attachment::fromStorageDisk(DocumentService::getDisk(), $this->documentPath),
            ];
        }

        return [];
    }
}
