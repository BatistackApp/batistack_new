<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Date de commande')
                    ->date('d/m/Y')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Livraison prévue')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('convert_to_receipt')
                    ->label('Réceptionner')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\Commerce\PurchaseOrder $record) {
                        $receipt = \App\Models\Commerce\ReceiptNote::create([
                            'purchase_order_id' => $record->id,
                            'reference' => 'BR-' . uniqid(),
                            'status' => \App\Enums\Commerce\DeliveryStatus::DRAFT,
                            'received_at' => now(),
                        ]);
                        foreach ($record->items as $item) {
                            $receipt->items()->create([
                                'purchase_order_item_id' => $item->id,
                                'quantity_received' => $item->quantity,
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Bon de réception généré')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
