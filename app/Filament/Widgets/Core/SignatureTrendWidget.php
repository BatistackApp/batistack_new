<?php

namespace App\Filament\Widgets\Core;

use App\Models\Core\Signature;
use Carbon\Carbon;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\TrendWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Trend;
use LaBoiteACode\FilamentDashboardWidgets\Data\TrendPoint;

class SignatureTrendWidget extends TrendWidget
{
    protected static ?int $sort = 2;

    protected function getTrend(): Trend
    {
        $startDate = now()->subDays(30);

        // Fetch signatures per day
        $signatures = Signature::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        $points = [];
        $total = 0;

        for ($i = 0; $i <= 30; $i++) {
            $date = Carbon::parse($startDate)->addDays($i)->format('Y-m-d');
            $count = $signatures->get($date, 0);
            $total += $count;
            $points[] = TrendPoint::make(Carbon::parse($date)->format('d M'), $count);
        }

        return Trend::make('Activité des signatures (30 j)')
            ->value($total)
            ->type('line')
            ->points($points)
            ->color('primary');
    }
}
