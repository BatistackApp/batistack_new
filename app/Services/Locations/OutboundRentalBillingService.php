<?php

namespace App\Services\Locations;

use App\Models\Locations\OutboundRentalContract;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceLine;
use Carbon\Carbon;

class OutboundRentalBillingService
{
    public function generateInvoicesForActiveContracts(): void
    {
        $activeContracts = OutboundRentalContract::with(['lines', 'thirdParty'])
            ->where('status', 'active')
            ->get();

        foreach ($activeContracts as $contract) {
            $this->generateInvoiceIfDue($contract);
        }
    }

    public function generateInvoiceIfDue(OutboundRentalContract $contract): void
    {
        $startOfPeriod = match ($contract->billing_period) {
            'daily' => Carbon::now()->startOfDay(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $exists = CustomerInvoice::where('third_party_id', $contract->third_party_id)
            ->where('created_at', '>=', $startOfPeriod)
            ->where('notes', 'like', '%Contrat ' . $contract->reference . '%')
            ->exists();

        if ($exists) {
            return;
        }

        $this->createInvoice($contract);
    }

    protected function createInvoice(OutboundRentalContract $contract): void
    {
        $invoice = CustomerInvoice::create([
            'company_id' => $contract->company_id,
            'third_party_id' => $contract->third_party_id,
            'type' => 'invoice',
            'status' => 'draft',
            'reference' => 'INV-OUT-' . time(),
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'notes' => 'Location de matériel - Contrat ' . $contract->reference,
        ]);

        foreach ($contract->lines as $line) {
            $days = match ($contract->billing_period) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30, // Approx
                'yearly' => 365,
                default => 30,
            };

            CustomerInvoiceLine::create([
                'customer_invoice_id' => $invoice->id,
                'description' => 'Location: ' . ($line->fixedAsset->name ?? 'Équipement'),
                'quantity' => $days,
                'unit_price' => $line->daily_rate,
                'tax_rate' => 20.00,
            ]);
        }
    }
}
