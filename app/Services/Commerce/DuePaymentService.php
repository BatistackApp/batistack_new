<?php

namespace App\Services\Commerce;

use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Core\VatRate;
use App\Mail\Commerce\InvoiceDunningMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DuePaymentService
{
    /**
     * Taux légal par défaut pour les pénalités de retard (ex: BCE + 10 points, disons 10% annuels par simplicité)
     */
    const DEFAULT_PENALTY_RATE = 10.0;

    /**
     * Indemnité forfaitaire pour frais de recouvrement (Loi LME)
     */
    const DUNNING_FLAT_FEE = 40.0;

    public function processOverdueInvoices()
    {
        Log::info("Starting Dunning Process for overdue invoices.");

        // J+30 : Mise en demeure (Niveau 3)
        $this->processLevel(30, 2, 3);

        // J+15 : Relance 2 (Niveau 2)
        $this->processLevel(15, 1, 2);

        // J+3 : Relance Amiable (Niveau 1)
        $this->processLevel(3, 0, 1);
        
        Log::info("Dunning Process finished.");
    }

    protected function processLevel(int $days, int $currentLevel, int $nextLevel)
    {
        $invoices = CustomerInvoice::eligibleForDunning($days, $currentLevel)
            ->with(['client.contacts', 'items'])
            ->get();

        foreach ($invoices as $invoice) {
            try {
                $this->processInvoice($invoice, $nextLevel, $days);
            } catch (\Exception $e) {
                Log::error("Error processing dunning for invoice {$invoice->id}: " . $e->getMessage());
            }
        }
    }

    protected function processInvoice(CustomerInvoice $invoice, int $nextLevel, int $daysOverdue)
    {
        // Appliquer les frais si on passe au niveau 3 (Mise en demeure)
        if ($nextLevel === 3) {
            $this->applyPenalties($invoice);
        }

        // Envoyer l'email
        // On récupère le contact principal ou email de facturation
        $contact = $invoice->client->contacts()->where('is_primary', true)->first();
        $email = $contact ? $contact->email : $invoice->client->email;

        if ($email) {
            Mail::to($email)->send(new InvoiceDunningMail($invoice, $nextLevel));
        } else {
            Log::warning("No email found for client {$invoice->client->id} on invoice {$invoice->id}");
        }

        // Mettre à jour le statut de relance
        $invoice->update([
            'dunning_level' => $nextLevel,
            'last_dunning_at' => now(),
        ]);
        
        Log::info("Invoice {$invoice->reference} advanced to dunning level {$nextLevel}.");
    }

    protected function applyPenalties(CustomerInvoice $invoice)
    {
        // On vérifie qu'on n'a pas déjà ajouté l'indemnité
        $hasFee = $invoice->items()->where('name', 'LIKE', '%Indemnité forfaitaire de recouvrement%')->exists();
        
        if ($hasFee) {
            return;
        }

        // Récupérer la TVA 0% 
        $vatRate = VatRate::where('rate', 0)->first();
        
        // Calcul des pénalités (Total TTC * 10% / 365 * jours de retard)
        // Les pénalités s'appliquent sur le TTC
        $days = $invoice->getDaysOverdue();
        $penalties = round($invoice->total_ttc * (self::DEFAULT_PENALTY_RATE / 100) * ($days / 365), 2);

        // 1. Ligne: Indemnité forfaitaire
        CustomerInvoiceItem::create([
            'customer_invoice_id' => $invoice->id,
            'name' => 'Indemnité forfaitaire de recouvrement (loi LME)',
            'quantity' => 1,
            'price_unit' => self::DUNNING_FLAT_FEE,
            'vat_rate_id' => $vatRate?->id,
            'total_ht' => self::DUNNING_FLAT_FEE,
        ]);

        // 2. Ligne: Pénalités de retard
        if ($penalties > 0) {
            CustomerInvoiceItem::create([
                'customer_invoice_id' => $invoice->id,
                'name' => 'Pénalités de retard',
                'quantity' => 1,
                'price_unit' => $penalties,
                'vat_rate_id' => $vatRate?->id,
                'total_ht' => $penalties,
            ]);
        }

        // Recalculer le total de la facture en ajoutant les frais (TVA 0% donc HT = TTC)
        $invoice->update([
            'total_ht' => $invoice->total_ht + self::DUNNING_FLAT_FEE + $penalties,
            'total_ttc' => $invoice->total_ttc + self::DUNNING_FLAT_FEE + $penalties,
        ]);
    }
}
