<?php

namespace App\Mail\Commerce;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceDunningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerInvoice $invoice,
        public int $level
    ) {}

    public function shouldSend(): bool
    {
        $this->invoice->refresh();

        if ($this->invoice->is_fully_paid || $this->invoice->dunning_level !== $this->level) {
            \Illuminate\Support\Facades\Log::info("Dunning mail aborted for invoice {$this->invoice->reference}: status changed.");
            return false;
        }

        return true;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->level) {
            1 => "Relance amiable : Facture impayée {$this->invoice->reference}",
            2 => "Seconde relance : Facture impayée {$this->invoice->reference}",
            3 => "MISE EN DEMEURE : Facture impayée {$this->invoice->reference}",
            default => "Facture {$this->invoice->reference}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commerce.invoice-dunning',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
