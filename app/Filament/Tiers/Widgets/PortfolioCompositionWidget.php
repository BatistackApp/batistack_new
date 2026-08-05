<?php

namespace App\Filament\Tiers\Widgets;

use App\Models\Tiers\ThirdParty;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;

class PortfolioCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Répartition du Portefeuille';
    }

    protected function getComposition(): Composition
    {
        $thirdParties = ThirdParty::active()->get();
        
        $groups = $thirdParties->groupBy(fn ($tp) => $tp->type ? $tp->type->getLabel() : 'Autre');

        $slices = [];
        $colors = ['success', 'warning', 'info', 'primary', 'gray', 'danger'];
        $i = 0;

        foreach ($groups as $label => $group) {
            // Utiliser la couleur de l'énumération si possible, sinon une couleur par défaut
            $typeInstance = $group->first()->type ?? null;
            $color = $typeInstance ? $typeInstance->getColor() : $colors[$i % count($colors)];

            $slices[] = CompositionSlice::make($label, $group->count())
                ->color($color);
            $i++;
        }

        return Composition::make('Tiers')
            ->slices($slices)
            ->type('doughnut');
    }
}
