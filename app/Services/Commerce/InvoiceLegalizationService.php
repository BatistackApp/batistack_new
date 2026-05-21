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
     * @throws Exception
     * @throws \Throwable
     */
    public function legalizeCustomerInvoice(CustomerInvoice $invoice): void
    {
        $isDraft = $invoice->status === InvoiceStatus::DRAFT || $invoice->getOriginal('status') === InvoiceStatus::DRAFT;

        if (!$isDraft) {
            throw new Exception('Seule une facture en brouillon peut être légalisée et scellée.');
        }

        DB::transaction(function () use ($invoice) {
            // Attribution du numéro séquentiel définitif sans trou (ex: FC-2026-00123)
            $year = now()->year;
            $lastInvoice = CustomerInvoice::whereYear('created_at', $year)
                ->where('status', '!=', InvoiceStatus::DRAFT)
                ->orderBy('reference', 'desc')
                ->first();

            $sequence = 1;
            if ($lastInvoice && preg_match('/-(\d+)$/', $lastInvoice->reference, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            $definitiveRef = sprintf('FACT-%s-%05d', $year, $sequence);

            // On utilise updateQuietly pour ne pas re-déclencher les observers en boucle
            $invoice->updateQuietly([
                'reference' => $definitiveRef,
                'status' => InvoiceStatus::VALIDATED,
            ]);

            // Note BTP : C'est ici que l'on générerait un Hash cryptographique (chaînage)
            // de la facture avec la facture précédente pour répondre à la norme NF525 (Loi Anti-fraude).
        });
    }
}
