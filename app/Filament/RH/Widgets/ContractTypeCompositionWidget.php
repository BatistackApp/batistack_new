<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Contract;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;

class ContractTypeCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Répartition des Effectifs';
    }

    protected function getComposition(): Composition
    {
        $contracts = Contract::active()->get();

        $groups = $contracts->groupBy(fn ($contract) => $contract->type ? $contract->type->getLabel() : 'Autre');

        $slices = [];
        $colors = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];
        $i = 0;

        foreach ($groups as $label => $group) {
            $slices[] = CompositionSlice::make($label, $group->count())
                ->color($colors[$i % count($colors)]);
            $i++;
        }

        return Composition::make('Contrats')
            ->slices($slices)
            ->type('doughnut');
    }
}
