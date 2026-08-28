<?php

namespace App\Filament\Salarie\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SalarieStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return [];
        }

        // 1. Heures travaillées ce mois-ci
        $hoursWorked = $employee->getHoursWorkedThisMonth();

        // 2. Absences prises ce mois-ci
        $absences = $employee->getAbsencesThisMonth();

        // 3. Statut visite médicale
        $needsMedicalVisit = $employee->needsMedicalVisit();
        $medicalVisitStatus = $needsMedicalVisit ? 'À renouveler' : 'À jour';
        $medicalVisitColor = $needsMedicalVisit ? 'danger' : 'success';
        $medicalVisitIcon = $needsMedicalVisit ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle';

        return [
            Stat::make('Heures travaillées (ce mois)', $hoursWorked.' h')
                ->description('Total des heures déclarées')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Absences (ce mois)', $absences.' jour(s)')
                ->description('Jours de congés ou maladie')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Visite Médicale', $medicalVisitStatus)
                ->description($needsMedicalVisit ? 'Veuillez contacter les RH' : 'Tout est en ordre')
                ->descriptionIcon($medicalVisitIcon)
                ->color($medicalVisitColor),
        ];
    }
}
