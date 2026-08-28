<?php

namespace App\Filament\Chantier\Widgets;

use App\Models\Chantiers\ChantierLog;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LatestActivityWidget extends TableWidget
{
    protected static ?string $heading = 'Derniers événements terrain';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ChantierLog::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('created_at')->label('Créé le')
                    ->label('Heure')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->date->format('d/m/Y')),
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Auteur'),
                IconColumn::make('incident_reported')
                    ->label('Critique')
                    ->boolean()
                    ->trueIcon(Phosphor::Warning)
                    ->trueColor('danger'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('chantier.name')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat),

                                TextEntry::make('date')->label('Date')
                                    ->label('Poster le')
                                    ->date('d/m/Y'),

                                TextEntry::make('user.name')
                                    ->label('Posté par'),

                                TextEntry::make('weather_condition')
                                    ->label('Condition météo'),

                                IconEntry::make('incident_reported')
                                    ->boolean()
                                    ->tooltip(fn (Model $record) => $record->incident_reported ? 'Critique' : 'Normal')
                                    ->label('Critique'),
                            ]),

                        TextEntry::make('content')
                            ->label('Notes')
                            ->html(),
                    ]),
            ]);
    }
}
