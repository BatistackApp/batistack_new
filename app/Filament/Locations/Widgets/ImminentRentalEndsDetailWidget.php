<?php

namespace App\Filament\Locations\Widgets;

use App\Models\Locations\RentalContract;
use App\Enums\Locations\RentalStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\Locations\Resources\RentalContractResource;

class ImminentRentalEndsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Fins de Contrats Imminentes (7 jours)';
    }

    protected function getDetails(): array
    {
        $contracts = RentalContract::with(['supplier', 'chantier'])
            ->where('status', RentalStatus::ACTIVE)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('end_date', 'asc')
            ->get();

        $details = [];

        foreach ($contracts as $contract) {
            $daysLeft = now()->diffInDays($contract->end_date, false);
            
            $statusText = $daysLeft == 0 ? "Prend fin aujourd'hui" : "Prend fin dans " . (int)$daysLeft . " jour(s)";
            
            $details[] = Detail::make($contract->reference . ' - ' . ($contract->supplier->name ?? 'Inconnu'), $statusText)
                ->icon('heroicon-o-clock')
                ->color($daysLeft <= 2 ? 'danger' : 'warning')
                ->url(RentalContractResource::getUrl('edit', ['record' => $contract]));
        }

        return $details;
    }
}
