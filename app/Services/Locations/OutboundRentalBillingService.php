<?php

namespace App\Services\Locations;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Locations\OutboundRentalContract;
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

        if ($contract->status !== 'active') {
            return;
        }

        if ($contract->lines->isEmpty()) {
            return;
        }

        $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');

        $exists = CustomerInvoice::where('billing_key', $billingKey)->exists();

        if ($exists) {
            return;
        }

        $this->createInvoice($contract, $billingKey);
    }

    protected function createInvoice(OutboundRentalContract $contract, string $billingKey): void
    {
        $invoice = CustomerInvoice::create([
            'client_id' => $contract->third_party_id,
            'type' => InvoiceType::SIMPLE,
            'status' => InvoiceStatus::DRAFT,
            'reference' => 'INV-OUT-'.time(),
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'responsable_id' => auth()->id() ?? 1,
            'notes' => 'Location de matériel - Contrat '.$contract->reference,
            'billing_key' => $billingKey,
        ]);

        foreach ($contract->lines as $line) {
            $days = match ($contract->billing_period) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30, // Approx
                'yearly' => 365,
                default => 30,
            };

            CustomerInvoiceItem::create([
                'customer_invoice_id' => $invoice->id,
                'name' => 'Location: '.($line->fixedAsset->name ?? 'Équipement'),
                'quantity' => $days,
                'price_unit' => $line->daily_rate,
                'vat_rate_id' => 1,
            ]);
        }

        $contract->update(['last_invoice_id' => $invoice->id]);
    }
}
