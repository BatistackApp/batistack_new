<?php

namespace App\Filament\Immobilisation\Widgets;

use App\Models\Immobilisation\AssetCategory;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;

class AssetCategoryCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;

    protected function getComposition(): Composition
    {
        $categories = AssetCategory::with('fixedAssets')->get();
        $slices = [];

        $colors = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];
        $colorIndex = 0;

        foreach ($categories as $category) {
            $totalGrossValue = $category->fixedAssets->sum('purchase_price');
            if ($totalGrossValue > 0) {
                $color = $colors[$colorIndex % count($colors)];
                $slices[] = CompositionSlice::make($category->name, (float) $totalGrossValue)
                    ->color($color);
                $colorIndex++;
            }
        }

        return Composition::make('Répartition du Patrimoine (Valeur Brute)')
            ->slices($slices)
            ->type('doughnut');
    }
}
