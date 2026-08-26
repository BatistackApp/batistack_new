<?php

namespace App\Filament\Commerce\Widgets;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class MonthlyRevenueVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Chiffre d\'Affaires Mensuel';
    }

    protected function getItems(): array
    {
        $currentMonthStart = now()->startOfMonth();
        $previousMonthStart = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        $currentRevenue = CustomerInvoice::whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAID])
            ->where('created_at', '>=', $currentMonthStart)
            ->sum('total_ht');

        $previousRevenue = CustomerInvoice::whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAID])
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total_ht');

        return [
            VarianceItem::make('CA Facturé (HT)', (float) $currentRevenue)
                ->previous((float) $previousRevenue)
                ->formatUsing(fn ($value) => number_format($value, 2, ',', ' ').' €')
                ->changeFormatUsing(fn ($change) => ($change > 0 ? '+' : '').number_format($change, 2, ',', ' ').' €')
                ->icon('heroicon-o-currency-euro'),
        ];
    }
}
