<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SupplierInvoice;
use App\Models\User;
use App\Notifications\Commerce\SupplierInvoiceAuditFailedNotification;
use App\Notifications\Commerce\SupplierInvoiceReadyForPaymentNotification;
use App\Services\Commerce\SupplierInvoiceAuditService;
use Illuminate\Support\Facades\Notification;

class SupplierInvoiceObserver
{
    public function __construct(
        protected SupplierInvoiceAuditService $auditService
    ) {}

    public function created(SupplierInvoice $invoice): void
    {
        // Définition de l'échéance (ex: 30 jours)
        if (! $invoice->due_date) {
            $invoice->updateQuietly([
                'due_date' => now()->addDays(30),
            ]);
        }

        // Log
        \Log::info("Facture fournisseur {$invoice->reference} enregistrée - Montant : {$invoice->amount_ht} € HT");
    }

    public function updated(SupplierInvoice $invoice): void
    {
        // Transition DRAFT → AUDIT : Lancement du contrôle 3 voies
        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatus::AUDIT) {
            $this->handleAuditStarted($invoice);
        }

        // Transition vers BON_A_PAYER : Audit réussi
        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatus::BON_A_PAYER) {
            $this->handleAuditPassed($invoice);
        }

        // Transition vers LITIGE : Audit échoué
        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatus::LITIGE) {
            $this->handleAuditFailed($invoice);
        }
    }

    /**
     * Action : Audit commencé.
     * Contrôle 3 voies automatique (BC / BR / Facture).
     */
    protected function handleAuditStarted(SupplierInvoice $invoice): void
    {
        // 1. Lancement de l'audit automatique
        $auditResult = $this->auditService->auditInvoice($invoice);

        // 2. Si pas de litige détecté → Passage en BON_A_PAYER
        if ($auditResult['is_valid']) {
            $invoice->updateQuietly([
                'status' => InvoiceStatus::BON_A_PAYER,
            ]);
        } else {
            // 3. Si litiges détectés → Passage en LITIGE + notification
            $invoice->updateQuietly([
                'status' => InvoiceStatus::LITIGE,
                'dispute_reason' => implode(' | ', $auditResult['disputes']),
            ]);

            // Notification au service Achats
            $this->notifyAuditFailure($invoice, $auditResult['disputes']);
        }

        \Log::info("Audit facture {$invoice->reference} lancé - Résultat : ".
            ($auditResult['is_valid'] ? 'OK' : 'LITIGE'));
    }

    /**
     * Action : Audit réussi.
     */
    protected function handleAuditPassed(SupplierInvoice $invoice): void
    {
        // Notification au service Compta/Trésorerie (les admins)
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new SupplierInvoiceReadyForPaymentNotification($invoice));

        \Log::info("Facture fournisseur {$invoice->reference} validée - Bon à payer");
    }

    /**
     * Action : Audit échoué (litiges détectés).
     */
    protected function handleAuditFailed(SupplierInvoice $invoice): void
    {
        \Log::warning("Facture fournisseur {$invoice->reference} en litige - Raisons : {$invoice->dispute_reason}");
    }

    /**
     * Notification du service Achats en cas de litige.
     */
    protected function notifyAuditFailure(SupplierInvoice $invoice, array $disputes): void
    {
        // Récupération des utilisateurs du service Achats (par exemple, rôle 'Procurement')
        $procurement = User::where('is_admin', true)->get();

        // Notification
        Notification::send(
            $procurement,
            new SupplierInvoiceAuditFailedNotification($invoice, $disputes)
        );
    }
}
