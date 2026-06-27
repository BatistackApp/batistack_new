<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WeatherAlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'weatherAlerts';
    
    protected static ?string $title = 'Alertes Météo';

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('type')->label('Type')->disabled(),
                \Filament\Forms\Components\TextInput::make('severity')->label('Sévérité')->disabled(),
                \Filament\Forms\Components\DateTimePicker::make('started_at')->label('Début')->disabled(),
                \Filament\Forms\Components\DateTimePicker::make('ended_at')->label('Fin')->disabled(),
                \Filament\Forms\Components\Textarea::make('description')->label('Description')->disabled()->columnSpanFull(),
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
                        'orange' => 'danger',
                        'rouge' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->label('Début'),
                Tables\Columns\TextColumn::make('ended_at')->dateTime()->label('Fin'),
                Tables\Columns\TextColumn::make('description')->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ]);
    }
}
