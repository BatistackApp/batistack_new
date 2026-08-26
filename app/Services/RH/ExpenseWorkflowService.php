<?php

namespace App\Services\RH;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Enums\RH\ExpenseItemStatus;
use App\Enums\RH\ExpenseReportStatus;
use App\Models\RH\ExpenseReport;
use Exception;
use Illuminate\Support\Facades\DB;

class ExpenseWorkflowService
{
    /**
     * Submit an expense report for validation.
     */
    public function submit(ExpenseReport $report): void
    {
        if ($report->status !== ExpenseReportStatus::DRAFT) {
            throw new Exception('Seul un rapport en brouillon peut être soumis.');
        }

        if ($report->items()->count() === 0) {
            throw new Exception('Le rapport de frais ne contient aucune ligne de dépense.');
        }

        $report->update(['status' => ExpenseReportStatus::SUBMITTED]);
    }

    /**
     * Validate an expense report.
     * All items must be either approved or rejected.
     */
    public function validate(ExpenseReport $report): void
    {
        DB::transaction(function () use (&$report) {
            $report = ExpenseReport::where('id', $report->id)->lockForUpdate()->firstOrFail();

            if ($report->status !== ExpenseReportStatus::SUBMITTED) {
                throw new Exception('Le rapport doit être soumis avant de pouvoir être validé.');
            }

            $pendingItems = $report->items()->where('status', ExpenseItemStatus::PENDING)->count();

            if ($pendingItems > 0) {
                throw new Exception("Impossible de valider la note de frais : il reste {$pendingItems} ligne(s) en attente.");
            }

            // Calculate total amount based ONLY on approved items
            $totalAmount = $report->items()
                ->where('status', ExpenseItemStatus::APPROVED)
                ->sum('amount_ttc');

            // Handle attached advances
            $advances = $report->advances()->where('status', ExpenseAdvanceStatus::PAID)->lockForUpdate()->get();
            $advanceDeducted = $advances->sum('amount');

            foreach ($advances as $advance) {
                $advance->update(['status' => ExpenseAdvanceStatus::DEDUCTED]);
            }

            $report->update([
                'status' => ExpenseReportStatus::VALIDATED,
                'total_amount' => $totalAmount,
                'advance_deducted' => $advanceDeducted,
            ]);
        });
    }

    /**
     * Reject an entire expense report.
     */
    public function reject(ExpenseReport $report, string $reason): void
    {
        if ($report->status !== ExpenseReportStatus::SUBMITTED) {
            throw new Exception('Seul un rapport soumis peut être rejeté.');
        }

        // Ideally, we would save the rejection reason on the report or via activity log.
        // For simplicity, we just change the status.
        $report->update(['status' => ExpenseReportStatus::REJECTED]);
    }

    /**
     * Mark an expense report as paid.
     */
    public function pay(ExpenseReport $report): void
    {
        if ($report->status !== ExpenseReportStatus::VALIDATED) {
            throw new Exception("Le rapport doit être validé avant d'être payé.");
        }

        $report->update(['status' => ExpenseReportStatus::PAID]);
    }
}
