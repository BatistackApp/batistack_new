<?php

namespace App\Filament\Articles\Resources\Items\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Models\Chantiers\Chantier;
use App\Services\Articles\StockService;
use Ariefng\FilamentCalculator\Actions\CalculatorAction;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use MarceloRodigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'État des stocks par dépôt';

    protected static string|null|\BackedEnum $icon = Phosphor::Warehouse;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === ItemType::STOCKABLE;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('Dépôt')
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->disabledOn('edit')
                    ->native(false),
                TextInput::make('quantity')->label('Quantité')
                    ->label('Quantité en stock')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->suffix(fn ($livewire) => $livewire->getOwnerRecord()->unit->symbol),
                TextInput::make('min_threshold')
                    ->label('Seuil d\'alerte')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->helperText('Une notification sera envoyée si le stock descend sous ce seuil.'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dépôt')
                    ->columnSpanFull()
                    ->description(fn ($record) => $record->warehouse?->name)
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Stock physique')
                            ->state(fn ($record) => number_format((float) $record->quantity, 2, ',', ' ')." {$record->item->unit->symbol}"),
                        TextEntry::make('reserved_quantity')
                            ->label('Réservé')
                            ->state(fn ($record) => number_format((float) $record->reserved_quantity, 2, ',', ' ')." {$record->item->unit->symbol}"),
                        TextEntry::make('available')
                            ->label('Disponible')
                            ->state(fn ($record) => number_format($record->getAvailableQuantity(), 2, ',', ' ')." {$record->item->unit->symbol}"),
                    ])->columns(3),

                Section::make('Mouvements de stock')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('mouvements')
                            ->label('Mouvements')
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->state(fn ($record) => $record->type?->getLabel())
                                    ->color(fn ($record) => $record->type?->getColor()),
                                TextEntry::make('quantity_delta')
                                    ->label('Quantité')
                                    ->state(fn ($record) => ((float) $record->quantity_delta >= 0 ? '+' : '').number_format((float) $record->quantity_delta, 2, ',', ' ')),
                                TextEntry::make('batch_number')
                                    ->label('N° de lot')
                                    ->placeholder('—'),
                                TextEntry::make('expiration_date')
                                    ->label('Péremption')
                                    ->date('d/m/Y')
                                    ->placeholder('—'),
                                TextEntry::make('reason')
                                    ->label('Motif')
                                    ->placeholder('—'),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse.name')
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Dépôt')
                    ->weight('bold'),
                TextColumn::make('quantity')->label('Quantité')
                    ->label('Physique')
                    ->numeric(decimalPlaces: 2)
                    ->color('gray')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('reserved_quantity')
                    ->label('Réservé')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color('warning')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('available')
                    ->label('Disponible')
                    ->state(fn ($record) => $record->getAvailableQuantity())
                    ->numeric(decimalPlaces: 2)
                    ->weight('bold')
                    ->color(fn ($record) => $record->getAvailableQuantity() <= $record->min_threshold ? 'danger' : 'success')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('min_threshold')
                    ->label('Seuil mini')
                    ->numeric(decimalPlaces: 2)
                    ->color('gray'),
                TextColumn::make('locations_summary')
                    ->label('Emplacements')
                    ->state(fn ($record) => $record->locations->pluck('location_code')->filter()->implode(', ') ?: '—')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Initialiser un dépôt')
                    ->icon(Phosphor::Plus),
                AssociateAction::make(),
            ])
            ->recordActions([
                Action::make('mouvementer')
                    ->label('Mouvementer')
                    ->icon(Phosphor::ArrowsClockwise)
                    ->color('primary')
                    ->form([
                        Select::make('type')
                            ->label('Type de mouvement')
                            ->options([
                                'in' => 'Entrée',
                                'out' => 'Sortie',
                            ])
                            ->required(),
                        TextInput::make('quantity')->label('Quantité')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffixAction(CalculatorAction::make()),
                        BarcodeInput::make('location_code')
                            ->label('Emplacement (optionnel)')
                            ->nullable()
                            ->placeholder('Scanner ou laisser vide pour FIFO'),
                        TextInput::make('batch_number')->label('Numéro de lot')
                            ->required(fn ($get, $livewire) => $get('type') === 'in' && $livewire->getOwnerRecord()->is_sensitive),
                        DatePicker::make('expiration_date')->label('Date de péremption')
                            ->required(fn ($get, $livewire) => $get('type') === 'in' && $livewire->getOwnerRecord()->is_sensitive),
                        Textarea::make('description')->label('Description'),
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->createMouvement(
                            $record->item,
                            $record->warehouse,
                            $data['type'],
                            $data['quantity'],
                            $data['description'],
                            null,
                            null,
                            $data['batch_number'],
                            $data['expiration_date'],
                            $data['location_code'] ?? null
                        );
                        Notification::make()->title('Mouvement de stock créé')->success()->send();
                    }),
                Action::make('reserve')
                    ->label('Réserver')
                    ->icon(Phosphor::LockKey)
                    ->color('warning')
                    ->form([
                        TextInput::make('quantity')->label('Quantité')
                            ->label('Quantité à réserver')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->getAvailableQuantity()),
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->reserve($record->item, $record->warehouse, $data['quantity']);
                        Notification::make()->title('Stock réservé')->success()->send();
                    }),
                Action::make('release')
                    ->label('Libérer')
                    ->icon(Phosphor::LockKeyOpen)
                    ->color('gray')
                    ->form([
                        TextInput::make('quantity')->label('Quantité')
                            ->label('Quantité à libérer')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->reserved_quantity),
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->release($record->item, $record->warehouse, $data['quantity']);
                        Notification::make()->title('Stock libéré')->success()->send();
                    })
                    ->visible(fn ($record) => $record->reserved_quantity > 0),
                Action::make('consume')
                    ->label('Consommer Rsv.')
                    ->icon(Phosphor::Package)
                    ->color('primary')
                    ->form([
                        TextInput::make('quantity')->label('Quantité')
                            ->label('Quantité à consommer')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->reserved_quantity),
                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier')
                            ->options(Chantier::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $chantier = Chantier::find($data['chantier_id']);
                        if (! $chantier) {
                            return;
                        }
                        $reason = 'Consommation pour le chantier : '.$chantier->name;

                        app(StockService::class)->consumeReserved(
                            $record->item,
                            $record->warehouse,
                            $data['quantity'],
                            $reason,
                            StockMouvementSource::SITE,
                            $chantier->id
                        );
                        Notification::make()->title('Stock consommé')->success()->send();
                    })
                    ->visible(fn ($record) => $record->reserved_quantity > 0),
                Action::make('assignBin')
                    ->label('Assigner emplacement')
                    ->icon(Phosphor::MapPin)
                    ->color('info')
                    ->form([
                        BarcodeInput::make('location_code')
                            ->label('Emplacement (scanner le code du bin)')
                            ->autofocus()
                            ->live(debounce: 500)
                            ->required()
                            ->placeholder('Ex: A01-R03-S02-B05'),
                        TextInput::make('quantity')
                            ->label('Quantité à assigner')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn ($record) => $record->quantity)
                            ->suffix(fn ($record) => $record->item->unit->symbol),
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->assignToLocation(
                            $record,
                            $data['location_code'],
                            $data['quantity']
                        );
                        Notification::make()->title('Emplacement assigné')->success()->send();
                    }),
                Action::make('moveBin')
                    ->label('Déplacer emplacement')
                    ->icon(Phosphor::ArrowsLeftRight)
                    ->color('warning')
                    ->form([
                        Select::make('from_location')
                            ->label('De (emplacement source)')
                            ->options(fn ($record) => $record->locations->pluck('location_code', 'location_code'))
                            ->required()
                            ->native(false),
                        BarcodeInput::make('to_location')
                            ->label('Vers (scanner le code cible)')
                            ->autofocus()
                            ->live(debounce: 500)
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Quantité à déplacer')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->moveLocation(
                            $record,
                            $data['from_location'],
                            $data['to_location'],
                            $data['quantity']
                        );
                        Notification::make()->title('Emplacement déplacé')->success()->send();
                    })
                    ->visible(fn ($record) => $record->locations()->hasQuantity()->count() > 0),
                ViewAction::make(),
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
