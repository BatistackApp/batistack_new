<?php

namespace App\Filament\RH\Widgets;

use App\Services\RH\SafetyRateService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SafetyRatesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $rates = app(SafetyRateService::class)->rollingYear();

        return [
            Stat::make('Taux de Fréquence (TF)', number_format($rates['tf'], 2, ',', ' '))
                ->description('Accidents avec arrêt × 1 000 000 ÷ heures travaillées')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color($rates['tf'] > 0 ? 'danger' : 'success'),

            Stat::make('Taux de Gravité (TG)', number_format($rates['tg'], 2, ',', ' '))
                ->description('Jours d\'arrêt × 1 000 ÷ heures travaillées')
                ->descriptionIcon('heroicon-o-clock')
                ->color($rates['tg'] > 0 ? 'warning' : 'success'),

            Stat::make('Accidents du travail', $rates['accidentCount'])
                ->description($rates['accidentCount'] > 0 ? 'Sur la période de 12 mois glissants' : 'Aucun accident déclaré')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Heures travaillées (12 mois)', number_format($rates['hoursWorked'], 0, ',', ' '))
                ->description($rates['from']->format('d/m/Y').' → '.$rates['to']->format('d/m/Y'))
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('info'),
        ];
    }
}
