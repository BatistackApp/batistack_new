<?php

namespace App\Filament\Locations\Resources\Locations\OutboundRentalContracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutboundRentalContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informations de Base')->schema([
                    \Filament\Forms\Components\Select::make('company_id')
                        ->relationship('company', 'legal_name')
                        ->label('Société')
                        ->required()
                        ->default(1),
                    \Filament\Forms\Components\Select::make('third_party_id')
                        ->relationship('thirdParty', 'name')
                        ->label('Client')
                        ->required(),
                    \Filament\Forms\Components\Select::make('chantier_id')
                        ->relationship('chantier', 'name')
                        ->label('Chantier de destination')
                        ->nullable(),
                    \Filament\Forms\Components\TextInput::make('reference')
                        ->required()
                        ->default(fn () => 'OUT-' . time()),
                    \Filament\Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Brouillon',
                            'active' => 'Actif',
                            'completed' => 'Terminé',
                            'cancelled' => 'Annulé',
                        ])
                        ->required()
                        ->default('draft'),
                    \Filament\Forms\Components\Select::make('billing_period')
                        ->options([
                            'daily' => 'Journalier',
                            'weekly' => 'Hebdomadaire',
                            'monthly' => 'Mensuel',
                            'yearly' => 'Annuel',
                        ])
                        ->required()
                        ->default('monthly'),
                ])->columns(2),

                \Filament\Schemas\Components\Section::make('Dates et Pénalités')->schema([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\DatePicker::make('expected_end_date')
                        ->afterOrEqual('start_date'),
                    \Filament\Forms\Components\DatePicker::make('actual_end_date')
                        ->afterOrEqual('start_date'),
                    \Filament\Forms\Components\TextInput::make('daily_penalty_rate')
                        ->label('Pénalité de retard par jour')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ])->columns(2),
                
                \Filament\Schemas\Components\Section::make('Équipements Loués')->schema([
                    \Filament\Forms\Components\Repeater::make('lines')
                        ->relationship()
                        ->schema([
                            \Filament\Forms\Components\Select::make('fixed_asset_id')
                                ->relationship('fixedAsset', 'name')
                                ->label('Équipement')
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('daily_rate')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('€')
                                ->required(),
                        ])
                        ->columns(2)
                ]),
            ]);
    }
}
