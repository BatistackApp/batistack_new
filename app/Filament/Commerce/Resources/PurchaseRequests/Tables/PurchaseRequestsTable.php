<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')
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
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('convert_to_order')
                    ->label('Transformer en commande')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\Commerce\PurchaseRequest $record) {
                        $order = \App\Models\Commerce\PurchaseOrder::create([
                            'supplier_id' => $record->supplier_id,
                            'chantier_id' => $record->chantier_id,
                            'reference' => 'CMD-' . uniqid(),
                            'status' => \App\Enums\Commerce\QuoteStatus::DRAFT,
                        ]);
                        foreach ($record->items as $item) {
                            $order->items()->create([
                                'item_id' => $item->item_id,
                                'name' => $item->name,
                                'quantity' => $item->quantity,
                                'price_unit_ht' => $item->item->purchase_price ?? 0,
                                'vat_rate_id' => \App\Models\Core\VatRate::first()->id ?? null,
                            ]);
                        }
                        $record->update(['status' => \App\Enums\Commerce\QuoteStatus::ACCEPTED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Commande générée avec succès')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
