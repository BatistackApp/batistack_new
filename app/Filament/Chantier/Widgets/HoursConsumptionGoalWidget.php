<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class HoursConsumptionGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Consommation Heures (Chantiers Actifs)';
    }

    protected function getGoal(): Goal
    {
        $analyticService = app(ChantierAnalyticService::class);
        $activeChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)->get();

        $totalBudgetHours = 0;
        $totalRealHours = 0;

        foreach ($activeChantiers as $chantier) {
            $metrics = $analyticService->getPerformanceMetrics($chantier);
            $totalBudgetHours += $metrics['hours']['budget'];
            $totalRealHours += $metrics['hours']['real'];
        }

        $target = $totalBudgetHours > 0 ? $totalBudgetHours : 1;

        return Goal::make('Heures consommées', $totalRealHours, $target)
            ->formatUsing(fn ($value) => round($value).' h')
            ->color($totalRealHours > $target ? 'danger' : 'primary');
    }
}
