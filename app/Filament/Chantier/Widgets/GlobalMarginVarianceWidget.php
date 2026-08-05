<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\TimeEntry;
use App\Models\Articles\StockMouvement;
use App\Services\Chantiers\ChantierAnalyticService;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\VarianceWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\VarianceItem;

class GlobalMarginVarianceWidget extends VarianceWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Marge Analytique Globale (Chantiers Actifs)';
    }

    protected function getItems(): array
    {
        $analyticService = app(ChantierAnalyticService::class);
        $activeChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)->get();
        
        $currentMargin = 0;
        foreach ($activeChantiers as $chantier) {
            $metrics = $analyticService->getPerformanceMetrics($chantier);
            $currentMargin += $metrics['financials']['margin_real'] ?? 0;
        }

        // Estimer les coûts du dernier mois pour ces chantiers
        $chantierIds = $activeChantiers->pluck('id');
        
        $recentLaborCost = TimeEntry::whereIn('chantier_id', $chantierIds)
            ->where('date', '>=', now()->subDays(30))
            ->where('status', \App\Enums\RH\TimeEntryStatus::APPROVED)
            ->with('employee.currentContract')
            ->get()
            ->sum(fn ($entry) => $entry->hours * ($entry->employee->currentContract?->hourly_rate ?? 0));
            
        $recentMaterialCost = StockMouvement::outgoing()
            ->bySource(\App\Enums\Articles\StockMouvementSource::SITE)
            ->whereIn('reference_id', $chantierIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->with('stock.item')
            ->get()
            ->sum(function ($mouvement) {
                $unitPrice = $mouvement->stock->item->purchase_price ?? 0;
                return abs($mouvement->quantity_delta) * $unitPrice;
            });
            
        $recentCosts = $recentLaborCost + $recentMaterialCost;
        $previousMargin = $currentMargin + $recentCosts;

        return [
            VarianceItem::make('Marge Consolidée', $currentMargin)
                ->previous($previousMargin)
                ->formatUsing(fn ($value) => number_format($value, 2, ',', ' ') . ' €')
                ->changeFormatUsing(fn ($change) => ($change > 0 ? '+' : '') . number_format($change, 2, ',', ' ') . ' €')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
