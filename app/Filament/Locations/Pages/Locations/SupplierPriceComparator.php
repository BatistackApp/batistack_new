<?php

namespace App\Filament\Locations\Pages\Locations;

use Filament\Pages\Page;

class SupplierPriceComparator extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Comparateur de Prix';
    protected static ?string $title = 'Comparateur de Tarifs Fournisseurs';
    protected static ?string $slug = 'comparateur-prix';

    protected string $view = 'filament.locations.pages.locations.supplier-price-comparator';

    public ?string $equipment_category = null;
    public ?int $duration_days = null;
    public array $results = [];

    public function mount()
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Select::make('equipment_category')
                ->label('Catégorie d\'équipement')
                ->options(function () {
                    return \App\Models\Locations\SupplierPriceGrid::query()
                        ->distinct()
                        ->pluck('equipment_category', 'equipment_category')
                        ->toArray();
                })
                ->required(),
            \Filament\Forms\Components\TextInput::make('duration_days')
                ->label('Durée (en jours)')
                ->numeric()
                ->required(),
        ];
    }

    public function search()
    {
        $data = $this->form->getState();
        $category = $data['equipment_category'] ?? null;
        $days = $data['duration_days'] ?? 0;

        if (!$category || !$days) {
            $this->results = [];
            return;
        }

        $grids = \App\Models\Locations\SupplierPriceGrid::with('supplier')
            ->where('equipment_category', $category)
            ->get();

        $this->results = $grids->map(function ($grid) use ($days) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            $weeks = floor($remainingDays / 7);
            $daysLeft = $remainingDays % 7;

            $cost = 0;
            if ($grid->monthly_rate && $months > 0) {
                $cost += $months * $grid->monthly_rate;
            } elseif ($grid->weekly_rate && $months > 0) {
                $weeks += $months * 4; // fallback approx
            } elseif ($grid->daily_rate && $months > 0) {
                $daysLeft += $months * 30;
            }

            if ($grid->weekly_rate && $weeks > 0) {
                $cost += $weeks * $grid->weekly_rate;
            } elseif ($grid->daily_rate && $weeks > 0) {
                $daysLeft += $weeks * 7;
            }

            if ($grid->daily_rate && $daysLeft > 0) {
                $cost += $daysLeft * $grid->daily_rate;
            }
            
            // Simplified logic: If no specific rate, fallback to daily if exists
            if ($cost == 0 && $grid->daily_rate) {
                $cost = $days * $grid->daily_rate;
            }

            return [
                'supplier_name' => $grid->supplier->name ?? 'Inconnu',
                'daily_rate' => $grid->daily_rate,
                'weekly_rate' => $grid->weekly_rate,
                'monthly_rate' => $grid->monthly_rate,
                'total_cost' => $cost,
            ];
        })->sortBy('total_cost')->values()->toArray();
    }
}
