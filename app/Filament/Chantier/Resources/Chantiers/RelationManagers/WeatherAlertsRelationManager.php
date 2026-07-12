<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WeatherAlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'weatherAlerts';

    protected static ?string $title = 'Alertes Météo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')->label('Type')->disabled(),
                TextInput::make('severity')->label('Sévérité')->disabled(),
                DateTimePicker::make('started_at')->label('Début')->disabled(),
                DateTimePicker::make('ended_at')->label('Fin')->disabled(),
                Textarea::make('description')->label('Description')->disabled()->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Type d\'alerte'),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'jaune' => 'warning',
                        'orange', 'rouge' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->label('Début'),
                Tables\Columns\TextColumn::make('ended_at')->dateTime()->label('Fin'),
                Tables\Columns\TextColumn::make('description')->wrap(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
