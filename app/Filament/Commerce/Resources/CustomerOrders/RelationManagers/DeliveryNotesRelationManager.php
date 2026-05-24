<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerDeliveryNoteItem;
use App\Models\Commerce\CustomerOrderItem;
use App\Services\Commerce\CommerceDocumentationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DeliveryNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryNotes';

    protected static ?string $title = 'Bon de Livraison';

    protected static string|BackedEnum|null $icon = Phosphor::Truck;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->emptyStateHeading('Aucun BL pour cette commande')
            ->emptyStateDescription('Créer un nouveau BL pour commencer.')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro BL')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('delivery_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Nb articles')
                    ->counts('items'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DeliveryStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nouveau BL')
                    ->schema([
                        DatePicker::make('delivered_at')
                            ->label('Date de livraison')
                            ->required()
                            ->default(now()),

                        Repeater::make('items')
                            ->label('Lignes de livraison')
                            ->columns(2)
                            ->schema([
                                Select::make('customer_order_item_id')
                                    ->label('Article commandé')
                                    ->options(function (RelationManager $livewire) {
                                        $order = $livewire->getOwnerRecord();
                                        if (! $order) {
                                            return [];
                                        }

                                        // On récupère les items de la commande et on affiche leur nom
                                        return $order->items()
                                            ->whereHas('item', function (Builder $query) {
                                                $query->whereIn('type', [ItemType::STOCKABLE, ItemType::CONSUMABLE]);
                                            })
                                            ->with('item') // Eager load pour l'accès au nom
                                            ->get()
                                            ->pluck('item.name', 'id');
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                                        $orderItem = CustomerOrderItem::find($state);
                                        $deliveryItem = CustomerDeliveryNoteItem::where('item_id', $orderItem->item_id)->sum('quantity_delivered');
                                        $sub = $deliveryItem - $orderItem->quantity;

                                        $set('quantity_delivered', $sub);
                                    })
                                    ->preload()
                                    ->required(),

                                TextInput::make('quantity_delivered')
                                    ->label('Quantité livrée')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('notes')
                                    ->label('Notes')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->using(function (array $data, RelationManager $livewire) {
                        $order = $livewire->getOwnerRecord();

                        $deliveryNote = CustomerDeliveryNote::create([
                            'customer_order_id' => $order->id,
                            'client_id' => $order->client_id,
                            'chantier_id' => $order->chantier_id,
                            'reference' => 'BL-'.date('Y').'-'.str_pad(CustomerDeliveryNote::count() + 1, 5, '0', STR_PAD_LEFT),
                            'status' => DeliveryStatus::PREPARATION,
                            'delivery_date' => $data['delivered_at'] ?? now(),
                            'responsable_id' => auth()->user()->id,
                        ]);

                        // Création des lignes de livraison
                        if (isset($data['items'])) {
                            foreach ($data['items'] as $item) {
                                $orderItem = CustomerOrderItem::find($item['customer_order_item_id']);
                                $item['item_id'] = $orderItem->item_id;
                                $deliveryNote->items()->create($item);
                            }
                        }

                        // Mise à jour du statut de la commande
                        if ($order->items()->count() === $deliveryNote->items()->sum('quantity_delivered')) {
                            $order->update(['status' => OrderStatus::DELIVERED]);
                        } else {
                            $order->update(['status' => OrderStatus::PARTIALLY_DELIVERED]);
                        }

                        return $deliveryNote;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver()
                        ->modalHeading(fn (Model $record) => "Détail du Bon de livraison: {$record->reference}")
                        ->modalFooterActions([
                            Action::make('pdf')
                                ->label('Imprimer')
                                ->icon(Phosphor::Printer)
                                ->action(fn (Model $record, CommerceDocumentationService $service) => response()->download($service->generateDeliveryNotePdf($record))),
                        ])
                        ->schema([
                            Section::make()
                                ->columnSpanFull()
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('reference')->label('Référence'),
                                    TextEntry::make('order.reference')->label('Commande de référence')->url(fn (Model $record) => url('/commerce/customer-orders/'.$record->order->id)),
                                    TextEntry::make('status')->label('Etat de la livraison')->badge(),
                                    TextEntry::make('delivery_date')->label('Date de livraison')->date('d/m/Y'),
                                    ViewEntry::make('items')
                                        ->view('filament.commerce.delivery_note_items_table')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('pdf')
                        ->label('Imprimer')
                        ->icon(Phosphor::Printer)
                        ->action(fn (Model $record, CommerceDocumentationService $service) => response()->download($service->generateDeliveryNotePdf($record))),

                    Action::make('createInvoice')
                        ->label('Facturer')
                        ->icon(Phosphor::FilePlus)
                        ->visible(fn (Model $record) => $record->order->status === OrderStatus::DELIVERED)
                        ->url(fn (Model $record) => route('filament.commerce.resources.customer-invoices.create', ['delivery' => $record->id]))
                        ->openUrlInNewTab(),

                    Action::make('changeStatus')
                        ->label('Changer le status')
                        ->icon(Phosphor::ArrowsDownUp)
                        ->color('purple')
                        ->schema([
                            Select::make('status')
                                ->label('Transition')
                                ->options(DeliveryStatus::class),
                        ])
                        ->action(function (array $data, CustomerDeliveryNote $record) {
                            $record->update(['status' => $data['status']]);
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
