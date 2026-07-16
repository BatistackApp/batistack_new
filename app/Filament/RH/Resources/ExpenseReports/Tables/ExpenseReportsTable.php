<?php

namespace App\Filament\RH\Resources\ExpenseReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}")
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('month')
                    ->label('Mois')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Année')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getLabel())
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('Montant Total')
                    ->numeric()
                    ->suffix(' €')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
