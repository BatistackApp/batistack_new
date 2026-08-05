<?php

namespace App\Filament\Locations\Widgets;

use App\Models\Locations\RentalContract;
use App\Enums\Locations\RentalStatus;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use Illuminate\Support\Carbon;

class RentalGlobalCostVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Coûts de Location Globaux';
    }

    protected function getItems(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        $currentCost = $this->calculateCostForPeriod($startOfMonth, $endOfMonth);
        $prevCost = $this->calculateCostForPeriod($startOfLastMonth, $endOfLastMonth);

        return [
            VarianceItem::make('Coût ce mois', $currentCost)
                ->previous($prevCost)
                ->formatUsing(fn (float $val) => number_format($val, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '') . number_format($val, 2, ',', ' ') . ' €')
        ];
    }

    private function calculateCostForPeriod(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): float
    {
        $cost = 0;

        // Retrieve contracts that overlap with the given period
        $contracts = RentalContract::where('start_date', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $start);
            })
            ->whereIn('status', [RentalStatus::ACTIVE, RentalStatus::TERMINATED, RentalStatus::SUSPENDED])
            ->get();

        foreach ($contracts as $contract) {
            $startDate = Carbon::parse($contract->start_date)->max($start);
            $endDate = $contract->end_date ? Carbon::parse($contract->end_date)->min($end) : $end;

            if ($startDate->lessThanOrEqualTo($endDate)) {
                $days = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
                $cost += $days * ($contract->daily_cost_ht ?? 0);
            }
        }

        return $cost;
    }
}
