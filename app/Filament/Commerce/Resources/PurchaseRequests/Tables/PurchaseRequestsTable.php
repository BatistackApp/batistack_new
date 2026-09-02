<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests\Tables;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Core\VatRate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequestsTable
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('convert_to_order')
                    ->label('Transformer en commande')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (PurchaseRequest $record) {
                        $order = PurchaseOrder::create([
                            'supplier_id' => $record->supplier_id,
                            'chantier_id' => $record->chantier_id,
                            'reference' => 'CMD-'.uniqid(),
                            'status' => QuoteStatus::DRAFT,
                        ]);
                        foreach ($record->items as $item) {
                            $order->items()->create([
                                'item_id' => $item->item_id,
                                'name' => $item->name,
                                'quantity' => $item->quantity,
                                'price_unit_ht' => $item->item->purchase_price ?? 0,
                                'vat_rate_id' => VatRate::first()->id ?? null,
                            ]);
                        }
                        $record->update(['status' => QuoteStatus::ACCEPTED]);
                        Notification::make()
                            ->title('Commande générée avec succès')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->check(fn ($records) => $records->every(fn ($r) => $r->status === \App\Enums\Commerce\QuoteStatus::DRAFT)),
                ]),
            ]);
    }
}
