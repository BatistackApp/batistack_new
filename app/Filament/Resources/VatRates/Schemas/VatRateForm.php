<?php

namespace App\Filament\Resources\VatRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VatRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du taux de TVA')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nom')
                            ->label('Libellé du taux')
                            ->placeholder('ex: TVA Normale')
                            ->required(),

                        // Utilisation du Calculator (si package installé) ou TextInput standard avec pas
                        TextInput::make('rate')
                            ->label('Valeur du taux')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->required()
                            ->helperText('Précision supportée jusqu\'à 4 décimales.'),
                    ]),

                Section::make('Paramètres d\'application')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_default')
                            ->label('Taux par défaut')
                            ->onIcon(Phosphor::CheckCircle)
                            ->offIcon(Phosphor::XCircle)
                            ->live(),
                        Toggle::make('is_active')
                            ->label('Taux actif')
                            ->default(true),
                    ]),
            ]);
    }
}
