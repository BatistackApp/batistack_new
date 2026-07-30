<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
                Group::make([
                    ViewField::make('speech_script')
                        ->view('filament.chantier.scripts.speech-recognition')
                        ->hiddenLabel(),
                    \Filament\Forms\Components\Textarea::make('content')
                        ->label('Événements du jour')
                        ->required()
                        ->rows(5)
                        ->extraAttributes(['id' => 'speech-textarea'])
                        ->hintAction(
                            Action::make('dictate')
                                ->icon('heroicon-m-microphone')
                                ->label('Dicter le rapport')
                                ->color(fn ($livewire) => 'primary')
                                ->extraAttributes([
                                    'x-on:click' => 'toggleRecording()',
                                    'x-bind:class' => "isRecording ? 'text-danger-600 animate-pulse' : ''",
                                ])
                        ),
                ])->columnSpanFull()->extraAttributes(['x-data' => 'speechRecognition']),
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
