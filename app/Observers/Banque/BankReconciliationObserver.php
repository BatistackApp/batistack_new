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
            } elseif ($totalReconciled > 0.05) {
                if ($invoice->status !== InvoiceStatus::PARTIALLY_PAID) {
                    $invoice->status = InvoiceStatus::PARTIALLY_PAID;
                    $invoice->save();
                }
            } else {
                if (in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID])) {
                    // Revert to validated if it's no longer paid or partially paid (e.g., all reconciliations deleted)
                    $invoice->status = InvoiceStatus::VALIDATED;
                    $invoice->save();
                }
            }
        } elseif ($invoice instanceof \App\Models\RH\ExpenseReport) {
            $totalReconciled = $invoice->morphMany(BankReconciliation::class, 'reconcilable')->sum('amount_applied');
            $totalAmount = $invoice->total_amount ?? 0;

            if ($totalAmount > 0 && $totalReconciled >= $totalAmount - 0.05) { // 5 cents tolerance
                if ($invoice->status !== \App\Enums\RH\ExpenseReportStatus::PAID) {
                    $invoice->status = \App\Enums\RH\ExpenseReportStatus::PAID;
                    $invoice->save();
                }
            } else {
                if ($invoice->status === \App\Enums\RH\ExpenseReportStatus::PAID) {
                    $invoice->status = \App\Enums\RH\ExpenseReportStatus::VALIDATED;
                    $invoice->save();
                }
            }
        } elseif ($invoice instanceof \App\Models\Paie\Payslip) {
            $totalReconciled = $invoice->morphMany(BankReconciliation::class, 'reconcilable')->sum('amount_applied');
            $totalAmount = $invoice->net_payable ?? 0;

            if ($totalAmount > 0 && $totalReconciled >= $totalAmount - 0.05) { // 5 cents tolerance
                if ($invoice->status !== \App\Enums\Paie\PayslipStatus::PAID) {
                    $invoice->status = \App\Enums\Paie\PayslipStatus::PAID;
                    $invoice->save();
                }
            } else {
                if ($invoice->status === \App\Enums\Paie\PayslipStatus::PAID) {
                    $invoice->status = \App\Enums\Paie\PayslipStatus::VALIDATED;
                    $invoice->save();
                }
            }
        }
    }
}
