<?php

namespace App\Filament\Paie\Widgets;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Models\Paie\AdvancePayment;
use App\Models\Paie\Payslip;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PaieStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $payslips = Payslip::whereMonth('payment_date', $currentMonth)->whereYear('payment_date', $currentYear)->get();

        $masseSalariale = $payslips->sum('gross_salary') + $payslips->sum('employer_cost');
        $netAPayer = $payslips->sum('net_payable');
        $bulletinsCount = $payslips->count();
        $acomptes = AdvancePayment::where('status', AdvancePaymentStatus::PAID)
            ->whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount');

        return [
            Stat::make('Masse Salariale', number_format($masseSalariale, 2, ',', ' ').' €')
                ->description('Coût total de la paie ce mois-ci')
                ->descriptionIcon(Phosphor::Bank)
                ->color('primary'),

            Stat::make('Net à Payer Total', number_format($netAPayer, 2, ',', ' ').' €')
                ->description('Montant total versé aux salariés')
                ->descriptionIcon(Phosphor::Money)
                ->color('success'),

            Stat::make('Bulletins Générés', $bulletinsCount)
                ->description('Pour la période en cours')
                ->descriptionIcon(Phosphor::Files)
                ->color('info'),

            Stat::make('Acomptes Versés', number_format($acomptes, 2, ',', ' ').' €')
                ->description('Avances sur salaire ce mois-ci')
                ->descriptionIcon(Phosphor::ArrowSquareOut)
                ->color('warning'),
        ];
    }
}
