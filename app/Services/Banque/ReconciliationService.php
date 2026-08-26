<?php

namespace App\Services\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\ExpenseItemStatus;
use App\Enums\RH\ExpensePaymentMethod;
use App\Enums\RH\ExpenseReportStatus;
use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Paie\Payslip;
use App\Models\RH\ExpenseItem;
use App\Models\RH\ExpenseReport;
use Illuminate\Database\Eloquent\Collection;

class ReconciliationService
{
    /**
     * Suggests potential invoices that match a given bank transaction.
     * Returns an array of suggestions with a score.
     */
    public function suggestMatches(BankTransaction $transaction): array
    {
        $suggestions = [];

        // Determine if we look for Customer Invoices (Credit) or Supplier Invoices/ExpenseReports (Debit)
        if ($transaction->amount > 0) {
            $candidates = CustomerInvoice::where('status', '!=', 'paid')->get();

            foreach ($candidates as $invoice) {
                $score = $this->calculateScore($transaction, $invoice, $invoice->client->name ?? '');

                if ($score > 0) {
                    $suggestions[] = [
                        'model' => $invoice,
                        'type' => CustomerInvoice::class,
                        'score' => $score,
                    ];
                }
            }
        } else {
            $candidates = SupplierInvoice::where('status', '!=', 'paid')->get();

            foreach ($candidates as $invoice) {
                $score = $this->calculateScore($transaction, $invoice, $invoice->supplier->name ?? '');

                if ($score > 0) {
                    $suggestions[] = [
                        'model' => $invoice,
                        'type' => SupplierInvoice::class,
                        'score' => $score,
                    ];
                }
            }

            // Also check for ExpenseReports
            $expenseReports = ExpenseReport::where('status', ExpenseReportStatus::VALIDATED)->get();

            foreach ($expenseReports as $report) {
                $score = $this->calculateScore($transaction, $report, $report->employee->first_name.' '.$report->employee->last_name);

                if ($score > 0) {
                    $suggestions[] = [
                        'model' => $report,
                        'type' => ExpenseReport::class,
                        'score' => $score,
                    ];
                }
            }

            // Also check for Corporate Card ExpenseItems (tickets individuels)
            $expenseItems = ExpenseItem::where('payment_method', ExpensePaymentMethod::CORPORATE_CARD->value)
                ->where('status', '!=', ExpenseItemStatus::REJECTED)
                ->get();

            // Filter out those already reconciled
            $reconciledItemIds = BankReconciliation::where('reconcilable_type', ExpenseItem::class)
                ->pluck('reconcilable_id')
                ->toArray();

            foreach ($expenseItems as $item) {
                if (in_array($item->id, $reconciledItemIds)) {
                    continue;
                }

                $score = $this->calculateExpenseItemScore($transaction, $item);

                if ($score > 0) {
                    $suggestions[] = [
                        'model' => $item,
                        'type' => ExpenseItem::class,
                        'score' => $score,
                    ];
                }
            }

            // Also check for Payslips
            $payslips = Payslip::where('status', '!=', PayslipStatus::PAID)->with('employee')->get();

            foreach ($payslips as $payslip) {
                if ($payslip->employee) {
                    $score = $this->calculateScore($transaction, $payslip, $payslip->employee->first_name.' '.$payslip->employee->last_name);

                    if ($score > 0) {
                        $suggestions[] = [
                            'model' => $payslip,
                            'type' => Payslip::class,
                            'score' => $score,
                        ];
                    }
                }
            }
        }

        // Sort suggestions by highest score first
        usort($suggestions, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $suggestions;
    }

    /**
     * Attempts to automatically reconcile a collection of transactions.
     * Returns the number of successfully reconciled transactions.
     */
    public function bulkReconcile(Collection $transactions, int $threshold = 80): int
    {
        $successCount = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->status !== TransactionStatus::PENDING) {
                continue;
            }

            $suggestions = $this->suggestMatches($transaction);

            if (count($suggestions) > 0) {
                $bestMatch = $suggestions[0];

                if ($bestMatch['score'] >= $threshold) {
                    BankReconciliation::create([
                        'bank_transaction_id' => $transaction->id,
                        'reconcilable_type' => $bestMatch['type'],
                        'reconcilable_id' => $bestMatch['model']->id,
                        'amount_applied' => abs($transaction->amount),
                    ]);

                    $transaction->update(['status' => TransactionStatus::RECONCILED]);
                    $successCount++;
                }
            }
        }

        return $successCount;
    }

    /**
     * Calculates a matching score between a transaction and an invoice.
     * Higher score = better match. Max score is roughly 100.
     */
    private function calculateScore(BankTransaction $transaction, $invoice, string $thirdPartyName): int
    {
        $score = 0;
        $absTransactionAmount = abs($transaction->amount);
        $invoiceAmountTtc = $invoice->total_ttc ?? $invoice->amount_ttc ?? $invoice->total_amount ?? $invoice->net_payable ?? 0;

        // 1. Exact amount match (+50 points)
        if (round($absTransactionAmount, 2) === round((float) $invoiceAmountTtc, 2)) {
            $score += 50;
        }

        // 2. Reference match in description (+40 points)
        $descriptionLower = strtolower($transaction->description);
        $reference = $invoice->reference ?? $invoice->period ?? null;
        if ($reference && str_contains($descriptionLower, strtolower($reference))) {
            $score += 40;
        }

        // 3. Third party name match in description (+10 points)
        if (! empty($thirdPartyName) && str_contains($descriptionLower, strtolower($thirdPartyName))) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Calculates a matching score between a transaction and an expense item.
     */
    private function calculateExpenseItemScore(BankTransaction $transaction, ExpenseItem $item): int
    {
        $score = 0;
        $absTransactionAmount = abs($transaction->amount);
        $itemAmountTtc = $item->amount_ttc ?? 0;

        // 1. Exact amount match (+50 points)
        if (round($absTransactionAmount, 2) === round((float) $itemAmountTtc, 2)) {
            $score += 50;
        }

        // 2. Date match (transaction date can be a few days after ticket date) (+30 points)
        if ($item->date && $transaction->date) {
            $diffDays = $transaction->date->diffInDays($item->date, false);
            // Si la transaction est passée entre 0 et 4 jours après le ticket
            if ($diffDays <= 0 && $diffDays >= -4) {
                $score += 30;
            } elseif (abs($diffDays) <= 7) {
                // Si la date est proche (1 semaine)
                $score += 10;
            }
        }

        // 3. Merchant name match in description (+20 points)
        $descriptionLower = strtolower($transaction->description);
        $merchant = $item->merchant ?? '';
        if (! empty($merchant) && str_contains($descriptionLower, strtolower($merchant))) {
            $score += 20;
        }

        return $score;
    }
}
