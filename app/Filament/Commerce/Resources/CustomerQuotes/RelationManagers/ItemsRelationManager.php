<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\RelationManagers;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Ligne du devis';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultGroup('lot_label')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Référence'),

                TextColumn::make('item.name')
                    ->label('Désignation'),

                TextColumn::make('selling_price')
                    ->money('EUR')
                    ->label('Prix Unitaire HT'),

                TextColumn::make('quantity')->label('Quantité')
                    ->label('Qte.'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->summarize(Sum::make('total_ht')->hiddenLabel()->money('EUR')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une ligne')
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

                        Grid::make(6)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('lot_label')
                                    ->label('Lot'),

                                TextInput::make('name')->label('Nom')
                                    ->required()
                                    ->label('Désignation'),

                                TextInput::make('quantity')->label('Quantité')
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
                        $quote = $livewire->getOwnerRecord();

                        $data['total_ht'] = $data['selling_price'] * $data['quantity'];
                        $data['purchase_price'] = 0;
                        $quote->items()->create($data);

                        $quote->update([
                            'total_ht' => $quote->items()->sum('total_ht'),
                            'total_ttc' => $quote->items()->sum(\DB::raw('total_ht * (1 + (select rate from vat_rates where id = vat_rate_id) / 100)')),
                        ]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Supprimer')
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $record->delete();
                        $record->quote()->update([
                            'total_ht' => $record->quote()->items()->sum('total_ht'),
                            'total_ttc' => $record->quote()->items()->sum(\DB::raw('total_ht * (1 + (select rate from vat_rates where id = vat_rate_id) / 100)')),
                        ]);
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->status->value !== QuoteStatus::DRAFT->value;
    }
}
