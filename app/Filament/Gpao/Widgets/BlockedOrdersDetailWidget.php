<?php

namespace App\Filament\Gpao\Widgets;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

class BlockedOrdersDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'OFs en Rupture de Stock (Bloqués)';
    }

    protected function getDetails(): array
    {
        $activeOrders = ManufacturingOrder::with(['requirements.item.stocks', 'item'])
            ->whereIn('status', [ManufacturingStatus::PLANNED, ManufacturingStatus::IN_PROGRESS])
            ->get();

        $details = [];

        foreach ($activeOrders as $order) {
            $missingNames = [];
            foreach ($order->requirements as $req) {
                $missing = $req->quantity_required - $req->quantity_consumed;
                if ($missing > 0) {
                    $totalStock = $req->item->stocks->sum('quantity');
                    if ($totalStock < $missing) {
                        $missingNames[] = $req->item->name;
                    }
                }
            }

            if (! empty($missingNames)) {
                $details[] = Detail::make($order->reference, 'Composants manquants : '.implode(', ', $missingNames))
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger');
            }
        }

        return $details;
    }
}
