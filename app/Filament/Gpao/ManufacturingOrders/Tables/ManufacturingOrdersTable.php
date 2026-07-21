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

                \Filament\Tables\Columns\TextColumn::make('customerOrder.reference')
                    ->label('Cmd. Origine')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->customer_order_id ? route('filament.commerce.resources.customer-orders.edit', $record->customer_order_id) : null)
                    ->color('primary')
                    ->openUrlInNewTab(),

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
                    ->label('Terminer (Au contrôle)')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->status === \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS)
                    ->action(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->update(['status' => \App\Enums\Gpao\ManufacturingStatus::QUALITY_CONTROL])),

                \Filament\Actions\Action::make('quality_control')
                    ->label('Contrôle Qualité')
                    ->icon('heroicon-o-shield-check')
                    ->color('fuchsia')
                    ->visible(fn (\App\Models\Gpao\ManufacturingOrder $record) => $record->status === \App\Enums\Gpao\ManufacturingStatus::QUALITY_CONTROL)
                    ->form([
                        \Filament\Forms\Components\Radio::make('status')
                            ->label('Résultat')
                            ->options([
                                'passed' => 'Validé',
                                'failed' => 'Refusé',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Notes / Commentaires'),
                    ])
                    ->action(function (array $data, \App\Models\Gpao\ManufacturingOrder $record) {
                        $record->qualityChecks()->create([
                            'inspector_id' => auth()->id(),
                            'status' => $data['status'],
                            'notes' => $data['notes'],
                            'checked_at' => now(),
                        ]);

                        if ($data['status'] === 'passed') {
                            $record->update(['status' => \App\Enums\Gpao\ManufacturingStatus::COMPLETED]);
                            \Filament\Notifications\Notification::make()->title('Contrôle validé. Produit en stock.')->success()->send();
                        } else {
                            $record->update(['status' => \App\Enums\Gpao\ManufacturingStatus::IN_PROGRESS]);
                            \Filament\Notifications\Notification::make()->title('Contrôle refusé. OF renvoyé en cours.')->warning()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
