<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Tables;

use App\Enums\RH\ExpenseAdvanceStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpenseAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable()
                    ->label('Montant'),

                TextColumn::make('request_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),

                TextColumn::make('reason')
                    ->limit(50)
                    ->label('Motif'),

                TextColumn::make('status')
                    ->badge()
                    ->label('Statut'),

                TextColumn::make('expenseReport.id')
                    ->label('Note liée')
                    ->url(fn ($record) => $record->expense_report_id ? route('filament.salarie.resources.expense-reports.view', $record->expense_report_id) : null)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ExpenseAdvanceStatus::class)
                    ->label('Statut'),
            ])
            ->recordActions([
                EditAction::make()->visible(fn ($record) => $record->status === ExpenseAdvanceStatus::PENDING),
                DeleteAction::make()->visible(fn ($record) => $record->status === ExpenseAdvanceStatus::PENDING),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
