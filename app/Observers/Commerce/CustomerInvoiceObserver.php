<?php

namespace App\Observers\Commerce;

use App\Models\Commerce\CustomerInvoice;
use App\Notifications\Commerce\InvoicePaidNotification;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\InvoiceLegalizationService;

class CustomerInvoiceObserver
{
    public function __construct(
        protected CommerceDocumentationService $documentService,
        protected InvoiceLegalizationService $legalizationService
    ) {}

    public function created(CustomerInvoice $invoice): void
    {
        // Définition automatique de l'échéance (Conditions de paiement : 30 jours)
        if (! $invoice->due_date) {
            $invoice->updateQuietly([
                'due_date' => now()->addDays(30),
            ]);
        }

        // Génération automatique du PDF
        try {
            $pdfPath = $this->documentService->generateInvoicePdf($invoice);
            $invoice->updateQuietly([
                'pdf_path' => $pdfPath,
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur génération PDF facture {$invoice->reference}", ['error' => $e->getMessage()]);
        }

        // Log d'audit
        \Log::info("Facture {$invoice->reference} créée pour {$invoice->client->name}");
    }

    public function updated(CustomerInvoice $invoice): void {}

    /**
     * Action : Facture légalisée (passage en VALIDATED).
     * Verrouillage anti-fraude NF525.
     */
    protected function handleInvoiceLegalized(CustomerInvoice $invoice): void
    {
        // 1. Attribution du numéro séquentiel définitif par le service de légalisation
        try {
            $this->legalizationService->legalizeCustomerInvoice($invoice);
        } catch (\Exception $e) {
            \Log::error("Erreur légalisation facture {$invoice->reference}", ['error' => $e->getMessage()]);

            return;
        } catch (\Throwable $e) {
            \Log::error("Erreur légalisation facture {$invoice->reference}", ['error' => $e->getMessage()]);
        }

        // 2. Notification au client (facture prête à payer)
        if ($invoice->client && $invoice->client->primaryContact) {
            $invoice->client->primaryContact->notify(
                new InvoiceGeneratedNotification($invoice)
            );
        }

        // 3. Log d'audit
        \Log::info("Facture {$invoice->reference} légalisée - Numéro de scellement : {$invoice->reference}");
    }

    /**
     * Action : Facture totalement payée.
     */
    protected function handleInvoicePaid(CustomerInvoice $invoice): void
    {
        // Notification au client (quittance)
        if ($invoice->client && $invoice->client->primaryContact) {
            $invoice->client->primaryContact->notify(
                new InvoicePaidNotification($invoice)
            );
        }

        // Log
        \Log::info("Facture {$invoice->reference} payée intégralement le ".now()->format('d/m/Y'));
    }

    /**
     * Action : Facture annulée.
     * (Nécessite un avoir, pas suppression directe)
     */
    protected function handleInvoiceCancelled(CustomerInvoice $invoice): void
    {
        \Log::warning("Facture {$invoice->reference} annulée - Raison : {$invoice->cancellation_reason}");
    }
}
