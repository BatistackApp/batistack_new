<?php

namespace App\Filament\Laser\Resources\LaserQuotes\RelationManagers;

use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuoteLine;
use App\Services\Laser\LaserQuoteService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Lignes du devis';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->status->value !== QuoteStatus::DRAFT->value;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('material_id')
                    ->label('Matériau')
                    ->options(fn () => LaserMaterial::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $material = LaserMaterial::find($get('material_id'));
                        if ($material) {
                            $set('price_per_kg', $material->price_per_kg);
                            $set('price_per_meter', $material->price_per_meter);
                            $set('_material_density', $material->density_kg_m3);
                            static::recalculateLine($get, $set);
                        }
                    }),

                TextInput::make('description')
                    ->label('Description'),

                TextInput::make('_material_density')
                    ->label('Densité')
                    ->hidden()
                    ->dehydrated(false),

                TextInput::make('length_mm')
                    ->label('Longueur (mm)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('width_mm')
                    ->label('Largeur (mm)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('thickness_mm')
                    ->label('Épaisseur (mm)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('cut_length_mm')
                    ->label('Périmètre de découpe (mm)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('programming_cost')
                    ->label('Coût fixe programmation (€)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateLine($get, $set)),

                TextInput::make('discount_pct')
                    ->label('Remise (%)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100),

                TextInput::make('weight_kg')
                    ->label('Poids (kg)')
                    ->numeric()
                    ->readOnly(),

                TextInput::make('unit_price_ht')
                    ->label('Prix unitaire HT (€)')
                    ->numeric()
                    ->readOnly(),

                TextInput::make('total_ht')
                    ->label('Total HT (€)')
                    ->numeric()
                    ->readOnly(),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('material.name')
                    ->label('Matériau')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(30),

                TextColumn::make('length_mm')
                    ->label('Longueur')
                    ->suffix(' mm'),

                TextColumn::make('width_mm')
                    ->label('Largeur')
                    ->suffix(' mm'),

                TextColumn::make('thickness_mm')
                    ->label('Épaisseur')
                    ->suffix(' mm'),

                TextColumn::make('quantity')
                    ->label('Qté'),

                TextColumn::make('weight_kg')
                    ->label('Poids')
                    ->suffix(' kg'),

                TextColumn::make('unit_price_ht')
                    ->label('Prix unit.')
                    ->money('EUR'),

                TextColumn::make('discount_pct')
                    ->label('Remise')
                    ->suffix('%'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->summarized(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une ligne'),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    protected static function recalculateLine(Get $get, Set $set): void
    {
        $service = app(LaserQuoteService::class);

        $length = (float) ($get('length_mm') ?? 0);
        $width = (float) ($get('width_mm') ?? 0);
        $thickness = (float) ($get('thickness_mm') ?? 0);
        $quantity = (int) ($get('quantity') ?? 1);
        $cutLength = (float) ($get('cut_length_mm') ?? 0);
        $programmingCost = (float) ($get('programming_cost') ?? 0);
        $pricePerKg = (float) ($get('price_per_kg') ?? 0);
        $pricePerMeter = (float) ($get('price_per_meter') ?? 0);
        $density = (float) ($get('_material_density') ?? 0);

        $surface = LaserQuoteLine::computeSurface($length, $width);
        $weight = LaserQuoteLine::computeWeight($length, $width, $thickness, $density);

        $discount = $quantity >= 5
            ? $service->applyDiscount($quantity)
            : (float) ($get('discount_pct') ?? 0);

        $totalHt = $service->calculateLineTotal(
            $weight,
            $pricePerKg,
            $cutLength,
            $pricePerMeter,
            $programmingCost,
            $quantity,
            $discount,
        );

        $unitPrice = LaserQuoteLine::computeUnitPrice(
            $weight,
            $pricePerKg,
            $cutLength,
            $pricePerMeter,
            $programmingCost,
        );

        $set('surface_mm2', number_format($surface, 4, '.', ''));
        $set('weight_kg', number_format($weight, 4, '.', ''));
        $set('unit_price_ht', number_format($unitPrice, 4, '.', ''));
        $set('discount_pct', number_format($discount, 2, '.', ''));
        $set('total_ht', number_format($totalHt, 2, '.', ''));
    }
}
