<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Articles\Item;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;

class SupplierInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de facture')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')->label('Référence')
                            ->label('Numéro de facture')
                            ->required(),

                        Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('purchase_order_id')
                            ->label('Bon de commande')
                            ->relationship('order', 'reference')
                            ->searchable()
                            ->preload(),

                        DatePicker::make('due_date')
                            ->label('Échéance')
                            ->required(),

                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options(InvoiceStatus::class)
                            ->required()
                            ->native(false)
                            ->disabled(),

                        TextInput::make('invoice_date')
                            ->label('Date de facture')
                            ->type('date')
                            ->required(),
                    ]),

                Section::make('Lignes de facture')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->columns(4)
                            ->schema([
                                BarcodeInput::make('barcode')
                                    ->label('Scanner')
                                    ->columnSpan(4)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $item = Item::where('barcode', $state)->first();
                                            if ($item) {
                                                $set('item_id', $item->id);
                                                $set('name', $item->name);
                                                $set('price_unit', $item->purchase_price ?? 0);
                                                $quantity = (float) ($get('quantity') ?? 1);
                                                $set('quantity', $quantity);
                                                $set('subtotal_ht', number_format($quantity * (float) $item->purchase_price, 2, '.', ''));
                                            } else {
                                                Notification::make()->danger()->title('Article introuvable')->send();
                                            }
                                        }
                                    }),

                                Select::make('item_id')
                                    ->label('Article')
                                    ->options(Item::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpan(2)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $item = Item::find($state);
                                        if ($item) {
                                            $set('name', $item->name);
                                            $set('price_unit', $item->purchase_price ?? 0);
                                            $quantity = (float) ($get('quantity') ?? 1);
                                            $set('quantity', $quantity);
                                            $set('subtotal_ht', number_format($quantity * (float) $item->purchase_price, 2, '.', ''));
                                        }
                                    }),

                                TextInput::make('name')->label('Nom')
                                    ->label('Description')
                                    ->columnSpan(2)
                                    ->required(),

                                TextInput::make('quantity')->label('Quantité')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('price_unit')
                                    ->label('Prix unitaire HT')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€'),

                                TextInput::make('subtotal_ht')
                                    ->label('Sous-total HT')
                                    ->disabled()
                                    ->prefix('€'),
                            ]),
                    ]),

                Section::make('Totaux')
                    ->columns(3)
                    ->schema([
                        TextInput::make('amount_ht')
                            ->label('Total HT')
                            ->disabled()
                            ->prefix('€'),

                        TextInput::make('amount_tax')
                            ->label('Total TVA')
                            ->disabled()
                            ->prefix('€'),

                        TextInput::make('amount_ttc')
                            ->label('Total TTC')
                            ->disabled()
                            ->prefix('€'),
                    ]),

                Section::make('Audit 3 voies')
                    ->schema([
                        Select::make('status')->label('Statut')
                            ->label('Statut de l\'audit')
                            ->options(InvoiceStatus::class)
                            ->required()
                            ->native(false),

                        Textarea::make('dispute_reason')
                            ->label('Raison du litige (si applicable)')
                            ->rows(3)
                            ->visible(fn ($get) => $get('status') === InvoiceStatus::LITIGE->value),
                    ]),
            ]);
    }
}
