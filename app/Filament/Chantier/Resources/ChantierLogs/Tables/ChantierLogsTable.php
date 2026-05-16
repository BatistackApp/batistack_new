<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('d/m/Y')->sortable(),
                TextColumn::make('chantier.name')->label('Chantier')->searchable(),
                TextColumn::make('user.name')->label('Auteur'),
                IconColumn::make('incident_reported')
                    ->label('Incident')
                    ->boolean()
                    ->trueIcon(Phosphor::WarningOctagon)
                    ->trueColor('danger'),
                TextColumn::make('created_at')->label('Saisi le')->dateTime('H:i')->since(),
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
}
