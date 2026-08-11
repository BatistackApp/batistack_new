<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Tables;

use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\CancelAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\ConfirmedAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\PrinterAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('created_at')->label('Créé le')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(OrderStatus::class),

                SelectFilter::make('client_id')->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Model $record) => $record->status === OrderStatus::DRAFT),
                ActionGroup::make([
                    ConfirmedAction::make(),
                    CancelAction::make(),
                    PrinterAction::make(),
                    \Filament\Actions\Action::make('generateInvoice')
                        ->label('Générer Facture')
                        ->icon('heroicon-o-document-currency-euro')
                        ->color('success')
                        ->visible(fn (Model $record) => in_array($record->status, [OrderStatus::CONFIRMED, OrderStatus::PARTIALLY_DELIVERED, OrderStatus::DELIVERED]))
                        ->form([
                            \Filament\Forms\Components\Select::make('type')->label('Type')
                                ->label('Type de facture')
                                ->options([
                                    \App\Enums\Commerce\InvoiceType::SIMPLE->value => 'Facture Globale (Solde)',
                                    \App\Enums\Commerce\InvoiceType::ACOMPTE->value => "Facture d'Acompte",
                                ])
                                ->required()
                                ->reactive(),
                            \Filament\Forms\Components\TextInput::make('acompte_amount')
                                ->label('Montant de l\'acompte (HT)')
                                ->numeric()
                                ->required(fn (\Filament\Forms\Get $get) => $get('type') === \App\Enums\Commerce\InvoiceType::ACOMPTE->value)
                                ->visible(fn (\Filament\Forms\Get $get) => $get('type') === \App\Enums\Commerce\InvoiceType::ACOMPTE->value),
                        ])
                        ->action(function (array $data, \App\Models\Commerce\CustomerOrder $record) {
                            try {
                                $type = \App\Enums\Commerce\InvoiceType::from($data['type']);
                                $acompte = $data['acompte_amount'] ?? null;
                                
                                $invoice = app(\App\Services\Commerce\CustomerOrderService::class)->createInvoice($record, $type, auth()->user(), null, $acompte);
                                \Filament\Notifications\Notification::make()->success()->title('Facture générée')->send();
                                
                                return redirect(\App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource::getUrl('view', ['record' => $invoice]));
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                            }
                        }),
                    \Filament\Actions\Action::make('generateDeliveryNote')
                        ->label('Générer BL')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->visible(fn (Model $record) => in_array($record->status, [OrderStatus::CONFIRMED, OrderStatus::PARTIALLY_DELIVERED]))
                        ->requiresConfirmation()
                        ->action(function (\App\Models\Commerce\CustomerOrder $record) {
                            try {
                                $itemsData = $record->items->map(function ($item) {
                                    return [
                                        'item_id' => $item->item_id,
                                        'quantity_delivered' => $item->quantity,
                                    ];
                                })->toArray();
                                
                                $bl = app(\App\Services\Commerce\CustomerOrderService::class)->createDeliveryNote($record, $itemsData, auth()->user());
                                \Filament\Notifications\Notification::make()->success()->title('BL généré')->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                            }
                        }),
                ]),
            ]);
    }
}
