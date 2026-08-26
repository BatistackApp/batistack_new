<?php

namespace App\Filament\Paie\Resources\Paie\AdvancePayments\Tables;

use App\Enums\Paie\AdvancePaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdvancePaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount')->label('Montant')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('request_date')
                    ->label('Date demande')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label('Date paiement')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->searchable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (AdvancePaymentStatus $state): string => match ($state) {
                        AdvancePaymentStatus::PENDING => 'warning',
                        AdvancePaymentStatus::APPROVED => 'info',
                        AdvancePaymentStatus::PAID => 'success',
                        AdvancePaymentStatus::DEDUCTED => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
