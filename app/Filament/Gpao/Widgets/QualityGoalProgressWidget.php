<?php

namespace App\Filament\Gpao\Widgets;

use App\Models\Gpao\QualityCheck;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class QualityGoalProgressWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Taux de Qualité (First Time Yield)';
    }

    protected function getGoal(): Goal
    {
        $startOfMonth = now()->startOfMonth();

        $totalChecks = QualityCheck::where('checked_at', '>=', $startOfMonth)->count();
        $passedChecks = QualityCheck::where('checked_at', '>=', $startOfMonth)
            ->where('status', 'passed')
            ->count();

        $percentage = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0;

        $color = $percentage >= 95 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');

        return Goal::make('Objectif de conformité', $percentage, 95)
            ->formatUsing(fn (float $val) => number_format($val, 1).'%')
            ->color($color);
    }
}
