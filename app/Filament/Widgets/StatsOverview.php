<?php

namespace App\Filament\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\RH\TimeEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $chantiersCount = Chantier::where('status', ChantierStatus::IN_PROGRESS)->count();
        $revenue = CustomerInvoice::paid()->whereMonth('created_at', now()->month)->sum('total_ttc');
        $overdue = CustomerInvoice::overdue()->sum('total_ttc');
        $hours = TimeEntry::where('date', '>=', now()->startOfWeek())->sum('hours');

        return [
            Stat::make('Chantiers en cours', $chantiersCount)
                ->description('Actuellement actifs')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Chiffre d\'Affaires (Mois)', number_format((float) $revenue, 2, ',', ' ').' €')
                ->description('Factures encaissées ce mois-ci')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Factures en Retard', number_format((float) $overdue, 2, ',', ' ').' €')
                ->description('Impayés à surveiller')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make('Heures Pointées (Semaine)', number_format((float) $hours, 2, ',', ' ').' h')
                ->description('Total équipe sur 7 jours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
