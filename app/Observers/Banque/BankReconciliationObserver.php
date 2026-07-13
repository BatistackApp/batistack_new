<?php

namespace App\Observers\Banque;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Banque\BankReconciliation;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;

class BankReconciliationObserver
{
    public function created(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
    }

    public function updated(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
    }

    public function deleted(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
    }

    private function updateInvoiceStatus(BankReconciliation $reconciliation): void
    {
        $invoice = $reconciliation->reconcilable;

        if ($invoice instanceof CustomerInvoice || $invoice instanceof SupplierInvoice) {
            $totalReconciled = $invoice->morphMany(BankReconciliation::class, 'reconcilable')->sum('amount_applied');
            $totalTtc = $invoice->total_ttc ?? $invoice->amount_ttc ?? 0;

            if ($totalTtc > 0 && $totalReconciled >= $totalTtc - 0.05) { // 5 cents tolerance
                if ($invoice->status !== InvoiceStatus::PAID) {
                    $invoice->status = InvoiceStatus::PAID;
                    $invoice->save();
                }
            } else {
                if ($invoice->status === InvoiceStatus::PAID) {
                    // Revert to validated if it's no longer fully paid
                    $invoice->status = InvoiceStatus::VALIDATED;
                    $invoice->save();
                }
            }
        }
    }
}
