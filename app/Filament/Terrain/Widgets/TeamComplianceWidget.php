<?php

namespace App\Filament\Terrain\Widgets;

use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierComplianceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TeamComplianceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?string $heading = 'Conformité Équipes';

    protected function getStats(): array
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return [];
        }

        $complianceService = app(ChantierComplianceService::class);

        $activeChantiers = Chantier::forEmployee($employee)
            ->whereIn('status', [\App\Enums\Chantiers\ChantierStatus::IN_PROGRESS, \App\Enums\Chantiers\ChantierStatus::AWAITING_RECEPTION])
            ->get();

        $totalMembers = 0;
        $compliantMembers = 0;
        $nonCompliantMembers = 0;
        $issues = [];

        foreach ($activeChantiers as $chantier) {
            $result = $complianceService->checkTeamCompliance($chantier);
            $membersCount = $chantier->members()->count();
            $totalMembers += $membersCount;

            if ($result['is_compliant']) {
                $compliantMembers += $membersCount;
            } else {
                $nonCompliantMembers += $membersCount;
                $issues = array_merge($issues, $result['messages']);
            }
        }

        $complianceRate = $totalMembers > 0
            ? round(($compliantMembers / $totalMembers) * 100, 1)
            : 100;

        $stats = [
            Stat::make('Taux de conformité', $complianceRate.'%')
                ->description($totalMembers.' membre(s) sur '.($activeChantiers->count()).' chantier(s)')
                ->descriptionIcon(Phosphor::ShieldCheck)
                ->color($complianceRate === 100 ? 'success' : ($complianceRate >= 80 ? 'warning' : 'danger')),

            Stat::make('Équipes conformes', $compliantMembers)
                ->description('Membres à jour de leurs habilitations')
                ->descriptionIcon(Phosphor::CheckCircle)
                ->color('success'),

            Stat::make('Non conformes', $nonCompliantMembers)
                ->description($nonCompliantMembers > 0 ? 'Nécessitent une mise à jour' : 'Aucun problème détecté')
                ->descriptionIcon(Phosphor::Warning)
                ->color($nonCompliantMembers > 0 ? 'danger' : 'gray'),
        ];

        if (! empty($issues)) {
            $stats[] = Stat::make('Anomalies détectées', count($issues))
                ->description(collect($issues)->take(2)->implode(' | '))
                ->descriptionIcon(Phosphor::ListChecks)
                ->color('danger');
        }

        return $stats;
    }
}
