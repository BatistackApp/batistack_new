<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmailCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom interne de la campagne')
                    ->required()
                    ->maxLength(255),
                TextInput::make('subject')
                    ->label('Objet de l\'email')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('body')
                    ->label('Contenu de l\'email')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at')
                    ->label('Date de planification')
                    ->minDate(now())
                    ->nullable(),
            ]);
    }
}
