<?php

namespace App\Filament\Gpao\Widgets;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use Illuminate\Support\Carbon;

class TrsVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Taux de Rendement Synthétique (TRS)';
    }

    protected function getItems(): array
    {
        $currentMonthStart = now()->startOfMonth();
        $prevMonthStart = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        $currentTrs = $this->calculateTrs($currentMonthStart, now());
        $prevTrs = $this->calculateTrs($prevMonthStart, $prevMonthEnd);

        return [
            VarianceItem::make('TRS Global', $currentTrs)
                ->previous($prevTrs)
                ->formatUsing(fn (float $val) => number_format($val, 2) . '%')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '') . number_format($val, 2) . '%')
                ->color(fn (float $current, float $prev) => $current >= $prev ? 'success' : 'danger')
        ];
    }

    private function calculateTrs(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): float
    {
        $orders = ManufacturingOrder::where('status', ManufacturingStatus::COMPLETED)
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $totalProduced = $orders->sum('quantity_produced');
        $totalPlanned = $orders->sum('quantity_planned');

        if ($totalPlanned <= 0) {
            return 0;
        }

        return ($totalProduced / $totalPlanned) * 100;
    }
}
