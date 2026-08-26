<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankTransaction;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;

class ExpensesCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 5;

    public function getHeading(): string
    {
        return 'Dépenses par Catégorie (Ce mois)';
    }

    protected function getComposition(): Composition
    {
        // Getting all expenses for this month to group them
        $expenses = BankTransaction::expenses()
            ->thisMonth()
            ->with('category')
            ->get();

        $grouped = $expenses->groupBy('transaction_category_id');

        $slices = $grouped->map(function ($group) {
            $first = $group->first();
            $label = $first && $first->category ? $first->category->name : 'Non catégorisé';
            $total = $group->sum('amount');

            return CompositionSlice::make($label, abs($total))
                ->color($this->getRandomColor());
        })->values()->toArray();

        return Composition::make('Dépenses')
            ->type('doughnut')
            ->slices($slices);
    }

    private function getRandomColor(): string
    {
        $colors = ['primary', 'success', 'warning', 'info', 'danger'];

        return $colors[array_rand($colors)];
    }
}
