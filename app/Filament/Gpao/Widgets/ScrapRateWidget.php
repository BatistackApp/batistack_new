<?php

namespace App\Filament\Gpao\Widgets;

use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingScrap;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ScrapRateWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalScrap = ManufacturingScrap::sum('quantity');
        $totalProduced = ManufacturingOrder::sum('quantity_produced');

        $denominator = $totalProduced + $totalScrap;
        $rate = $denominator > 0 ? ($totalScrap / $denominator) * 100 : 0;

        return [
            Stat::make('Taux de Rebut Global', number_format($rate, 2).'%')
                ->description('Sur base des quantités produites vs rebutées')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($rate > 5 ? 'danger' : 'success'),

            Stat::make('Total Rebuts', number_format($totalScrap, 0).' unités')
                ->description('Quantité totale déclarée en rebut'),
        ];
    }
}
