<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\InvoiceStatus;
use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use LaBoiteACode\FilamentDashboardWidgets\Data\FunnelStage;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\FunnelWidget;

class LaserPipelineFunnelWidget extends FunnelWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '3';

    public function getHeading(): string
    {
        return 'Tunnel Commercial Laser';
    }

    protected function getFilters(): ?array
    {
        return [
            '3' => '3 derniers mois',
            '6' => '6 derniers mois',
            '12' => '12 derniers mois',
        ];
    }

    protected function getStages(): array
    {
        $months = (int) ($this->filter ?? 3);
        $dateLimit = now()->subMonths($months);

        $quotesTotal = LaserQuote::where('created_at', '>=', $dateLimit)->count();

        $quotesAccepted = LaserQuote::where('created_at', '>=', $dateLimit)
            ->where('status', QuoteStatus::ACCEPTED)
            ->count();

        $ordersConfirmed = LaserOrder::where('created_at', '>=', $dateLimit)
            ->whereNotIn('status', [OrderStatus::DRAFT, OrderStatus::CANCELLED])
            ->count();

        $invoicesPaid = LaserInvoice::where('created_at', '>=', $dateLimit)
            ->where('status', InvoiceStatus::PAID)
            ->count();

        return [
            FunnelStage::make('Devis émis', (float) $quotesTotal)->color('gray'),
            FunnelStage::make('Devis acceptés', (float) $quotesAccepted)->color('info'),
            FunnelStage::make('Commandes', (float) $ordersConfirmed)->color('primary'),
            FunnelStage::make('Factures payées', (float) $invoicesPaid)->color('success'),
        ];
    }
}
