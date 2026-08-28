<?php

namespace App\Filament\Technicien\Widgets;

use App\Enums\Core\SignatureStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TechnicienKpiWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Vue d\'ensemble';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return [];
        }

        $todayCount = Intervention::whereHas('workers', fn ($q) => $q->where('employee_id', $employee->id))
            ->whereDate('scheduled_at', now()->toDateString())
            ->whereNotIn('status', [InterventionStatus::BROUILLON, InterventionStatus::ANNULEE])
            ->count();

        $inProgressCount = Intervention::whereHas('workers', fn ($q) => $q->where('employee_id', $employee->id))
            ->where('status', InterventionStatus::EN_COURS)
            ->count();

        $pendingSignatures = Intervention::whereHas('workers', fn ($q) => $q->where('employee_id', $employee->id))
            ->whereIn('status', [InterventionStatus::EN_COURS, InterventionStatus::TERMINEE])
            ->whereDoesntHave('signatures', fn ($q) => $q->where('status', SignatureStatus::SIGNED))
            ->count();

        $hoursThisMonth = InterventionWorker::where('employee_id', $employee->id)
            ->whereHas('intervention', fn ($q) => $q
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
            )
            ->sum('hours_worked');

        return [
            Stat::make('Interventions aujourd\'hui', $todayCount)
                ->description('Planifiées pour aujourd\'hui')
                ->descriptionIcon(Phosphor::CalendarCheck)
                ->color($todayCount > 0 ? 'primary' : 'gray'),

            Stat::make('En cours', $inProgressCount)
                ->description($inProgressCount > 0 ? 'Interventions actives' : 'Aucune intervention active')
                ->descriptionIcon(Phosphor::Wrench)
                ->color($inProgressCount > 0 ? 'warning' : 'success'),

            Stat::make('Signatures en attente', $pendingSignatures)
                ->description($pendingSignatures > 0 ? 'Nécessitent une signature client' : 'Toutes les signatures obtenues')
                ->descriptionIcon(Phosphor::Pen)
                ->color($pendingSignatures > 0 ? 'danger' : 'success'),

            Stat::make('Heures ce mois', number_format((float) $hoursThisMonth, 1, ',', ' '))
                ->description('Heures travaillées ce mois')
                ->descriptionIcon(Phosphor::Clock)
                ->color('info'),
        ];
    }
}
