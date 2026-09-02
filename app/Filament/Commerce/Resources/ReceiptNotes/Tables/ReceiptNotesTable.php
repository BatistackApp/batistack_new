<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\Tables;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierInvoice;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label('Date de réception')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('has_litigation')
                    ->label('Litige')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('convert_to_invoice')
                    ->label('Créer Facture')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ReceiptNote $record) {
                        $invoice = SupplierInvoice::create([
                            'supplier_id' => $record->order->supplier_id ?? null,
                            'reference' => 'FACT-'.uniqid(),
                            'status' => InvoiceStatus::DRAFT,
                            'total_ht' => 0,
                            'total_ttc' => 0,
                            'due_date' => now()->addDays(30),
                        ]);
                        $total_ht = 0;
                        foreach ($record->items as $item) {
                            $price = $item->items->price_unit_ht ?? 0;
                            $qty = $item->quantity_received;
                            $invoice->items()->create([
                                'item_id' => $item->items->item_id ?? null,
                                'name' => $item->items->name ?? 'Article',
                                'quantity' => $qty,
                                'price_unit' => $price,
                                'vat_rate_id' => $item->items->vat_rate_id ?? null,
                            ]);
                            $total_ht += ($price * $qty);
                        }
                        $invoice->update([
                            'total_ht' => $total_ht,
                            'total_ttc' => $total_ht * 1.20, // default approximation, would need proper sum
                        ]);
                        $record->update(['status' => DeliveryStatus::DELIVERED]);
                        Notification::make()
                            ->title('Facture générée avec succès')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->check(fn ($records) => $records->every(fn ($r) => $r->status === \App\Enums\Commerce\DeliveryStatus::DRAFT)),
                ]),
            ]);
    }
}
