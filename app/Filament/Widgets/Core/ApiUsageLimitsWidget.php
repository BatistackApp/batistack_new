<?php

namespace App\Filament\Widgets\Core;

use LaBoiteACode\FilamentDashboardWidgets\Data\UsageLimit;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\UsageLimitsWidget;

class ApiUsageLimitsWidget extends UsageLimitsWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Limites API';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Consommation simulée des APIs externes';
    }

    protected function getLimits(): array
    {
        return [
            UsageLimit::make('API SIREN', 850, 1000)
                ->color('primary')
                ->icon('heroicon-o-building-office-2'),

            UsageLimit::make('Google Maps', 3250, 5000)
                ->color('success')
                ->icon('heroicon-o-map'),

            UsageLimit::make('Météo (OpenWeather)', 480, 500)
                ->color('danger')
                ->icon('heroicon-o-cloud'),
        ];
    }
}
