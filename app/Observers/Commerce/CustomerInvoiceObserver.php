<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Jobs\Commerce\SendCustomerInvoiceEmailJob;
use App\Models\Commerce\CustomerInvoice;
use App\Notifications\Commerce\InvoiceGeneratedNotification;
use App\Notifications\Commerce\InvoicePaidNotification;
use App\Notifications\Customer\FactureRecueNotification;
use App\Notifications\Customer\PaiementRecuNotification;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\InvoiceLegalizationService;

class CustomerInvoiceObserver
{
    public function __construct(
        protected CommerceDocumentationService $documentService,
        protected InvoiceLegalizationService $legalizationService
    ) {}

    public function creating(CustomerInvoice $invoice): void
    {
        // Définition automatique de l'échéance (Conditions de paiement : 30 jours)
        if (! $invoice->due_date) {
            $invoice->due_date = now()->addDays(30);
        }
    }

    public function created(CustomerInvoice $invoice): void
    {
        // Génération automatique du PDF via un Job
        GenerateDocumentJob::dispatch('invoice', $invoice);

        if ($invoice->customer_order_id) {
            $invoice->order->update([
                'status' => OrderStatus::BILLED,
            ]);
        }

        // Log d'audit
        \Log::info("Facture {$invoice->reference} créée pour {$invoice->client->name}");
    }

    /**
     * @throws \Throwable
     */
    public function updated(CustomerInvoice $invoice): void
    {
        // Validation : Légalisation (attribution du numéro final) et expédition
        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatus::VALIDATED && $invoice->getOriginal('status') === InvoiceStatus::DRAFT) {
            $this->handleInvoiceValidated($invoice);
        }

        // Paiement : Notification de remerciement et clôture
        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatus::PAID) {
            $this->handleInvoicePaid($invoice);
        }
    }

    /**
     * Actions liées à la validation définitive de la facture (légalisation, envoi).
     *
     * @throws \Throwable
     */
    protected function handleInvoiceValidated(CustomerInvoice $invoice): void
    {
        // 1. Légalisation : on attribue le vrai numéro
        $this->legalizationService->legalizeCustomerInvoice($invoice);

        // 2. Regénérer le PDF avec le vrai numéro
        GenerateDocumentJob::dispatch('invoice', $invoice);

        // 3. Dispatch de l'envoi email au client avec pièce jointe
        SendCustomerInvoiceEmailJob::dispatch($invoice);

        // 4. Notification In-App ou standard
        if ($invoice->client && $invoice->client->primaryContact) {
            $invoice->client->primaryContact->notify(new InvoiceGeneratedNotification($invoice));
            $invoice->client->primaryContact->notify(new FactureRecueNotification($invoice));
        }

        \Log::info("Facture {$invoice->reference} validée et légalisée.");
    }

    /**
     * Actions suite au règlement de la facture par le client.
     */
    protected function handleInvoicePaid(CustomerInvoice $invoice): void
    {
        if ($invoice->client && $invoice->client->primaryContact) {
            $invoice->client->primaryContact->notify(new InvoicePaidNotification($invoice));
            $invoice->client->primaryContact->notify(new PaiementRecuNotification($invoice));
        }

        \Log::info("Facture {$invoice->reference} soldée.");
    }
}
