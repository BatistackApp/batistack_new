<?php

namespace App\Filament\Technicien\Resources\Interventions\Schemas;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Articles\Item;
use App\Models\RH\Employee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InterventionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Intervention')
                    ->tabs([
                        Tabs\Tab::make('Général')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(['default' => 1, 'sm' => 2])
                                    ->schema([
                                        Select::make('third_party_id')
                                            ->relationship('thirdParty', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled()
                                            ->label('Client'),

                                        Select::make('chantier_id')->label('Chantier')
                                            ->relationship('chantier', 'reference')
                                            ->searchable()
                                            ->preload()
                                            ->disabled()
                                            ->label('Chantier associé')
                                            ->nullable(),

                                        Select::make('type')->label('Type')
                                            ->options(InterventionType::class)
                                            ->required()
                                            ->live()
                                            ->disabled()
                                            ->label('Type d\'intervention'),

                                        Select::make('status')->label('Statut')
                                            ->options(InterventionStatus::class)
                                            ->required()
                                            ->default(InterventionStatus::BROUILLON)
                                            ->label('Statut'),

                                        DateTimePicker::make('scheduled_at')
                                            ->label('Date planifiée')
                                            ->disabled()
                                            ->nullable(),

                                        DateTimePicker::make('completed_at')
                                            ->label('Date de fin (clôture)')
                                            ->disabled(fn (Get $get) => $get('status') !== InterventionStatus::TERMINEE->value)
                                            ->nullable(),

                                        TextInput::make('flat_rate_price')
                                            ->label('Prix forfaitaire')
                                            ->numeric()
                                            ->prefix('€')
                                            ->disabled()
                                            ->visible(fn (Get $get) => $get('type') === InterventionType::FORFAIT->value)
                                            ->nullable(),
                                    ]),

                                RichEditor::make('description')->label('Description')
                                    ->label('Description de l\'intervention / Panne')
                                    ->columnSpanFull()
                                    ->disabled()
                                    ->nullable(),
                            ]),

                        Tabs\Tab::make('Équipe')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Repeater::make('workers')
                                    ->relationship('workers')
                                    ->label('Techniciens affectés')
                                    ->disabled()
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                                            Select::make('employee_id')
                                                ->label('Employé')
                                                ->options(Employee::pluck('first_name', 'id'))
                                                ->searchable()
                                                ->required(),

                                            TextInput::make('hours_worked')
                                                ->label('Heures passées')
                                                ->numeric()
                                                ->step('0.5')
                                                ->required()
                                                ->default(1),
                                        ])
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Ajouter un technicien')
                            ]),

                        Tabs\Tab::make('Matériel')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Repeater::make('materials')
                                    ->relationship('materials')
                                    ->label('Pièces de rechange et matériel consommé')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 3])->schema([
                                            Select::make('item_id')
                                                ->label('Article')
                                                ->options(Item::pluck('name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn ($state, callable $set) => $set('selling_price', Item::find($state)?->selling_price)),

                                            Select::make('warehouse_id')
                                                ->relationship('warehouse', 'name')
                                                ->label('Entrepôt / Camion source')
                                                ->searchable()
                                                ->preload()
                                                ->required(),

                                            TextInput::make('quantity')->label('Quantité')
                                                ->label('Quantité')
                                                ->numeric()
                                                ->required()
                                                ->default(1),

                                            TextInput::make('selling_price')
                                                ->label('Prix unitaire')
                                                ->numeric()
                                                ->prefix('€')
                                                ->disabled()
                                                ->required(),
                                        ])
                                    ])
                                    ->defaultItems(0)
                                    ->addActionLabel('Ajouter une pièce')
                            ]),
                    ])->columnSpanFull()
            ]);
    }
}
