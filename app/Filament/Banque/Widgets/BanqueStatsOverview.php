<?php

namespace App\Filament\Banque\Widgets;

use App\Enums\Banque\TransactionStatus;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BanqueStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalBalance = BankAccount::sum('balance');
        $pendingCount = BankTransaction::where('status', TransactionStatus::PENDING)->count();
        $pendingAmount = BankTransaction::where('status', TransactionStatus::PENDING)->sum('amount');

        return [
            Stat::make('Trésorerie Globale', number_format($totalBalance, 2, ',', ' ').' €')
                ->description('Solde de tous les comptes synchronisés')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

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
