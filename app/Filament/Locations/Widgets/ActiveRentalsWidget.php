<?php

namespace App\Filament\Locations\Widgets;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveRentalsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCount = RentalContract::where('status', RentalStatus::ACTIVE)->count();
        $draftCount = RentalContract::where('status', RentalStatus::DRAFT)->count();
        $endingSoon = RentalContract::where('status', RentalStatus::ACTIVE)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(3))
            ->count();

        return [
            Stat::make('Locations Actives', $activeCount)
                ->description('Contrats en cours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Brouillons', $draftCount)
                ->description('Contrats à valider')
                ->color('warning'),
                
            Stat::make('Fin de location imminente', $endingSoon)
                ->description('Dans les 3 prochains jours')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($endingSoon > 0 ? 'danger' : 'success'),
        ];
    }
}
