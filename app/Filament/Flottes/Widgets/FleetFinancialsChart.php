<?php

namespace App\Filament\Flottes\Widgets;

use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\VehicleContract;
use App\Models\Flottes\VehicleMaintenance;
use DB;
use Filament\Widgets\ChartWidget;

class FleetFinancialsChart extends ChartWidget
{
    protected ?string $heading = 'Évolution des Charges Mensuelles de Flotte (HT)';

    protected static ?int $sort = 4;


    protected function getData(): array
    {
        $months = [];
        $maintenanceData = [];
        $expenseData = [];
        $fuelData = [];
        $contractData = [];

        // Itération sur les 6 derniers mois
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $months[] = $date->translatedFormat('F Y');

            // 1. Somme des maintenances du mois
            $maintenanceData[] = (float) VehicleMaintenance::whereBetween('performed_at', [$monthStart, $monthEnd])->sum('cost_ht');

            // 2. Somme des péages et stationnements
            $expenseData[] = (float) FleetExpense::whereBetween('spent_at', [$monthStart, $monthEnd])->sum('amount_ht');

            // 3. Somme des consommations de carburant
            $fuelData[] = (float) FuelTransaction::whereBetween('purchased_at', [$monthStart, $monthEnd])->sum('cost_ht');

            // 4. Somme pro-rata mensuelle des assurances et leasings
            $contractData[] = (float) VehicleContract::where('start_date', '<=', $monthEnd)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $monthStart))
                ->sum(DB::raw('annual_cost_ht / 12'));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Carburant',
                    'data' => $fuelData,
                    'backgroundColor' => '#f59e0b', // Orange
                ],
                [
                    'label' => 'Maintenance',
                    'data' => $maintenanceData,
                    'backgroundColor' => '#3b82f6', // Bleu
                ],
                [
                    'label' => 'Péages & Parkings',
                    'data' => $expenseData,
                    'backgroundColor' => '#10b981', // Vert
                ],
                [
                    'label' => 'Assurances & Loyers',
                    'data' => $contractData,
                    'backgroundColor' => '#64748b', // Ardoise
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
