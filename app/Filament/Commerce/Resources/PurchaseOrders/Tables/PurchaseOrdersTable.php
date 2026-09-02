<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Tables;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\ReceiptNote;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->label('Date de commande')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('expected_delivery_date')
                    ->label('Livraison prévue')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('convert_to_receipt')
                    ->label('Réceptionner')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $receipt = ReceiptNote::create([
                            'purchase_order_id' => $record->id,
                            'reference' => 'BR-'.uniqid(),
                            'status' => DeliveryStatus::DRAFT,
                            'received_at' => now(),
                        ]);
                        foreach ($record->items as $item) {
                            $receipt->items()->create([
                                'purchase_order_item_id' => $item->id,
                                'quantity_received' => $item->quantity,
                            ]);
                        }
                        Notification::make()
                            ->title('Bon de réception généré')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->check(fn ($records) => $records->every(fn ($r) => $r->status === \App\Enums\Commerce\OrderStatus::DRAFT)),
                ]),
            ]);
    }
}
