<?php

namespace App\Filament\Commerce\Widgets;

use App\Services\Commerce\CommerceAnalyticService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuoteConversionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $analyticService = app(CommerceAnalyticService::class);
        $conversion = $analyticService->getQuoteConversionRate(now()->startOfYear(), now());
        $payment = $analyticService->getAveragePaymentDelay();

        return [
            Stat::make('Taux de transformation', $conversion['conversion_rate'])
                ->description($conversion['signed_quotes'] . ' devis acceptés sur ' . $conversion['total_quotes'])
                ->color('success')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Délai moyen de paiement', $payment['average_delay_days'] . ' jours')
                ->description($payment['interpretation'])
                ->color($payment['average_delay_days'] > 30 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),
        ];
    }
}
