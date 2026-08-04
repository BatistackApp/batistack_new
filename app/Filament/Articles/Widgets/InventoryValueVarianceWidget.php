<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;
use Illuminate\Support\Facades\Cache;

class InventoryValueVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Valeur de l\'Inventaire (PUMP)';
    }

    protected function getItems(): array
    {
        $currentValue = Cache::remember('inventory_current_value', 600, function () {
            // Somme de (quantité stock * prix d'achat de l'article)
            return Stock::with('item')->get()->sum(function ($stock) {
                return $stock->quantity * ($stock->item?->purchase_price ?? 0);
            });
        });

        $previousValue = Cache::remember('inventory_previous_value', 600, function () use ($currentValue) {
            // On calcule l'impact des mouvements du dernier mois pour estimer la valeur précédente
            $lastMonthMovements = StockMouvement::with('stock.item')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();
                
            $impactValue = $lastMonthMovements->sum(function ($mouvement) {
                $price = $mouvement->stock?->item?->purchase_price ?? 0;
                // quantity_delta est positif pour IN et négatif pour OUT
                return $mouvement->quantity_delta * $price;
            });

            // Valeur il y a 30 jours = Valeur actuelle - valeur ajoutée nette
            return max(0, $currentValue - $impactValue);
        });

        return [
            VarianceItem::make('Valeur actuelle', $currentValue)
                ->previous($previousValue)
                ->formatUsing(fn ($value) => number_format($value, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn ($change) => ($change > 0 ? '+' : '') . number_format($change, 2, ',', ' ') . ' €')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
