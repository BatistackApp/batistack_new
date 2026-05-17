<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ChantierLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('chantier_id')
                        ->relationship('chantier', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('date')
                        ->default(now())
                        ->required()
                        ->native(false),
                ]),
                TextInput::make('weather_condition')
                    ->label('Météo')
                    ->placeholder('ex: Ensoleillé, 22°C'),
                RichEditor::make('content')
                    ->label('Événements du jour')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('incident_reported')
                    ->label('Signaler un incident critique')
                    ->onColor('danger')
                    ->helperText('Déclenchera une alerte immédiate au conducteur de travaux.'),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->label('Photos du jour')
                    ->collection('photos')
                    ->multiple()
                    ->image(),
            ]);
    }
}
