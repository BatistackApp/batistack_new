<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PayrollContributionProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')->label('Nom')
                    ->label('Nom du profil')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')->label('Description')
                    ->label('Description')
                    ->columnSpanFull(),
                TextInput::make('meal_allowance_amount')
                    ->label('Montant de la prime de panier')
                    ->numeric()
                    ->prefix('€')
                    ->default(11.20)
                    ->helperText('Montant forfaitaire journalier pour les repas (dépend de la convention).'),
            ]);
    }
}
