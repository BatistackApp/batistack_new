<?php

namespace App\Filament\Immobilisation\Widgets;

use App\Models\Immobilisation\FixedAsset;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class VgpGoalProgressWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Conformité VGP (Engins)';
    }

    protected function getGoal(): Goal
    {
        // Assets that require VGP
        $assets = FixedAsset::whereNotNull('vgp_frequency_months')->where('vgp_frequency_months', '>', 0)->get();
        
        $total = $assets->count();
        $passed = 0;

        foreach ($assets as $asset) {
            if ($asset->vgp_status === 'ok') {
                $passed++;
            }
        }

        $percentage = $total > 0 ? ($passed / $total) * 100 : 0;
        $color = $percentage >= 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');

        return Goal::make('Taux de conformité', $percentage, 100)
            ->formatUsing(fn (float $val) => number_format($val, 1) . '%')
            ->color($color);
    }
}
