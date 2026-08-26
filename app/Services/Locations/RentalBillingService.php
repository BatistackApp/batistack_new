<?php

namespace App\Services\Locations;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\VatRate;
use App\Models\Locations\RentalContract;

class RentalBillingService
{
    /**
     * GÃ©nÃ¨re une facture fournisseur brouillon pour un contrat de location.
     */
    public function generateDraftInvoice(RentalContract $contract): SupplierInvoice
    {
        $invoice = new SupplierInvoice;
        $invoice->supplier_id = $contract->supplier_id;
        $invoice->reference = "LOC-{$contract->reference}-".now()->format('Ymd');

        // Facture en DRAFT (Brouillon) pour validation manuelle
        $invoice->status = InvoiceStatus::DRAFT;

        $invoice->due_date = today()->addDays(30);

        // Calcul des montants d'aprÃ¨s les lignes du contrat
        $totalHt = $contract->lines()->sum('total_price_ht');

        // Si aucune ligne, on utilise le coÃ»t journalier pour un cycle (ex: mensuel)
        if ($totalHt == 0) {
            $daysInCycle = match ($contract->billing_period->value) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30,
                'yearly' => 365,
                default => 30,
            };
            $totalHt = $contract->daily_cost_ht * $daysInCycle;
        }

        $invoice->amount_ht = $totalHt;
        $invoice->amount_ttc = $totalHt * 1.20;

        // Validation automatique si le montant TTC est infÃ©rieur au seuil dÃ©fini
        $autoValidateThreshold = config('locations.auto_validate_threshold', 500); // 500 euros par dÃ©faut
        if ($invoice->amount_ttc <= $autoValidateThreshold) {
            $invoice->status = InvoiceStatus::VALIDATED;
        }

        $invoice->save();

        $vatRate = VatRate::getDefault() ?? VatRate::getStandard();
        $vatRateId = $vatRate ? $vatRate->id : 1; // Fallback to 1 if not seeded

        // GÃ©nÃ©ration des lignes de factures
        if ($contract->lines()->exists()) {
            foreach ($contract->lines as $line) {
                $invoice->items()->create([
                    'name' => "Location: {$line->name}",
                    'quantity' => $line->quantity,
                    'price_unit' => $line->unit_price_ht,
                    'vat_rate_id' => $vatRateId,
                ]);
            }
        } else {
            $invoice->items()->create([
                'name' => "Location rÃ©currente {$contract->name}",
                'quantity' => 1,
                'price_unit' => $totalHt,
                'vat_rate_id' => $vatRateId,
            ]);
        }

        return $invoice;
    }
}
