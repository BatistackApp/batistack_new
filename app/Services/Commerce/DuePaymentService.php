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

    public function getOverdueCustomerInvoices(int $daysOverdue = 1)
    {
        return CustomerInvoice::where('status', \App\Enums\Commerce\InvoiceStatus::VALIDATED)
            ->where('due_date', '<=', now()->subDays($daysOverdue)->endOfDay())
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getUpcomingSupplierPayments(int $daysAhead = 7)
    {
        return \App\Models\Commerce\SupplierInvoice::where('status', \App\Enums\Commerce\InvoiceStatus::VALIDATED)
            ->where('due_date', '>=', now()->startOfDay())
            ->where('due_date', '<=', now()->addDays($daysAhead)->endOfDay())
            ->get();
    }

    public function getClientBalance(\App\Models\Tiers\ThirdParty $client)
    {
        $invoices = CustomerInvoice::where('client_id', $client->id)
            ->whereIn('status', [\App\Enums\Commerce\InvoiceStatus::VALIDATED, \App\Enums\Commerce\InvoiceStatus::PAID])
            ->get();

        $totalInvoiced = (float) $invoices->sum('total_ttc');
        
        $totalPaid = (float) \App\Models\Commerce\PaymentAllocation::where('payable_type', CustomerInvoice::class)
            ->whereIn('payable_id', $invoices->pluck('id'))
            ->sum('allocated_amount');

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'balance' => $totalInvoiced - $totalPaid,
        ];
    }

    public function getCustomerAgingReport()
    {
        $overdue = CustomerInvoice::where('status', \App\Enums\Commerce\InvoiceStatus::VALIDATED)
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        $summary = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        foreach ($overdue as $invoice) {
            $days = (int) $invoice->due_date->diffInDays(now());
            if ($days <= 30) {
                $summary['0-30'] += (float) $invoice->total_ttc;
            } elseif ($days <= 60) {
                $summary['31-60'] += (float) $invoice->total_ttc;
            } elseif ($days <= 90) {
                $summary['61-90'] += (float) $invoice->total_ttc;
            } else {
                $summary['90+'] += (float) $invoice->total_ttc;
            }
        }

        return ['summary' => $summary];
    }

    public function getTotalSupplierOutstanding()
    {
        return (float) \App\Models\Commerce\SupplierInvoice::whereIn('status', [
            \App\Enums\Commerce\InvoiceStatus::VALIDATED, 
            \App\Enums\Commerce\InvoiceStatus::LITIGE
        ])->sum('amount_ttc');
    }

    public function generatePaymentReminder(\App\Models\Tiers\ThirdParty $client, int $reminderLevel = 1)
    {
        $titles = [
            1 => 'PREMIÈRE RELANCE AMIABLE',
            2 => 'SECONDE RELANCE - MISE EN DEMEURE',
            3 => 'DERNIÈRE RELANCE AVANT CONTENTIEUX',
        ];

        $invoices = CustomerInvoice::where('client_id', $client->id)
            ->where('status', \App\Enums\Commerce\InvoiceStatus::VALIDATED)
            ->where('due_date', '<', now()->startOfDay())
            ->get();
            
        if ($invoices->isEmpty()) {
            return [
                'client' => $client,
                'level' => $reminderLevel,
                'title' => $titles[$reminderLevel] ?? 'RELANCE',
                'total_due' => 0.0,
                'invoices' => collect(),
            ];
        }

        return [
            'client' => $client,
            'level' => $reminderLevel,
            'title' => $titles[$reminderLevel] ?? 'RELANCE',
            'total_due' => (float) $invoices->sum('total_ttc'),
            'invoices' => $invoices,
        ];
    }

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
        $shouldSendEmail = false;
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $nextLevel, &$shouldSendEmail) {
            $invoice = CustomerInvoice::lockForUpdate()->find($invoice->id);
            
            if (!$invoice || $invoice->dunning_level >= $nextLevel || $invoice->is_fully_paid) {
                return;
            }

            // Appliquer les frais si on passe au niveau 3 (Mise en demeure)
            if ($nextLevel === 3) {
                $this->applyPenalties($invoice);
            }

            // Mettre à jour le statut de relance
            $invoice->update([
                'dunning_level' => $nextLevel,
                'last_dunning_at' => now(),
            ]);
            
            $shouldSendEmail = true;
        });

        if ($shouldSendEmail) {
            // Envoyer l'email
            // On récupère le contact principal ou email de facturation
            $contact = $invoice->client->contacts()->where('is_primary', true)->first();
            $email = ($contact && !empty($contact->email)) ? $contact->email : $invoice->client->email;

            if ($email) {
                Mail::to($email)->send(new InvoiceDunningMail($invoice, $nextLevel));
                Log::info("Invoice {$invoice->reference} advanced to dunning level {$nextLevel}.");
            } else {
                Log::warning("No email found for client {$invoice->client->id} on invoice {$invoice->id}");
            }
        }
    }

    protected function applyPenalties(CustomerInvoice $invoice)
    {
        // On vérifie qu'on n'a pas déjà ajouté l'indemnité (avec verrou)
        $hasFee = $invoice->items()->where('name', 'LIKE', '%Indemnité forfaitaire de recouvrement%')->lockForUpdate()->exists();
        
        if ($hasFee) {
            return;
        }

        // Récupérer la TVA 0% 
        $vatRate = VatRate::where('rate', 0)->first();
        
        // Calcul des pénalités sur le RESTE À PAYER
        $amountToPenalize = $invoice->amount_remaining;
        
        $days = $invoice->getDaysOverdue();
        $penalties = round($amountToPenalize * (self::DEFAULT_PENALTY_RATE / 100) * ($days / 365), 2);

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
