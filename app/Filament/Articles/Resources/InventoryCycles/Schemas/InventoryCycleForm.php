<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Schemas;

use App\Enums\Articles\InventoryCycleStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class InventoryCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')->label('Nom')
                            ->label('Nom du cycle')
                            ->disabled()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('Dépôt')
                            ->relationship('warehouse', 'name')
                            ->disabled()
                            ->required(),
                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options(InventoryCycleStatus::class)
                            ->disabled()
                            ->required(),
                    ]),
                Section::make('Saisie du comptage')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship()
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Placeholder::make('item_name')
                                    ->label('Article')
                                    ->content(fn ($record) => $record?->item?->name.' ('.$record?->item?->reference.')'),
                                Placeholder::make('theoretical_quantity')
                                    ->label('Quantité théorique')
                                    ->content(fn ($record) => $record?->theoretical_quantity),
                                TextInput::make('counted_quantity')
                                    ->label('Quantité comptée')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(fn (string $operation, $get, ?Model $record) => $record?->cycle?->status !== InventoryCycleStatus::COMPLETED)
                                    ->disabled(fn ($get) => in_array($get('../../status'), [InventoryCycleStatus::PENDING_REVIEW->value, InventoryCycleStatus::COMPLETED->value, InventoryCycleStatus::CANCELLED->value])),
                            ]),
                    ]),
            ]);
    }
}
