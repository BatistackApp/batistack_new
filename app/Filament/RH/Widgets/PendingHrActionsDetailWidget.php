<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\ExpenseReport;
use App\Models\RH\Abscence;
use App\Enums\RH\ExpenseReportStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\RH\Resources\ExpenseReportResource;
use App\Filament\RH\Resources\AbscenceResource;

class PendingHrActionsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Actions RH en attente';
    }

    protected function getDetails(): array
    {
        $details = [];

        // 1. Notes de frais en attente de validation
        $expenseReports = ExpenseReport::with('employee')
            ->where('status', ExpenseReportStatus::SUBMITTED)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($expenseReports as $report) {
            $details[] = Detail::make('Note de Frais - ' . ($report->employee->first_name ?? '') . ' ' . ($report->employee->last_name ?? ''), number_format($report->total_amount, 2, ',', ' ') . ' €')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning')
                ->url(ExpenseReportResource::getUrl('edit', ['record' => $report]));
        }

        // 2. Absences à approuver (is_paid is null)
        $absences = Abscence::with('employee')
            ->whereNull('is_paid')
            ->orderBy('start_date', 'asc')
            ->get();

        foreach ($absences as $absence) {
            $details[] = Detail::make('Absence - ' . ($absence->employee->first_name ?? '') . ' ' . ($absence->employee->last_name ?? ''), $absence->start_date->format('d/m/Y'))
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(AbscenceResource::getUrl('edit', ['record' => $absence]));
        }

        return array_slice($details, 0, 10);
    }
}
