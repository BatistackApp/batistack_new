<?php

namespace App\Filament\RH\Widgets;

use App\Enums\RH\ExpenseReportStatus;
use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
use App\Models\RH\Abscence;
use App\Models\RH\ExpenseReport;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

class PendingHrActionsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

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
            $details[] = Detail::make('Note de Frais - '.($report->employee->first_name ?? '').' '.($report->employee->last_name ?? ''), number_format($report->total_amount, 2, ',', ' ').' €')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning')
                ->url(ExpenseReportResource::getUrl('edit', ['record' => $report]));
        }

        // 2. Absences à approuver (non payées)
        $absences = Abscence::with('employee')
            ->where('is_paid', false)
            ->orderBy('start_date', 'asc')
            ->get();

        foreach ($absences as $absence) {
            $details[] = Detail::make('Absence - '.($absence->employee->first_name ?? '').' '.($absence->employee->last_name ?? ''), $absence->start_date->format('d/m/Y'))
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(EmployeeResource::getUrl('edit', ['record' => $absence->employee_id]));
        }

        return array_slice($details, 0, 10);
    }
}
