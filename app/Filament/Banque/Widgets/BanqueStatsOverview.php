<?php

namespace App\Filament\Banque\Widgets;

use App\Enums\Banque\TransactionStatus;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BanqueStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $pendingCount = BankTransaction::where('status', TransactionStatus::PENDING)->count();
        $pendingAmount = BankTransaction::where('status', TransactionStatus::PENDING)->sum('amount');

        return [
            Stat::make('Transactions à lettrer', $pendingCount)
                ->description('Action requise')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($pendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Montant en attente', number_format(abs($pendingAmount), 2, ',', ' ').' €')
                ->description('Impact potentiel sur la comptabilité')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
