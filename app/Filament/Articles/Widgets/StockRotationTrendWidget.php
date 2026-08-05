<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\StockMouvement;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\TrendWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Trend;
use LaBoiteACode\FilamentDashboardWidgets\Data\TrendPoint;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use App\Enums\Articles\StockMouvementType;

class StockRotationTrendWidget extends TrendWidget
{
    protected static ?int $sort = 4;
    
    // Pour garder les dates générées
    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 derniers jours',
            '30' => '30 derniers jours',
            '90' => '3 mois',
        ];
    }

    public function getHeading(): string
    {
        return 'Mouvements de Stocks (Sorties)';
    }

    protected function getTrend(): Trend
    {
        $days = (int) $this->filter;
        
        $cacheKey = "dashboard_stock_rotation_{$days}";
        
        $rawPoints = Cache::remember($cacheKey, 300, function () use ($days) {
            $period = CarbonPeriod::create(now()->subDays($days), now());
            $data = [];
            
            $mouvements = StockMouvement::where('type', StockMouvementType::OUT->value)
                ->where('created_at', '>=', now()->subDays($days)->startOfDay())
                ->get()
                ->groupBy(function($m) {
                    return $m->created_at->format('Y-m-d');
                });
                
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $total = isset($mouvements[$dateStr]) ? $mouvements[$dateStr]->sum('quantity') : 0;
                
                $data[] = [
                    'label' => $date->format('d/m'),
                    'value' => (float) $total
                ];
            }
            
            return $data;
        });

        $points = [];
        $total = 0;
        foreach ($rawPoints as $pt) {
            if ($pt instanceof TrendPoint) {
                $points[] = $pt;
                $total += $pt->getValue();
            } else {
                $points[] = TrendPoint::make($pt['label'], $pt['value']);
                $total += $pt['value'];
            }
        }

        return Trend::make('Sorties cumulées')
            ->value($total)
            ->type('area')
            ->color('warning')
            ->points($points);
    }
}
