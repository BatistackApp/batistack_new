<?php

namespace App\Filament\Gpao\ManufacturingOrders\Schemas;

use App\Enums\Gpao\ManufacturingStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManufacturingOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->schema([
                    Section::make('Détails de l\'Ordre')
                        ->schema([
                            TextInput::make('reference')
                                ->label('Référence')
                                ->default('OF-'.strtoupper(uniqid()))
                                ->required()
                                ->readOnly(),

                            Select::make('item_id')
                                ->label('Article à produire')
                                ->relationship('item', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('status')
                                ->label('Statut')
                                ->options(ManufacturingStatus::class)
                                ->default(ManufacturingStatus::DRAFT)
                                ->required(),
                        ])->columns(3),

                    Section::make('Planification & Quantités')
                        ->schema([
                            TextInput::make('quantity_planned')
                                ->label('Quantité prévue')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(1),

                            TextInput::make('quantity_produced')
                                ->label('Quantité produite')
                                ->numeric()
                                ->default(0),

                            DatePicker::make('planned_start_date')
                                ->label('Date de début prévue'),

                            DatePicker::make('planned_end_date')
                                ->label('Date de fin prévue'),
                        ])->columns(2),
                ])->columnSpan('full'),
            ]);
    }
}
