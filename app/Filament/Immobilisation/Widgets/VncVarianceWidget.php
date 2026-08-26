<?php

namespace App\Filament\Immobilisation\Widgets;

use App\Models\Immobilisation\FixedAsset;
use Illuminate\Support\Carbon;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class VncVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Valeur Nette Comptable (VNC) Globale';
    }

    protected function getItems(): array
    {
        $assets = FixedAsset::with(['depreciations' => function ($query) {
            $query->where('is_passed', true);
        }, 'impairments'])->get();

        $currentVnc = 0;
        $prevVnc = 0;
        $startOfMonth = now()->startOfMonth();

        foreach ($assets as $asset) {
            // Current VNC
            $currentVnc += max(0, $asset->purchase_price
                - $asset->depreciations->sum('amount')
                - $asset->impairments->sum('amount'));

            // Previous Month VNC
            if (Carbon::parse($asset->purchase_date)->isBefore($startOfMonth)) {
                $prevDepreciations = $asset->depreciations->filter(function ($d) use ($startOfMonth) {
                    return Carbon::parse($d->period_date)->isBefore($startOfMonth);
                })->sum('amount');

                $prevImpairments = $asset->impairments->filter(function ($i) use ($startOfMonth) {
                    return Carbon::parse($i->date)->isBefore($startOfMonth);
                })->sum('amount');

                $prevVnc += max(0, $asset->purchase_price - $prevDepreciations - $prevImpairments);
            }
        }

        return [
            VarianceItem::make('VNC Actuelle', $currentVnc)
                ->previous($prevVnc)
                ->formatUsing(fn (float $val) => number_format($val, 2, ',', ' ').' €')
                ->changeFormatUsing(fn (float $val) => ($val > 0 ? '+' : '').number_format($val, 2, ',', ' ').' €'),
        ];
    }
}
