<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use DB;
use Exception;

class InvoiceLegalizationService
{
    /**
     * Verrouille légalement une facture client.
     * Cette action est irréversible (nécessitera un Avoir pour annulation).
     *
     * @throws Exception
     * @throws \Throwable
     */
    public function legalizeCustomerInvoice(CustomerInvoice $invoice): void
    {
        $isDraft = $invoice->status === InvoiceStatus::DRAFT || $invoice->getOriginal('status') === InvoiceStatus::DRAFT;

        if (! $isDraft) {
            throw new Exception('Seule une facture en brouillon peut être légalisée et scellée.');
        }

        DB::transaction(function () use ($invoice) {
            // Attribution du numéro séquentiel définitif sans trou (ex: FC-2026-00123) transféré à un autre service
            $year = date('Y');
            $definitiveRef = app(CustomerOrderService::class)->generateReferenceInvoice();
            // On utilise updateQuietly pour ne pas re-déclencher les observers en boucle

            $lastValidatedInvoice = CustomerInvoice::whereYear('created_at', $year)
                ->where('status', '!=', InvoiceStatus::DRAFT)
                ->whereNotNull('signature_hash') // On cherche la dernière facture scellée
                ->orderBy('reference', 'desc')
                ->first();

            // Note BTP : C'est ici que l'on générerait un Hash cryptographique (chaînage)
            // de la facture avec la facture précédente pour répondre à la norme NF525 (Loi Anti-fraude).
            $previousHash = $lastValidatedInvoice ? $lastValidatedInvoice->signature_hash : 'GENESIS';

            $invoiceDataToString = implode('|', [
                $definitiveRef,
                $invoice->created_at->toIso8601String(),
                number_format($invoice->total_ht, 2, '.', ''),
                number_format($invoice->total_ttc, 2, '.', ''),
                $invoice->client_id,
                $invoice->chantier_id,
            ]);

            // 3. Créer la "signature" en concaténant les données et le hash précédent
            $dataToHash = $invoiceDataToString.'|'.$previousHash;

            // 4. Calculer le nouveau hash
            $newHash = hash('sha256', $dataToHash);

            $invoice->updateQuietly([
                'reference' => $definitiveRef,
                'status' => InvoiceStatus::VALIDATED,
                'signature_hash' => $newHash,
            ]);
        });
    }
}
