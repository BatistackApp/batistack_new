<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static string|BackedEnum|null $icon = Phosphor::Notebook;

    protected static ?string $title = 'Journal de Bord Récent';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('user.name')->label('Saisie par'),
                IconColumn::make('incident_reported')
                    ->label('Incident')
                    ->boolean()
                    ->trueIcon(Phosphor::WarningOctagon)
                    ->trueColor('danger'),
                TextColumn::make('weather_condition')->label('Météo')->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()->label('Note Journalière'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
