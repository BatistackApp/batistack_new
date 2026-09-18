<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\InvoiceStatus;
use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LaserStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $quotesCount = LaserQuote::where('created_at', '>=', $monthStart)->count();

        $ordersCount = LaserOrder::where('created_at', '>=', $monthStart)
            ->whereNotIn('status', [OrderStatus::DRAFT, OrderStatus::CANCELLED])
            ->count();

        $turnoverHt = LaserInvoice::where('created_at', '>=', $monthStart)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->sum('total_ht');

        $pendingInvoicesHt = LaserInvoice::where('status', InvoiceStatus::VALIDATED)
            ->where('due_date', '<', now())
            ->sum('total_ht');

        return [
            Stat::make('Devis créés', $quotesCount)
                ->description('Ce mois-ci')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->icon('heroicon-o-document-text'),

            Stat::make('Commandes', $ordersCount)
                ->description('Hors brouillons/annulées')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('primary')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('CA facturé (HT)', '€ '.number_format($turnoverHt, 2, ',', ' '))
                ->description('Factures validées + payées ce mois')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Factures en retard', '€ '.number_format($pendingInvoicesHt, 2, ',', ' '))
                ->description('Échues non payées')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($pendingInvoicesHt > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
