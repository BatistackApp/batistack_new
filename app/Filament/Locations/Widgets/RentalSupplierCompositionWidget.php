<?php

namespace App\Filament\Locations\Widgets;

use App\Models\Locations\RentalContract;
use App\Enums\Locations\RentalStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use Illuminate\Support\Facades\DB;

class RentalSupplierCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Répartition du Budget (Coût Journalier) par Fournisseur';
    }

    protected function getComposition(): Composition
    {
        $contracts = RentalContract::with('supplier')
            ->whereIn('status', [RentalStatus::ACTIVE])
            ->get();

        $supplierCosts = [];

        foreach ($contracts as $contract) {
            $supplierName = $contract->supplier->name ?? 'Fournisseur inconnu';
            $cost = $contract->daily_cost_ht ?? 0;

            if (!isset($supplierCosts[$supplierName])) {
                $supplierCosts[$supplierName] = 0;
            }

            $supplierCosts[$supplierName] += $cost;
        }

        arsort($supplierCosts);

        $slices = [];
        $colors = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];
        $colorIndex = 0;

        foreach ($supplierCosts as $supplier => $cost) {
            if ($cost > 0) {
                $color = $colors[$colorIndex % count($colors)];
                $slices[] = CompositionSlice::make($supplier, round($cost, 2))
                    ->color($color);
                $colorIndex++;
            }
        }

        return Composition::make('Budget Journalier (€)')
            ->slices($slices)
            ->type('doughnut');
    }
}
