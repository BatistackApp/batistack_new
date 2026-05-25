<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Models\Commerce\CustomerDeliveryNoteItem;
use App\Models\Commerce\CustomerOrderItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_id')
                    ->label('Article')
                    ->relationship(
                        name: 'orderItem',
                        titleAttribute: 'id',
                        modifyQueryUsing: function (Builder $query, RelationManager $livewire) {
                            $orderId = $livewire->getOwnerRecord()->customer_order_id;

                            // Sécurité: si nous n'avons pas d'ID de commande, ne rien afficher.
                            if (! $orderId) {
                                return $query->whereNull('id');
                            }
                            // 2. Filtrer les articles de commande pour qu'ils appartiennent à la bonne commande.
                            $query->where('customer_order_id', $orderId);

                            // 3. Filtrer en utilisant une sous-requête sur la relation 'item' pour vérifier le type.
                            $query->whereHas('item', function (Builder $subQuery) {
                                $subQuery->where('type', ItemType::CONSUMABLE)
                                    ->orWhere('type', ItemType::STOCKABLE);
                            });

                            return $query;
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} (Restant: {$record->quantity_undelivered})")
                    ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                        $item = CustomerOrderItem::find($state);
                        $set('quantity_undelivered', $item->quantity_undelivered);
                    })
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->required(),

                TextEntry::make('quantity_undelivered')
                    ->label('Qte à livrer')
                    ->default(0),

                TextInput::make('quantity_delivered')
                    ->label('Quantité à livré')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_id')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item.name')
                    ->label('Désignation'),

                TextColumn::make('quantity_ordered')
                    ->badge()
                    ->label('Qte Commandée'),

                TextColumn::make('quantity_in_stock')
                    ->badge()
                    ->numeric(2)
                    ->label('Qte en stock'),

                TextColumn::make('quantity_delivered')
                    ->badge()
                    ->numeric(2)
                    ->label('Qte Livrée'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un article')
                    ->action(function (array $data, RelationManager $livewire) {
                        $orderItem = $livewire->getOwnerRecord()->order->items()->where('id', $data['item_id'])->first();
                        CustomerDeliveryNoteItem::create([
                            'customer_delivery_note_id' => $livewire->getOwnerRecord()->id,
                            'customer_order_item_id' => $data['item_id'],
                            'item_id' => $orderItem->item_id,
                            'quantity_delivered' => $data['quantity_delivered'],
                        ]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
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
