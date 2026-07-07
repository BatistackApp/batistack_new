<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppVersionWidget extends BaseWidget
{
    protected static ?int $sort = 100; // Mettre le widget à la fin du tableau de bord

    protected function getStats(): array
    {
        $version = config('app.version', 'v0.0.0 (Développement)');

        return [
            Stat::make('Version de l\'Application', $version)
                ->description('Dernière version déployée en production')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),
        ];
    }
}
