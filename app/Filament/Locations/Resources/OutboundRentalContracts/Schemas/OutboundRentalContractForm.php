<?php

namespace App\Filament\Locations\Resources\OutboundRentalContracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutboundRentalContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de Base')->schema([
                    Select::make('company_id')
                        ->relationship('company', 'legal_name')
                        ->label('Société')
                        ->required()
                        ->default(1),
                    Select::make('third_party_id')
                        ->relationship('thirdParty', 'name')
                        ->label('Client')
                        ->required(),
                    Select::make('chantier_id')
                        ->relationship('chantier', 'name')
                        ->label('Chantier de destination')
                        ->nullable(),
                    TextInput::make('reference')
                        ->required()
                        ->default(fn () => 'OUT-'.time()),
                    Select::make('status')
                        ->options([
                            'draft' => 'Brouillon',
                            'active' => 'Actif',
                            'completed' => 'Terminé',
                            'cancelled' => 'Annulé',
                        ])
                        ->required()
                        ->default('draft'),
                    Select::make('billing_period')
                        ->options([
                            'daily' => 'Journalier',
                            'weekly' => 'Hebdomadaire',
                            'monthly' => 'Mensuel',
                            'yearly' => 'Annuel',
                        ])
                        ->required()
                        ->default('monthly'),
                ])->columns(2),

                Section::make('Dates et Pénalités')->schema([
                    DatePicker::make('start_date')
                        ->required()
                        ->default(now()),
                    DatePicker::make('expected_end_date')
                        ->afterOrEqual('start_date'),
                    DatePicker::make('actual_end_date')
                        ->afterOrEqual('start_date'),
                    TextInput::make('daily_penalty_rate')
                        ->label('Pénalité de retard par jour')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ])->columns(2),

                Section::make('Équipements Loués')->schema([
                    Repeater::make('lines')
                        ->relationship()
                        ->schema([
                            Select::make('fixed_asset_id')
                                ->relationship('fixedAsset', 'name')
                                ->label('Équipement')
                                ->required(),
                            TextInput::make('daily_rate')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('€')
                                ->required(),
                        ])
                        ->columns(2),
                ]),
            ]);
    }
}
