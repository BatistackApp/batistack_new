<?php

namespace App\Filament\Terrain\Widgets;

use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\TimeEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DailyActivityWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Activité du jour';

    protected function getStats(): array
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return [];
        }

        $today = now()->toDateString();

        $hoursToday = TimeEntry::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->sum('hours');

        $reservesToday = ChantierReserve::whereHas('chantier', fn ($q) => $q->forEmployee($employee))
            ->whereDate('created_at', $today)
            ->count();

        $logsToday = ChantierLog::whereHas('chantier', fn ($q) => $q->forEmployee($employee))
            ->whereDate('date', $today)
            ->count();

        $incidentsToday = ChantierLog::whereHas('chantier', fn ($q) => $q->forEmployee($employee))
            ->whereDate('date', $today)
            ->where('incident_reported', true)
            ->count();

        $stats = [
            Stat::make('Heures pointées', number_format($hoursToday, 1, ',', ' '))
                ->description('Aujourd\'hui')
                ->descriptionIcon(Phosphor::Timer)
                ->color($hoursToday > 0 ? 'primary' : 'gray'),

            Stat::make('Réserves créées', $reservesToday)
                ->description('Nouvelles réserves aujourd\'hui')
                ->descriptionIcon(Phosphor::WarningCircle)
                ->color($reservesToday > 0 ? 'warning' : 'gray'),

            Stat::make('Journal', $logsToday.' entrée(s)')
                ->description('Notes de chantier du jour')
                ->descriptionIcon(Phosphor::Notebook)
                ->color($logsToday > 0 ? 'info' : 'gray'),

            Stat::make('Incidents', $incidentsToday)
                ->description($incidentsToday > 0 ? 'Incidents déclarés' : 'Aucun incident')
                ->descriptionIcon(Phosphor::WarningOctagon)
                ->color($incidentsToday > 0 ? 'danger' : 'success'),
        ];

        return $stats;
    }
}
