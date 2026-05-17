<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $analyticService = app(ChantierAnalyticService::class);

        // 1. Chantiers Actifs
        $activeCount = Chantier::where('status', ChantierStatus::IN_PROGRESS)->count();

        // 2. Volume d'affaires en cours (Somme des budgets HT des chantiers actifs)
        $totalBudget = Chantier::where('status', ChantierStatus::IN_PROGRESS)->sum('budget_total_ht');

        // 3. Consommation moyenne du budget heures (KPI critique)
        $activeChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)->get();
        $avgProgress = $activeChantiers->avg(fn ($c) => $analyticService->getPerformanceMetrics($c)['progress']) ?? 0;

        return [
            Stat::make('Chantiers en cours', $activeCount)
                ->description('Projets en phase de production')
                ->descriptionIcon(Phosphor::HardHat)
                ->color('primary'),

            Stat::make('Encours Client HT', number_format($totalBudget, 0, ',', ' ').' €')
                ->description('Valeur totale des marchés actifs')
                ->descriptionIcon(Phosphor::CurrencyEur)
                ->color('success'),

            Stat::make('Avancement Moyen', round($avgProgress, 1).' %')
                ->description('Progression technique globale')
                ->descriptionIcon(Phosphor::ChartLineUp)
                ->chart([20, 35, 45, 42, 55, 60, 58]) // Exemple de tendance
                ->color(fn () => $avgProgress > 90 ? 'success' : 'warning'),
        ];
    }
}
