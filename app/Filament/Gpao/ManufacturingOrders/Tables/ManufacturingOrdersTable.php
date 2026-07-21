<?php

namespace App\Filament\Gpao\ManufacturingOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class ManufacturingOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('quantity_planned')
                    ->label('Qte. Prévue')
                    ->numeric()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('quantity_produced')
                    ->label('Qte. Produite')
                    ->numeric()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        \App\Enums\Gpao\ManufacturingStatus::DRAFT->value => 'gray',
                        \App\Enums\Gpao\ManufacturingStatus::PLANNED->value => 'info',
                        \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS->value => 'warning',
                        \App\Enums\Gpao\ManufacturingStatus::COMPLETED->value => 'success',
                        \App\Enums\Gpao\ManufacturingStatus::CANCELLED->value => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('planned_start_date')
                    ->label('Début')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Gpao\ManufacturingStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\Action::make('start')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Gpao\ManufacturingOrder $record) => in_array($record->status, [\App\Enums\Gpao\ManufacturingStatus::DRAFT, \App\Enums\Gpao\ManufacturingStatus::PLANNED]))
                    ->action(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->update(['status' => \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS])),

                \Filament\Actions\Action::make('complete')
                    ->label('Terminer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->status === \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS)
                    ->action(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->update(['status' => \App\Enums\Gpao\ManufacturingStatus::COMPLETED])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
