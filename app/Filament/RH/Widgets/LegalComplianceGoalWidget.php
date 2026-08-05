<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Employee;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class LegalComplianceGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Conformité Légale (VM/Habilitations)';
    }

    protected function getGoal(): Goal
    {
        $activeEmployees = Employee::active()->get();
        $totalActive = $activeEmployees->count();
        $compliantCount = 0;

        foreach ($activeEmployees as $employee) {
            if (!$employee->needsMedicalVisit()) {
                $compliantCount++;
            }
        }

        $target = $totalActive > 0 ? $totalActive : 1;

        $percentage = $totalActive > 0 ? ($compliantCount / $totalActive) * 100 : 0;
        $color = $percentage >= 95 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');

        return Goal::make('Dossiers à jour', $compliantCount, $target)
            ->color($color);
    }
}
