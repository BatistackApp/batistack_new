<?php

namespace App\Filament\Flottes\Widgets;

use App\Services\Flottes\FleetCostService;
use App\Models\Flottes\Vehicle;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;

class TcoVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'TCO Mensuel & Coûts d\'Exploitation';
    }

    protected function getItems(): array
    {
        $costService = app(FleetCostService::class);
        $vehicles = Vehicle::all();
        $currentMonth = now()->startOfMonth();
        $prevMonthStart = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        $currentTco = 0;
        $prevTco = 0;
        
        foreach ($vehicles as $v) {
            $currentTco += $costService->getMaintenanceCostsByPeriod($v, $currentMonth, now()) 
                + (float) $v->fuelTransactions()->where('purchased_at', '>=', $currentMonth)->sum('cost_ht')
                + (float) $v->expenses()->where('spent_at', '>=', $currentMonth)->sum('amount_ht')
                + ($v->getAnnualCost() / 12);

            $prevTco += $costService->getMaintenanceCostsByPeriod($v, $prevMonthStart, $prevMonthEnd) 
                + (float) $v->fuelTransactions()->whereBetween('purchased_at', [$prevMonthStart, $prevMonthEnd])->sum('cost_ht')
                + (float) $v->expenses()->whereBetween('spent_at', [$prevMonthStart, $prevMonthEnd])->sum('amount_ht')
                + ($v->getAnnualCost() / 12);
        }

        return [
            VarianceItem::make('Coût Mensuel (TCO)', (float) $currentTco)
                ->previous((float) $prevTco)
                ->formatUsing(fn ($val) => number_format($val, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn ($val) => ($val > 0 ? '+' : '') . number_format($val, 2, ',', ' ') . ' €')
        ];
    }
}
