<?php

namespace App\Filament\Tiers\Widgets;

use App\Models\Tiers\ThirdParty;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;

class ClientAcquisitionVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Acquisition Clients';
    }

    protected function getItems(): array
    {
        $currentMonthCount = ThirdParty::clients()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastMonthCount = ThirdParty::clients()
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        return [
            VarianceItem::make('Nouveaux clients', (float) $currentMonthCount)
                ->previous((float) $lastMonthCount)
                ->formatUsing(fn (float $val) => (int) $val)
        ];
    }
}
