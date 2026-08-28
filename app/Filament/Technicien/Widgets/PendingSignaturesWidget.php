<?php

namespace App\Filament\Technicien\Widgets;

use App\Enums\Core\SignatureStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PendingSignaturesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Suivi des signatures';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $employee = auth()->user()->salarie;

        if (! $employee) {
            return [];
        }

        $scope = fn ($q) => $q->where('employee_id', $employee->id);

        $totalSigned = Intervention::whereHas('workers', $scope)
            ->whereHas('signatures', fn ($q) => $q->where('status', SignatureStatus::SIGNED))
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $totalPending = Intervention::whereHas('workers', $scope)
            ->whereIn('status', [InterventionStatus::EN_COURS, InterventionStatus::TERMINEE])
            ->whereDoesntHave('signatures', fn ($q) => $q->where('status', SignatureStatus::SIGNED))
            ->count();

        $totalDone = Intervention::whereHas('workers', $scope)
            ->where('status', InterventionStatus::TERMINEE)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $signatureRate = ($totalDone > 0) ? round(($totalSigned / $totalDone) * 100) : null;

        return [
            Stat::make('Signées ce mois', $totalSigned)
                ->description('Interventions avec signature obtenue')
                ->descriptionIcon(Phosphor::CheckCircle)
                ->color('success'),

            Stat::make('En attente', $totalPending)
                ->description($totalPending > 0 ? 'Signature(s) manquante(s)' : 'Toutes obtenues')
                ->descriptionIcon(Phosphor::Clock)
                ->color($totalPending > 0 ? 'warning' : 'success'),

            Stat::make('Taux de signature', $signatureRate !== null ? $signatureRate.'%' : '—')
                ->description($totalDone > 0 ? $totalDone.' intervention(s) terminée(s) ce mois' : 'Aucune intervention terminée ce mois')
                ->descriptionIcon(Phosphor::ChartPieSlice)
                ->color($signatureRate === null ? 'gray' : ($signatureRate >= 80 ? 'success' : ($signatureRate >= 50 ? 'warning' : 'danger'))),
        ];
    }
}
