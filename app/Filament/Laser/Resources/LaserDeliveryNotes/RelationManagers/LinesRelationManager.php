<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Models\Laser\LaserDeliveryNoteLine;
use App\Services\Laser\LaserDeliveryNoteService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Lignes du bon de livraison';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->status !== \App\Enums\Laser\DeliveryStatus::DRAFT;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('quantity_delivered')
                ->label('Quantité livrée')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->maxValue(fn ($record) => $record?->quantity)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->select([
                    'id',
                    'laser_delivery_note_id',
                    'laser_order_line_id',
                    'material_id',
                    'description',
                    'length_mm',
                    'width_mm',
                    'thickness_mm',
                    'quantity',
                    'quantity_delivered',
                    'weight_kg',
                ])
                ->with('material:id,name'))
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
                    ->label('Qté cmd'),

                TextColumn::make('quantity_delivered')
                    ->label('Qté livrée')
                    ->color(fn ($record) => $record->quantity_delivered < $record->quantity ? 'warning' : null),

                TextColumn::make('weight_kg')
                    ->label('Poids unit.')
                    ->suffix(' kg'),

                TextColumn::make('total_weight_kg')
                    ->label('Poids total')
                    ->state(fn ($record) => round($record->quantity_delivered * $record->weight_kg, 4))
                    ->suffix(' kg'),
            ])
            ->recordActions([
                Action::make('editQuantity')
                    ->label('Modifier la quantité')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        TextInput::make('quantity_delivered')
                            ->label('Quantité livrée')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->visible(fn () => ! $this->isReadOnly())
                    ->action(function (LaserDeliveryNoteLine $record, array $data): void {
                        app(LaserDeliveryNoteService::class)->updateDeliveryQuantity(
                            $record,
                            (int) $data['quantity_delivered'],
                        );
                    }),
            ]);
    }
}
