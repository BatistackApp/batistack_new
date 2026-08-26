<?php

namespace App\Filament\RH\Widgets;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\RH\TimeEntry;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class OvertimeVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Heures Supplémentaires';
    }

    protected function getItems(): array
    {
        $currentMonthEntries = TimeEntry::thisMonth()
            ->where('status', TimeEntryStatus::APPROVED)
            ->whereIn('type', [TimeEntryType::OVERTIME_25, TimeEntryType::OVERTIME_50])
            ->sum('hours');

        $lastMonthEntries = TimeEntry::whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->where('status', TimeEntryStatus::APPROVED)
            ->whereIn('type', [TimeEntryType::OVERTIME_25, TimeEntryType::OVERTIME_50])
            ->sum('hours');

        return [
            VarianceItem::make('Heures validées (Mois en cours)', (float) $currentMonthEntries)
                ->previous((float) $lastMonthEntries)
                ->formatUsing(fn (float $val) => number_format($val, 1, ',', ' ').' h')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '').number_format($val, 1, ',', ' ').' h'),
        ];
    }
}
