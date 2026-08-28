<?php

namespace App\Filament\Commerce\Widgets;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use LaBoiteACode\FilamentDashboardWidgets\Data\FunnelStage;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\FunnelWidget;

class SalesPipelineFunnelWidget extends FunnelWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '3';

    public function getHeading(): string
    {
        return 'Tunnel de Conversion des Ventes';
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

        $quotesTotal = CustomerQuote::where('created_at', '>=', $dateLimit)->count();

        $quotesSigned = CustomerQuote::where('created_at', '>=', $dateLimit)
            ->where('status', QuoteStatus::SIGNED)
            ->count();

        $ordersValidated = CustomerOrder::where('created_at', '>=', $dateLimit)
            ->where('status', '!=', OrderStatus::DRAFT)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->count();

        $invoicesPaid = CustomerInvoice::where('created_at', '>=', $dateLimit)
            ->where('status', InvoiceStatus::PAID)
            ->count();

        return [
            FunnelStage::make('Devis Émis', (float) $quotesTotal)->color('gray'),
            FunnelStage::make('Devis Signés', (float) $quotesSigned)->color('info'),
            FunnelStage::make('Commandes Validées', (float) $ordersValidated)->color('primary'),
            FunnelStage::make('Factures Payées', (float) $invoicesPaid)->color('success'),
        ];
    }
}
