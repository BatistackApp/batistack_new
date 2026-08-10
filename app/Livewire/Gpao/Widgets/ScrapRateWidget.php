<?php

namespace App\Livewire\Gpao\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ScrapRateWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalScrap = \App\Models\Gpao\ManufacturingScrap::sum('quantity');
        $totalProduced = \App\Models\Gpao\ManufacturingOrder::sum('quantity_produced');
        
        $denominator = $totalProduced + $totalScrap;
        $rate = $denominator > 0 ? ($totalScrap / $denominator) * 100 : 0;

        return [
            Stat::make('Taux de Rebut Global', number_format($rate, 2) . '%')
                ->description('Sur base des quantités produites vs rebutées')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($rate > 5 ? 'danger' : 'success'),
            
            Stat::make('Total Rebuts', number_format($totalScrap, 0) . ' unités')
                ->description('Quantité totale déclarée en rebut'),
        ];
    }
}
