<?php

namespace App\Filament\Paie\Widgets;

use App\Models\Paie\Payslip;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;

class PayrollCostCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Structure du Coût de la Paie';
    }

    protected function getComposition(): Composition
    {
        $currentPeriod = now()->format('Y-m');

        $payslips = Payslip::where('period', $currentPeriod)->get();

        $totalNetPaid = $payslips->sum('net_paid');
        $totalGross = $payslips->sum('gross_salary');
        $totalNetSocial = $payslips->sum('net_social');
        $totalEmployerCost = $payslips->sum('employer_cost');

        $employeeContributions = $totalGross - $totalNetSocial;
        $employerContributions = $totalEmployerCost - $totalGross;

        $slices = [];

        if ($totalNetPaid > 0) {
            $slices[] = CompositionSlice::make('Salaire Net Versé', (float) $totalNetPaid)
                ->color('success');
        }

        if ($employeeContributions > 0) {
            $slices[] = CompositionSlice::make('Cotisations Salariales', (float) $employeeContributions)
                ->color('warning');
        }

        if ($employerContributions > 0) {
            $slices[] = CompositionSlice::make('Cotisations Patronales', (float) $employerContributions)
                ->color('danger');
        }

        return Composition::make('Structure (€)')
            ->slices($slices)
            ->type('doughnut');
    }
}
