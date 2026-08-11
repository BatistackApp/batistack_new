<?php

namespace App\Filament\RH\Resources\CibtpDeclarations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CibtpDeclarationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de la déclaration')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier')
                            ->relationship('chantier', 'name')
                            ->searchable()
                            ->required(),

                        Select::make('weather_alert_id')
                            ->label('Alerte Météo Associée')
                            ->relationship('weatherAlert', 'type')
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('date')->label('Date')
                            ->label('Date d\'intempérie')
                            ->required(),

                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'submitted' => 'Soumise à la CIBTP',
                                'validated' => 'Validée / Indemnisée',
                            ])
                            ->default('draft')
                            ->required(),

                        TextInput::make('total_lost_hours')
                            ->label('Total des heures perdues (Estimation)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
