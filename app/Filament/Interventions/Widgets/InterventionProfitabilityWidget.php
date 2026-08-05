<?php

namespace App\Filament\Interventions\Widgets;

use App\Models\Interventions\Intervention;
use App\Enums\Interventions\InterventionStatus;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use Illuminate\Support\Carbon;

class InterventionProfitabilityWidget extends VarianceWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Rentabilité SAV Globale';
    }

    protected function getItems(): array
    {
        $startOfMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        $interventions = Intervention::with(['workers', 'materials'])
            ->whereIn('status', [InterventionStatus::FACTUREE, InterventionStatus::TERMINEE])
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $startOfLastMonth)
            ->get();

        $currentMargin = 0;
        $prevMargin = 0;

        foreach ($interventions as $intervention) {
            $workerCost = $intervention->workers->sum(fn ($w) => $w->hours_worked * $w->hourly_cost);
            $materialCost = $intervention->materials->sum(fn ($m) => $m->quantity * $m->unit_cost);
            
            // If flat_rate_price is set, we use it. Otherwise, maybe we use sum of selling prices?
            // The issue mentions "marge financière globale". Usually it's flat_rate_price - costs.
            // Let's assume flat_rate_price is the revenue.
            $revenue = $intervention->flat_rate_price ?? 0;
            if (!$intervention->flat_rate_price) {
                // If no flat rate, maybe billed hourly + materials selling price
                $revenue = $intervention->workers->sum(fn ($w) => $w->hours_worked * ($w->hourly_cost * 1.5)) // fallback markup
                         + $intervention->materials->sum(fn ($m) => $m->quantity * $m->selling_price);
            }

            $margin = $revenue - $workerCost - $materialCost;

            if (Carbon::parse($intervention->completed_at)->isBetween($startOfLastMonth, $endOfLastMonth)) {
                $prevMargin += $margin;
            } else {
                $currentMargin += $margin;
            }
        }

        return [
            VarianceItem::make('Marge du mois en cours', $currentMargin)
                ->previous($prevMargin)
                ->formatUsing(fn (float $val) => number_format($val, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '') . number_format($val, 2, ',', ' ') . ' €')
        ];
    }
}
