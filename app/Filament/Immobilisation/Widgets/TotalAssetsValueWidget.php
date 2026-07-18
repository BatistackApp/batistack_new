<?php

namespace App\Filament\Immobilisation\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalAssetsValueWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalBrut = \App\Models\Immobilisation\FixedAsset::sum('purchase_price');
        
        $totalVnc = 0;
        $activeAssets = \App\Models\Immobilisation\FixedAsset::where('status', \App\Enums\Immobilisation\AssetStatus::ACTIVE)->get();
        foreach ($activeAssets as $asset) {
            $lastDepreciation = $asset->depreciations()->where('is_passed', true)->orderByDesc('period_date')->first();
            $totalVnc += $lastDepreciation ? $lastDepreciation->remaining_vnc : ($asset->purchase_price - $asset->salvage_value);
        }

        $disposedCount = \App\Models\Immobilisation\FixedAsset::where('status', \App\Enums\Immobilisation\AssetStatus::DISPOSED)->count();

        return [
            Stat::make('Valeur brute totale', number_format($totalBrut, 2, ',', ' ') . ' €')
                ->description('Coût d\'acquisition total des actifs')
                ->icon('heroicon-o-currency-euro')
                ->color('primary'),
            Stat::make('VNC totale estimée', number_format($totalVnc, 2, ',', ' ') . ' €')
                ->description('Valeur Nette Comptable restante')
                ->icon('heroicon-o-chart-bar')
                ->color('success'),
            Stat::make('Actifs sortis/cédés', $disposedCount)
                ->description('Historique des sorties d\'actifs')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('gray'),
        ];
    }
}
