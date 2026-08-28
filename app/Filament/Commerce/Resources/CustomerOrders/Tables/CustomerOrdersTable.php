<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Tables;

use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\CancelAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\ConfirmedAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\PrinterAction;
use App\Models\Commerce\CustomerOrder;
use App\Services\Commerce\CustomerOrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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
                    Action::make('generateInvoice')
                        ->label('Générer Facture')
                        ->icon('heroicon-o-document-currency-euro')
                        ->color('success')
                        ->visible(fn (Model $record) => in_array($record->status, [OrderStatus::CONFIRMED, OrderStatus::PARTIALLY_DELIVERED, OrderStatus::DELIVERED]))
                        ->form([
                            Select::make('type')->label('Type')
                                ->label('Type de facture')
                                ->options([
                                    InvoiceType::SIMPLE->value => 'Facture Globale (Solde)',
                                    InvoiceType::ACOMPTE->value => "Facture d'Acompte",
                                ])
                                ->required()
                                ->reactive(),
                            TextInput::make('acompte_amount')
                                ->label('Montant de l\'acompte (HT)')
                                ->numeric()
                                ->required(fn (Get $get) => $get('type') === InvoiceType::ACOMPTE->value)
                                ->visible(fn (Get $get) => $get('type') === InvoiceType::ACOMPTE->value),
                        ])
                        ->action(function (array $data, CustomerOrder $record) {
                            try {
                                $type = InvoiceType::from($data['type']);
                                $acompte = $data['acompte_amount'] ?? null;

                                $invoice = app(CustomerOrderService::class)->createInvoice($record, $type, auth()->user(), null, $acompte);
                                Notification::make()->success()->title('Facture générée')->send();

                                return redirect(CustomerInvoiceResource::getUrl('view', ['record' => $invoice]));
                            } catch (\Exception $e) {
                                Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                            }
                        }),
                    Action::make('generateDeliveryNote')
                        ->label('Générer BL')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->visible(fn (Model $record) => in_array($record->status, [OrderStatus::CONFIRMED, OrderStatus::PARTIALLY_DELIVERED]))
                        ->requiresConfirmation()
                        ->action(function (CustomerOrder $record) {
                            try {
                                $itemsData = $record->items->map(function ($item) {
                                    return [
                                        'item_id' => $item->item_id,
                                        'quantity_delivered' => $item->quantity,
                                    ];
                                })->toArray();

                                $bl = app(CustomerOrderService::class)->createDeliveryNote($record, $itemsData, auth()->user());
                                Notification::make()->success()->title('BL généré')->send();
                            } catch (\Exception $e) {
                                Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                            }
                        }),
                ]),
            ]);
    }
}
