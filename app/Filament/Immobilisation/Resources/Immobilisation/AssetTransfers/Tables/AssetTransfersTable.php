<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fixedAsset.name')
                    ->label('Actif')
                    ->searchable(),
                TextColumn::make('fromChantier.name')
                    ->label('Origine')
                    ->searchable(),
                TextColumn::make('toChantier.name')
                    ->label('Destination')
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->label('Demandé par')
                    ->sortable(),
                TextColumn::make('transfer_date')
                    ->label('Date prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'approved',
                        'info' => 'in_transit',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    \Filament\Actions\Action::make('print_transfer')
                        ->label('Bon de transport')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function (\App\Models\Immobilisation\AssetTransfer $record) {
                            $service = new \App\Services\Immobilisation\ImmobilisationDocumentService();
                            $path = $service->generateTransferDocument($record);
                            return response()->download($path);
                        }),
                    \Filament\Actions\Action::make('mark_completed')
                        ->label('Marquer comme réceptionné')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (\App\Models\Immobilisation\AssetTransfer $record) => $record->status !== 'completed' && $record->status !== 'cancelled')
                        ->action(function (\App\Models\Immobilisation\AssetTransfer $record) {
                            $record->update(['status' => 'completed']);
                            $record->fixedAsset->update(['chantier_id' => $record->to_chantier_id]);
                        }),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
