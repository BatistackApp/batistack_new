<?php

namespace App\Filament\Tiers\Widgets;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TiersStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Calcul des alertes de vigilance réelles
        $alertsCount = ThirdParty::where('is_active', true)
            ->get()
            ->filter(fn ($tp) => ! $tp->compliant_status['compliant'])
            ->count();

        return [
            Stat::make('Portefeuille Clients', ThirdParty::where('type', ThirdPartyType::CLIENT)->where('is_active', true)->count())
                ->description('Clients actifs en base')
                ->descriptionIcon(Phosphor::Users)
                ->chart([7, 3, 4, 5, 6, 3, 5, 8])
                ->color('success'),

            Stat::make('Partenaires Techniques', ThirdParty::whereIn('type', [ThirdPartyType::SUPPLIER, ThirdPartyType::SUBCONTRACTOR])->count())
                ->description('Fournisseurs & Sous-traitants')
                ->descriptionIcon(Phosphor::HardHat)
                ->color('info'),

            Stat::make('Risques Conformité', $alertsCount)
                ->description('Tiers non-conformes (Vigilance)')
                ->descriptionIcon(Phosphor::Warning)
                ->chart([2, 5, 3, 8, 4, 9, 5, 2])
                ->color($alertsCount > 0 ? 'danger' : 'success'),
        ];
    }
}
