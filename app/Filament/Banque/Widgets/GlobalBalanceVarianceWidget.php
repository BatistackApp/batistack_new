<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class GlobalBalanceVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Trésorerie Globale';
    }

    protected function getItems(): array
    {
        $currentBalance = BankAccount::sum('balance');

        $thisMonthNet = BankTransaction::thisMonth()
            ->get()
            ->sum(function ($transaction) {
                return $transaction->type->value === 'credit' ? $transaction->amount : -$transaction->amount;
            });

        $previousBalance = $currentBalance - $thisMonthNet;

        return [
            VarianceItem::make('Solde', $currentBalance)
                ->previous($previousBalance)
                ->formatUsing(fn ($value) => number_format((float) $value, 2, ',', ' ').' €')
                ->changeFormatUsing(fn ($change) => ($change > 0 ? '+' : '').number_format((float) $change, 2, ',', ' ').' €')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
