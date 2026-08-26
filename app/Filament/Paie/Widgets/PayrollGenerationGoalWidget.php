<?php

namespace App\Filament\Paie\Widgets;

use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class PayrollGenerationGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Avancement de la Paie du mois';
    }

    protected function getGoal(): Goal
    {
        $currentPeriod = now()->format('Y-m');

        // On compte le nombre de bulletins générés sur la période courante
        $payslipsGenerated = Payslip::where('period', $currentPeriod)->count();

        // On considère que l'objectif est de générer un bulletin pour chaque employé (actif)
        $totalEmployees = Employee::count();

        $target = $totalEmployees > 0 ? $totalEmployees : 1;

        return Goal::make('Bulletins générés', $payslipsGenerated, $target);
    }
}
