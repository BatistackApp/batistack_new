<?php

namespace App\Filament\Terrain\Widgets;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\TimeEntry;
use App\Services\Chantiers\ChantierComplianceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TerrainDashboardWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return [];
        }

        $activeChantiers = Chantier::forEmployee($employee)
            ->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::AWAITING_RECEPTION, ChantierStatus::SUSPENDED])
            ->count();

        $openReserves = ChantierReserve::whereHas('chantier', fn ($q) => $q->forEmployee($employee))
            ->whereIn('status', [ChantierReserveStatus::OPEN, ChantierReserveStatus::IN_PROGRESS])
            ->count();

        $hoursThisWeek = TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('hours');

        $complianceService = app(ChantierComplianceService::class);
        $activeChantierModels = Chantier::forEmployee($employee)
            ->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::AWAITING_RECEPTION])
            ->get();

        $nonCompliantCount = 0;
        foreach ($activeChantierModels as $chantier) {
            $result = $complianceService->checkTeamCompliance($chantier);
            if (! $result['is_compliant']) {
                $nonCompliantCount++;
            }
        }

        $lastActivity = ChantierReserve::whereHas('chantier', fn ($q) => $q->forEmployee($employee))
            ->latest('created_at')
            ->first();

        $stats = [
            Stat::make('Chantiers actifs', $activeChantiers)
                ->description('En cours, en réception ou suspendus')
                ->descriptionIcon(Phosphor::HardHat)
                ->color('primary'),

            Stat::make('Réserves ouvertes', $openReserves)
                ->description($openReserves > 0 ? 'Nécessitent une attention' : 'Aucune réserve en attente')
                ->descriptionIcon(Phosphor::WarningCircle)
                ->color($openReserves > 0 ? 'danger' : 'success'),

            Stat::make('Heures cette semaine', number_format($hoursThisWeek, 1, ',', ' '))
                ->description('Pointages de la semaine en cours')
                ->descriptionIcon(Phosphor::Timer)
                ->color('info'),

            Stat::make('Équipes non conformes', $nonCompliantCount)
                ->description($nonCompliantCount > 0 ? 'Habilitations à vérifier' : 'Toutes les équipes sont conformes')
                ->descriptionIcon(Phosphor::ShieldCheck)
                ->color($nonCompliantCount > 0 ? 'warning' : 'success'),
        ];

        if ($lastActivity) {
            $stats[] = Stat::make('Dernière activité', $lastActivity->reference)
                ->description($lastActivity->title.' — '.$lastActivity->created_at->diffForHumans())
                ->descriptionIcon(Phosphor::ClockCounterClockwise)
                ->color('gray');
        }

        return $stats;
    }
}
