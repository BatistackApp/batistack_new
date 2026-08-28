<?php

namespace App\Services\Interventions;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Interventions\Intervention;

class InterventionBillingService
{
    /**
     * Génère une facture client à partir d'une intervention terminée.
     */
    public function generateInvoice(Intervention $intervention): ?CustomerInvoice
    {
        if ($intervention->status !== InterventionStatus::TERMINEE) {
            return null;
        }

        // Créer la facture brouillon
        $invoice = CustomerInvoice::create([
            'client_id' => $intervention->third_party_id,
            'chantier_id' => $intervention->chantier_id,
            'responsable_id' => 1, // Fix pour le responsable de la facture
            'status' => InvoiceStatus::DRAFT,
            'type' => InvoiceType::SIMPLE,
            'reference' => 'TEMP-'.$intervention->id, // Souvent géré par un Observer (ex: FAC-2026-...)
        ]);

        if ($intervention->type === InterventionType::FORFAIT) {
            // Ligne unique pour le forfait
            CustomerInvoiceItem::create([
                'customer_invoice_id' => $invoice->id,
                'name' => "Intervention Forfaitaire {$intervention->reference}",
                'quantity' => 1,
                'price_unit' => $intervention->flat_rate_price,
                'total_ht' => $intervention->flat_rate_price,
                'vat_rate_id' => 1, // Fix: TVA standard par defaut
            ]);
        } else {
            // Facturation en régie
            // 1. Facturation des matériaux
            foreach ($intervention->materials as $material) {
                CustomerInvoiceItem::create([
                    'customer_invoice_id' => $invoice->id,
                    'item_id' => $material->item_id,
                    'name' => $material->item->name ?? 'Pièce détachée',
                    'quantity' => $material->quantity,
                    'price_unit' => $material->selling_price,
                    'total_ht' => $material->quantity * $material->selling_price,
                    'vat_rate_id' => 1,
                ]);
            }

            // 2. Facturation de la main d'oeuvre (simplifié, on suppose qu'on a un taux de revente)
            // Si hourly_selling_price n'existe pas, on pourrait le mettre à 0 ou gérer via une table de taux.
            // Pour l'exemple, on crée la ligne s'il y a des heures.
            $totalHours = $intervention->workers->sum('hours_worked');
            if ($totalHours > 0) {
                CustomerInvoiceItem::create([
                    'customer_invoice_id' => $invoice->id,
                    'name' => "Main d'œuvre (Intervention)",
                    'quantity' => $totalHours,
                    'price_unit' => 50, // Taux horaire de vente par défaut à configurer
                    'total_ht' => $totalHours * 50,
                    'vat_rate_id' => 1,
                ]);
            }
        }

        $intervention->update([
            'status' => InterventionStatus::FACTUREE,
        ]);

        return $invoice;
    }
}
