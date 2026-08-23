<?php

namespace App\Observers\Banque;

use App\Enums\Accounting\JournalType;
use App\Enums\Commerce\InvoiceStatus;
use App\Models\Accounting\EcritureComptable;
use App\Models\Banque\BankReconciliation;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Services\Accounting\AccountingPlanService;
use App\Services\Accounting\EcritureComptableService;

class BankReconciliationObserver
{
    public function __construct(
        private AccountingPlanService $accountingPlanService = new AccountingPlanService(),
        private EcritureComptableService $ecritureService = new EcritureComptableService(),
    ) {}

    public function created(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
        $this->generateAccountingEntries($reconciliation);
    }

    public function updated(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
    }

    public function deleted(BankReconciliation $reconciliation): void
    {
        $this->updateInvoiceStatus($reconciliation);
        $this->reverseAccountingEntries($reconciliation);
    }

    private function generateAccountingEntries(BankReconciliation $reconciliation): void
    {
        $transaction = $reconciliation->bankTransaction;
        $invoice = $reconciliation->reconcilable;

        if (! $transaction || ! $invoice) {
            return;
        }

        $amount = (float) $reconciliation->amount_applied;

        if ($amount <= 0) {
            return;
        }

        try {
            $numeroPiece = $this->ecritureService->generateNumeroPiece(JournalType::BANQUE);
            $date = $transaction->date->toDateString();
            $libelle = 'Lettrage ' . ($invoice->reference ?? $invoice->number ?? 'N/A');

            if ($transaction->type === \App\Enums\Banque\TransactionType::DEBIT) {
                $compteCharge = $this->accountingPlanService->getChargeAccount();
                $compteBanque = $this->accountingPlanService->getBankAccount();

                $this->ecritureService->createBalancedPair(
                    [
                        'date_ecriture' => $date,
                        'date_piece' => $date,
                        'journal_type' => JournalType::BANQUE,
                        'numero_piece' => $numeroPiece,
                        'compte_numero' => $compteCharge,
                        'libelle' => $libelle,
                        'chantier_id' => $transaction->chantier_id,
                        'reconcilable_type' => get_class($reconciliation),
                        'reconcilable_id' => $reconciliation->id,
                    ],
                    [
                        'date_ecriture' => $date,
                        'date_piece' => $date,
                        'journal_type' => JournalType::BANQUE,
                        'numero_piece' => $numeroPiece,
                        'compte_numero' => $compteBanque,
                        'libelle' => $libelle,
                        'chantier_id' => $transaction->chantier_id,
                        'reconcilable_type' => get_class($reconciliation),
                        'reconcilable_id' => $reconciliation->id,
                    ]
                );
            } else {
                $compteBanque = $this->accountingPlanService->getBankAccount();
                $compteClient = $this->accountingPlanService->getClientAccount();

                $this->ecritureService->createBalancedPair(
                    [
                        'date_ecriture' => $date,
                        'date_piece' => $date,
                        'journal_type' => JournalType::BANQUE,
                        'numero_piece' => $numeroPiece,
                        'compte_numero' => $compteBanque,
                        'libelle' => $libelle,
                        'chantier_id' => $transaction->chantier_id,
                        'reconcilable_type' => get_class($reconciliation),
                        'reconcilable_id' => $reconciliation->id,
                    ],
                    [
                        'date_ecriture' => $date,
                        'date_piece' => $date,
                        'journal_type' => JournalType::BANQUE,
                        'numero_piece' => $numeroPiece,
                        'compte_numero' => $compteClient,
                        'libelle' => $libelle,
                        'chantier_id' => $transaction->chantier_id,
                        'reconcilable_type' => get_class($reconciliation),
                        'reconcilable_id' => $reconciliation->id,
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Silently ignore accounting entry generation errors
            // to not break existing reconciliation workflows
        }
    }

    private function reverseAccountingEntries(BankReconciliation $reconciliation): void
    {
        EcritureComptable::where('reconcilable_type', get_class($reconciliation))
            ->where('reconcilable_id', $reconciliation->id)
            ->delete();
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
