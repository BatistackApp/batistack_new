<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\InvoiceStatus;
use App\Models\Articles\Item;
use App\Models\Commerce\CustomerInvoiceItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles/Services';

    protected static string|BackedEnum|null $icon = Phosphor::Package;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('item_id')
                            ->label('Articles/Services')
                            ->searchable()
                            ->options(function () {
                                $items = Item::all();

                                $groupedOptions = $items->groupBy(fn ($item) => $item->type->value)
                                    ->map(function ($itemsInGroup, $itemTypeString) {
                                        $itemType = ItemType::from($itemTypeString);
                                        $groupLabel = $itemType->getLabel();

                                        $options = $itemsInGroup->pluck('name', 'id')->toArray();

                                        return [
                                            'label' => $groupLabel,
                                            'options' => $options,
                                        ];
                                    })
                                    ->pluck('options', 'label')
                                    ->toArray();

                                return $groupedOptions;
                            })
                            ->preload()
                            ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                                $item = Item::find($state);

                                $set('name', $item->name);
                                $set('price_unit', $item->selling_price);
                            })
                            ->reactive(),

                        TextInput::make('name')
                            ->label('Désignation')
                            ->required(),

                        TextInput::make('price_unit')
                            ->label('Prix Unitaire HT')
                            ->readOnly(fn (Get $get) => ! empty($get('item_id')))
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                                $set('total_ht', $state * $get('quantity'));
                            })
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Quantité')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, string $state) {
                                $set('total_ht', $state * $get('price_unit'));
                            })
                            ->numeric(),

                        Select::make('vat_rate_id')
                            ->label('TVA Appli.')
                            ->relationship('vatRate', 'name')
                            ->required(),

                        TextInput::make('total_ht')
                            ->label('Total HT')
                            ->numeric()
                            ->disabled(),

                    ]),
            ]);
    }

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        return Tab::make(self::$title)
            ->badge($ownerRecord->items->count())
            ->icon(self::$icon);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Référence'),

                TextColumn::make('name')
                    ->description(fn (Model $record) => $record->item->description)
                    ->label('Nom'),

                TextColumn::make('quantity')
                    ->numeric()
                    ->alignCenter()
                    ->label('Qte'),

                TextColumn::make('item.unit.name')
                    ->alignCenter()
                    ->label('Unité'),

                TextColumn::make('price_unit')
                    ->label('Prix Unitaire HT')
                    ->alignEnd()
                    ->money('EUR'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->alignEnd()
                    ->money('EUR'),

                TextColumn::make('vatRate.rate')
                    ->label('TVA')
                    ->alignCenter()
                    ->numeric()
                    ->suffix('%'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un ligne')
                    ->modalHeading('Ajouter un ligne')
                    ->action(function (array $data, RelationManager $livewire) {
                        $data['customer_invoice_id'] = $livewire->getOwnerRecord()->id;
                        $data['total_ht'] = $data['price_unit'] * $data['quantity'];
                        $invoiceItem = $livewire->getOwnerRecord()->items()->create($data);

                        $livewire->getOwnerRecord()->update([
                            'total_ht' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit')),
                            'total_tva' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit * ( (SELECT rate FROM vat_rates WHERE id = customer_invoice_items.vat_rate_id) / 100)')),
                            'total_ttc' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit * (1 + (SELECT rate FROM vat_rates WHERE id = customer_invoice_items.vat_rate_id) / 100)')),
                        ]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->action(function (array $data, Model $record, RelationManager $livewire) {
                        $record->delete();

                        // Calcule des nouveau totaux
                        $record->invoice->update([
                            'total_ht' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit')),
                            'total_tva' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit * ( (SELECT rate FROM vat_rates WHERE id = customer_invoice_items.vat_rate_id) / 100)')),
                            'total_ttc' => $livewire->getOwnerRecord()->items()->sum(\DB::raw('customer_invoice_items.quantity * customer_invoice_items.price_unit * (1 + (SELECT rate FROM vat_rates WHERE id = customer_invoice_items.vat_rate_id) / 100)')),
                        ]);
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->ownerRecord->status !== InvoiceStatus::DRAFT;
    }
}
