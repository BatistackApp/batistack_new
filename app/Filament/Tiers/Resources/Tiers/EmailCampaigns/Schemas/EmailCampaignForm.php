<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Schemas;

use Filament\Schemas\Schema;

class EmailCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nom interne de la campagne')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('Objet de l\'email')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\RichEditor::make('body')
                    ->label('Contenu de l\'email')
                    ->required()
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Date de planification')
                    ->minDate(now())
                    ->nullable(),
            ]);
    }
}
