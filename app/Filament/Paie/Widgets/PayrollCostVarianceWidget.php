<?php

namespace App\Filament\Paie\Widgets;

use App\Models\Paie\Payslip;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class PayrollCostVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Masse Salariale (Coût Employeur)';
    }

    protected function getItems(): array
    {
        $currentPeriod = now()->format('Y-m');
        $previousPeriod = now()->subMonth()->format('Y-m');

        $currentCost = Payslip::where('period', $currentPeriod)->sum('employer_cost');
        $prevCost = Payslip::where('period', $previousPeriod)->sum('employer_cost');

        return [
            VarianceItem::make('Coût total ce mois', (float) $currentCost)
                ->previous((float) $prevCost)
                ->formatUsing(fn (float $val) => number_format($val, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '') . number_format($val, 2, ',', ' ') . ' €')
        ];
    }
}
