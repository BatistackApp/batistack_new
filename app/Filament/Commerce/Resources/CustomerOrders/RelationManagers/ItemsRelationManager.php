<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles / Services';

    protected static string|BackedEnum|null $icon = Phosphor::Package;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('#'),

                TextColumn::make('item.name')
                    ->label('Désignation')
                    ->description(fn (Model $record) => $record->item->description),

                TextColumn::make('quantity')
                    ->label('Qte.')
                    ->numeric(),

                TextColumn::make('item.unit.symbol')
                    ->label('Unit'),

                TextColumn::make('selling_price')
                    ->label('Prix Unitaire HT')
                    ->money('EUR'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->summarize(Sum::make()->money('EUR')->label('Total HT')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une ligne')
                    ->modalHeading('Ajouter une ligne')
                    ->schema([
                        Select::make('item_id')
                            ->label('Article/Service')
                            ->columnSpanFull()
                            ->options(Item::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Selectionner un article ou un service pour mettre à jour les données dans le formulaire')
                            ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                                $item = Item::find($state);

                                $set('name', $item->name);
                                $set('selling_price', $item->selling_price);
                                $set('purchase_price', $item->purchase_price);

                                $quantity = (float) ($get('quantity') ?? 0);
                                $sellingPrice = (float) ($get('selling_price') ?? 0);
                                $subtotalHt = $quantity * $sellingPrice;
                                $set('subtotal_ht', number_format($subtotalHt, 2, '.', ''));
                            }),

                        Grid::make(5)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Désignation'),

                                TextInput::make('quantity')
                                    ->required()
                                    ->label('Quantité')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) { // Corrected: removed $livewire
                                        $quantity = (float) ($get('quantity') ?? 0);
                                        $sellingPrice = (float) ($get('selling_price') ?? 0);
                                        $subtotalHt = $quantity * $sellingPrice;
                                        $set('subtotal_ht', number_format($subtotalHt, 2, '.', ''));
                                    })
                                    ->numeric(),

                                TextInput::make('selling_price')
                                    ->label('Prix Unitaire HT')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) { // Corrected: removed $livewire
                                        $quantity = (float) ($get('quantity') ?? 0);
                                        $sellingPrice = (float) ($get('selling_price') ?? 0);
                                        $subtotalHt = $quantity * $sellingPrice;
                                        $set('subtotal_ht', number_format($subtotalHt, 2, '.', ''));
                                    })
                                    ->prefix('€'),

                                Select::make('vat_rate_id')
                                    ->label('TVA')
                                    ->required()
                                    ->options(VatRate::all()->pluck('name', 'id')),

                                TextInput::make('subtotal_ht')
                                    ->label('Sous-total HT')
                                    ->disabled()
                                    ->prefix('€'),

                                TextInput::make('purchase_price')
                                    ->hidden(),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $order = $livewire->getOwnerRecord();

                        $data['total_ht'] = $data['selling_price'] * $data['quantity'];
                        $data['purchase_price'] = 0;
                        $order->items()->create($data);

                        $order->update([
                            'total_ht' => $order->items()->sum('total_ht'),
                            'total_ttc' => $order->items()->sum(\DB::raw('total_ht * (1 + (select rate from vat_rates where id = vat_rate_id) / 100)')),
                        ]);
                    })
                    ->icon(Phosphor::Plus),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->iconButton()
                    ->icon(Phosphor::XBold)
                    ->tooltip('Supprimer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->ownerRecord->status === OrderStatus::DRAFT;
    }
}
