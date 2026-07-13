<?php

namespace App\Services\Banque;

use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
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

        // Determine if we look for Customer Invoices (Credit) or Supplier Invoices (Debit)
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
        }

        // Sort suggestions by highest score first
        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return $suggestions;
    }

    /**
     * Calculates a matching score between a transaction and an invoice.
     * Higher score = better match. Max score is roughly 100.
     */
    private function calculateScore(BankTransaction $transaction, $invoice, string $thirdPartyName): int
    {
        $score = 0;
        $absTransactionAmount = abs($transaction->amount);
        $invoiceAmountTtc = $invoice->total_ttc ?? $invoice->amount_ttc ?? 0;

        // 1. Exact amount match (+50 points)
        if (round($absTransactionAmount, 2) === round((float) $invoiceAmountTtc, 2)) {
            $score += 50;
        }

        // 2. Reference match in description (+40 points)
        $descriptionLower = strtolower($transaction->description);
        if ($invoice->reference && str_contains($descriptionLower, strtolower($invoice->reference))) {
            $score += 40;
        }

        // 3. Third party name match in description (+10 points)
        if (!empty($thirdPartyName) && str_contains($descriptionLower, strtolower($thirdPartyName))) {
            $score += 10;
        }

        return $score;
    }
}
