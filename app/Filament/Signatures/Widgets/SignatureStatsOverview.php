<?php

namespace App\Filament\Signatures\Widgets;

use App\Enums\Core\SignatureStatus;
use App\Models\Core\Signature;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $total = Signature::count();
        $pending = Signature::where('status', SignatureStatus::PENDING)->count();
        $signed = Signature::where('status', SignatureStatus::SIGNED)->count();
        $refused = Signature::where('status', SignatureStatus::REFUSED)->count();
        $today = Signature::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Signatures', $total)
                ->description('Toutes les demandes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('En attente', $pending)
                ->description('Signature(s) en cours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Signées', $signed)
                ->description('Complétées avec succès')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Refusées', $refused)
                ->description('Refus par un signataire')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make("Aujourd'hui", $today)
                ->description('Nouvelles demandes')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
