<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FleetStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Coût global de détention (TCO) cumulé de l'ensemble du parc roulant HT
        $totalTco = (float) Vehicle::sum('tco_cache') + (float) FleetExpense::sum('amount_ht');

        // 2. Trajets / Covoiturages d'équipages actuellement en cours d'exécution
        $activeAssignments = VehicleAssignment::where('status', AssignmentStatus::ACTIVE)->count();

        // 3. Matériel ou véhicules immobilisés pour panne ou sinistre carrosserie
        $immobilizedCount = Vehicle::where('status', VehicleStatus::MAINTENANCE)
            ->orWhere('status', VehicleStatus::BROKEN)
            ->count();

        // 4. Alertes de dérive énergétique et d'usage suspect (week-end ou hors pointage RH)
        $suspiciousFuel = FuelTransaction::where('is_suspicious', true)->count();
        $suspiciousExpenses = FleetExpense::where('is_suspicious', true)->count();
        $totalFraudAlerts = $suspiciousFuel + $suspiciousExpenses;

        return [
            Stat::make('Valeur du Parc (TCO)', number_format($totalTco, 2, ',', ' ').' €')
                ->description('Coût HT global d\'acquisition et détention')
                ->descriptionIcon(Phosphor::CurrencyEur)
                ->color('info'),

            Stat::make('Missions en cours', $activeAssignments)
                ->description('Équipages et utilitaires déployés')
                ->descriptionIcon(Phosphor::CarSimple)
                ->color('success'),

            Stat::make('Immobilisations', $immobilizedCount)
                ->description('Véhicules en atelier ou en panne')
                ->descriptionIcon(Phosphor::Wrench)
                ->color($immobilizedCount > 0 ? 'danger' : 'success'),

            Stat::make('Dérives & Soupçons', $totalFraudAlerts)
                ->description('Plein ou péage suspect détecté')
                ->descriptionIcon(Phosphor::WarningOctagon)
                ->color($totalFraudAlerts > 0 ? 'danger' : 'success'),
        ];
    }
}
