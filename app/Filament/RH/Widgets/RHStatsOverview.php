<?php

namespace App\Filament\RH\Widgets;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class RHStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Effectif total actif
        $activeEmployees = Employee::where('is_active', true)->count();

        // 2. Pointages en attente de validation (Urgence pour la paie)
        $pendingApprovals = TimeEntry::where('status', TimeEntryStatus::SUBMITTED)->count();

        // 3. Montant total des indemnités GD du mois en cours
        $gdTotalThisMonth = TimeEntry::where('is_grand_deplacement', true)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('gd_allowance_amount');

        return [
            Stat::make('Effectif Actif', $activeEmployees)
                ->description('Salariés actuellement en poste')
                ->descriptionIcon(Phosphor::Users)
                ->color('info'),

            Stat::make('À Valider', $pendingApprovals)
                ->description('Pointages en attente d\'approbation')
                ->descriptionIcon(Phosphor::ClockClockwise)
                ->color($pendingApprovals > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => "window.location.href='/rh/time-entries?activeTab=submitted'",
                ]),

            Stat::make('Indemnités GD (Mois)', number_format($gdTotalThisMonth, 2, ',', ' ').' €')
                ->description('Total des forfaits 96€ générés')
                ->descriptionIcon(Phosphor::Coins)
                ->color('success'),
        ];
    }
}
