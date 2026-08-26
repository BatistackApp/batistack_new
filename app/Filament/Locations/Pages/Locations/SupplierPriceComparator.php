<?php

namespace App\Filament\Locations\Pages\Locations;

use App\Models\Locations\SupplierPriceGrid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class SupplierPriceComparator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Comparateur de Prix';

    protected static ?string $title = 'Comparateur de Tarifs Fournisseurs';

    protected static ?string $slug = 'comparateur-prix';

    protected string $view = 'filament.locations.pages.locations.supplier-price-comparator';

    public ?array $data = [];

    public array $results = [];

    public function mount()
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Select::make('equipment_category')
                ->label('Catégorie d\'équipement')
                ->options(function () {
                    return SupplierPriceGrid::query()
                        ->distinct()
                        ->pluck('equipment_category', 'equipment_category')
                        ->toArray();
                })
                ->required(),
            TextInput::make('duration_days')
                ->label('Durée (en jours)')
                ->numeric()
                ->minValue(1)
                ->required(),
        ])->statePath('data');
    }

    public function search()
    {
        $data = $this->form->getState();
        $category = $data['equipment_category'] ?? null;
        $days = $data['duration_days'] ?? 0;

        if (! $category || ! $days) {
            $this->results = [];

            return;
        }

        $grids = SupplierPriceGrid::with('supplier')
            ->where('equipment_category', $category)
            ->get();

        $this->results = $grids->map(function ($grid) use ($days) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            $weeks = floor($remainingDays / 7);
            $daysLeft = $remainingDays % 7;

            $cost = 0;

            if ($months > 0) {
                if ($grid->monthly_rate) {
                    $cost += $months * $grid->monthly_rate;
                } else {
                    return null;
                }
            }

            if ($weeks > 0) {
                if ($grid->weekly_rate) {
                    $cost += $weeks * $grid->weekly_rate;
                } else {
                    return null;
                }
            }

            if ($daysLeft > 0) {
                if ($grid->daily_rate) {
                    $cost += $daysLeft * $grid->daily_rate;
                } else {
                    return null;
                }
            }

            return [
                'supplier_name' => $grid->supplier->name ?? 'Inconnu',
                'daily_rate' => $grid->daily_rate,
                'weekly_rate' => $grid->weekly_rate,
                'monthly_rate' => $grid->monthly_rate,
                'total_cost' => $cost,
            ];
        })->filter()->sortBy('total_cost')->values()->toArray();
    }
}
