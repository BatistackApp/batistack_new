<?php

namespace App\Filament\Interventions\Widgets;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use Illuminate\Support\Carbon;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class InterventionSlaGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Taux de Résolution (SLA < 48h)';
    }

    protected function getGoal(): Goal
    {
        $interventions = Intervention::whereIn('status', [InterventionStatus::TERMINEE, InterventionStatus::FACTUREE])
            ->whereNotNull('completed_at')
            ->whereNotNull('scheduled_at')
            ->get();

        $total = $interventions->count();
        $passed = 0;

        foreach ($interventions as $intervention) {
            $scheduled = Carbon::parse($intervention->scheduled_at);
            $completed = Carbon::parse($intervention->completed_at);

            // Respected SLA if completed within 48h of scheduled_at
            if ($completed->diffInHours($scheduled) <= 48 && $completed->greaterThanOrEqualTo($scheduled)) {
                $passed++;
            } elseif ($completed->isBefore($scheduled)) {
                $passed++;
            }
        }

        $percentage = $total > 0 ? ($passed / $total) * 100 : 0;
        $color = $percentage >= 90 ? 'success' : ($percentage >= 70 ? 'warning' : 'danger');

        return Goal::make('Conformité SLA', $percentage, 100)
            ->formatUsing(fn (float $val) => number_format($val, 1).'%')
            ->color($color);
    }
}
