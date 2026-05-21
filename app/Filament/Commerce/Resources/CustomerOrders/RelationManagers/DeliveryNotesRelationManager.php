<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Services\Commerce\CommerceDocumentationService;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DeliveryNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryNotes';
    protected static ?string $title = 'Bon de Livraison';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->label('Numéro BL')
                    ->disabled(),

                Select::make('status')
                    ->label('Statut')
                    ->options(DeliveryStatus::class)
                    ->required(),

                DatePicker::make('delivered_at')
                    ->label('Date de livraison')
                    ->required(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro BL')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('delivered_at')
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
                            ->relationship()
                            ->columns(3)
                            ->schema([
                                Select::make('order_item_id')
                                    ->label('Article commandé')
                                    ->relationship('orderItem', 'name')
                                    ->searchable()
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
                            'reference' => 'BL-' . date('Y') . '-' . str_pad(CustomerDeliveryNote::count() + 1, 5, '0', STR_PAD_LEFT),
                            'status' => DeliveryStatus::PREPARATION,
                            'delivery_date' => $data['delivered_at'] ?? now(),
                        ]);

                        // Création des lignes de livraison
                        if (isset($data['items'])) {
                            foreach ($data['items'] as $item) {
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
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('pdf')
                        ->label('Imprimer')
                        ->icon(Phosphor::Printer)
                        ->action(fn(Model $record, CommerceDocumentationService $service) => response()->download($service->generateDeliveryNotePdf($record))),

                    Action::make('createInvoice')
                        ->label('Facturer')
                        ->icon(Phosphor::FilePlus)
                        ->visible(fn(Model $record) => $record->order->status === OrderStatus::DELIVERED)
                        ->url(fn(Model $record) => route('filament.commerce.resources.customer-invoices.create', ['delivery' => $record->id]))
                        ->openUrlInNewTab(),
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
