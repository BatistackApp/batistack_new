<?php

namespace App\Observers\RH;

use App\Models\RH\ExpenseItem;
use App\Services\RH\ExpenseValidationService;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class ExpenseItemObserver
{
    protected ExpenseValidationService $validationService;

    public function __construct(ExpenseValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function saving(ExpenseItem $item): void
    {
        $validation = $this->validationService->validateItem($item);

        if (!$validation['is_valid']) {
            // Warn the user
            Notification::make()
                ->warning()
                ->title('Attention: Dépassement de politique de frais')
                ->body($validation['reason'])
                ->persistent()
                ->send();
            
            // Optionally set status to rejected or flag it for manual review
            // For now, we just keep it pending but warn the user.
            $item->status = 'pending';
            if (empty($item->rejection_reason)) {
                $item->rejection_reason = $validation['reason'];
            }
        } else {
            if ($item->status === 'pending' && $item->rejection_reason === $validation['reason']) {
                $item->rejection_reason = null; // Clear if it was fixed
            }
        }
    }

    public function saved(ExpenseItem $item): void
    {
        $this->updateReportTotal($item->expense_report_id);
    }

    public function deleted(ExpenseItem $item): void
    {
        $this->updateReportTotal($item->expense_report_id);
    }

    protected function updateReportTotal($reportId): void
    {
        if ($reportId) {
            $report = \App\Models\RH\ExpenseReport::find($reportId);
            if ($report) {
                $report->total_amount = $report->items()->sum('amount_ttc');
                $report->save();
            }
        }
    }
}
