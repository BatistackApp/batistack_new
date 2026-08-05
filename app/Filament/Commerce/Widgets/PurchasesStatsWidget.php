<?php

namespace App\Filament\Commerce\Widgets;

use App\Services\Commerce\CommerceAnalyticService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchasesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $analyticService = app(CommerceAnalyticService::class);
        $metrics = $analyticService->getPurchasesMetrics(now()->startOfMonth(), now());

        return [
            Stat::make('Achats facturés (HT)', '€ ' . number_format($metrics['invoiced_ht'], 2, ',', ' '))
                ->description('Total facturé par les fournisseurs ce mois')
                ->color('danger')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Achats payés (HT)', '€ ' . number_format($metrics['paid_ht'], 2, ',', ' '))
                ->description('Montant réglé aux fournisseurs')
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
