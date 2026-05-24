<?php

namespace App\Filament\Commerce\Widgets;

use App\Services\Commerce\CommerceAnalyticService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $analyticService = app(CommerceAnalyticService::class);
        $metrics = $analyticService->getRevenueMetrics(now()->startOfMonth(), now());

        return [
            Stat::make('CA du mois (HT)', '€ '.number_format($metrics['revenue']['invoiced_ht'], 2, ',', ' '))
                ->description('Chiffre d\'affaires facturé')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('CA Encaissé (HT)', '€ '.number_format($metrics['revenue']['paid_ht'], 2, ',', ' '))
                ->description('Montant réellement reçu')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),

            Stat::make('En attente de paiement', '€ '.number_format($metrics['revenue']['pending_ht'], 2, ',', ' '))
                ->description('Factures validées non payées')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Pipeline (Devis)', '€ '.number_format($metrics['pipeline_ht'], 2, ',', ' '))
                ->description('Devis en cours de validation')
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }
}
