<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankTransaction;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class ReconciliationGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Taux de Rapprochement (Ce mois)';
    }

    protected function getGoal(): Goal
    {
        $totalTransactions = BankTransaction::thisMonth()->count();
        $reconciledTransactions = BankTransaction::thisMonth()
            ->whereHas('reconciliations')
            ->count();

        $target = $totalTransactions > 0 ? $totalTransactions : 1;

        return Goal::make('Transactions lettrées', $reconciledTransactions, $target)
            ->color('success');
    }
}
