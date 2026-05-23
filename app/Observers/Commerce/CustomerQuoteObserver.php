<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureType;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Models\Commerce\CustomerQuote;
use App\Notifications\Commerce\QuoteAcceptedNotification;
use App\Notifications\Commerce\QuoteRejectedNotification;
use App\Notifications\Commerce\QuoteSentNotification;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Core\SignatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CustomerQuoteObserver
{
    public function __construct(
        protected CommerceDocumentationService $documentService
    ) {}

    public function creating(CustomerQuote $quote): void
    {
        if (! $quote->expires_at) {
            $quote->expires_at = Carbon::now()->addDays(30);
        }
    }

    public function updated(CustomerQuote $quote): void
    {
        // Transition DRAFT → SENT : Génération PDF et notification client
        if ($quote->isDirty('status') && $quote->status === QuoteStatus::SENT) {
            $this->handleQuoteSent($quote);
        }

        // Transition SENT → SIGNED : Devis accepté
        if ($quote->isDirty('status') && $quote->status === QuoteStatus::SIGNED) {
            $this->handleQuoteSigned($quote);
        }

        // Transition vers REJECTED
        if ($quote->isDirty('status') && $quote->status === QuoteStatus::REJECTED) {
            $this->handleQuoteRejected($quote);
        }
    }

    public function deleted(CustomerQuote $quote): void
    {
        Storage::disk('public')->delete("commerce/quotes/devis_{$quote->reference}.pdf");
    }

    /**
     * Action : Devis envoyé au client.
     */
    protected function handleQuoteSent(CustomerQuote $quote): void
    {
        // 1. Génération PDF du devis
        GenerateDocumentJob::dispatch('quote', $quote);

        // 2. Notification client
        if ($quote->client && $quote->client->primaryContact) {
            $quote->client->primaryContact->notify(new QuoteSentNotification($quote));
        }

        app(SignatureService::class)->requestSignature($quote, SignatureType::AUTOGRAPH);

        // 3. Log d'audit
        \Log::info("Devis {$quote->reference} envoyé au client {$quote->client->name}");
    }

    /**
     * Action : Devis accepté par le client.
     */
    protected function handleQuoteSigned(CustomerQuote $quote): void
    {
        // Notification au service commercial
        if ($quote->user) {
            $quote->user->notify(
                new QuoteAcceptedNotification($quote)
            );
        }

        // Log
        \Log::info("Devis {$quote->reference} accepté le {$quote->signed_at}");
    }

    /**
     * Action : Devis rejeté.
     */
    protected function handleQuoteRejected(CustomerQuote $quote): void
    {
        // Notification au commercial
        if ($quote->user) {
            $quote->user->notify(
                new QuoteRejectedNotification($quote)
            );
        }

        \Log::warning("Devis {$quote->reference} rejeté par {$quote->client->name}");
    }
}
