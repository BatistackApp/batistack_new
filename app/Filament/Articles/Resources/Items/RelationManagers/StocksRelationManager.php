<?php

namespace App\Filament\Articles\Resources\Items\RelationManagers;

use App\Enums\Articles\ItemType;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Filament\Actions\Action;

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
                TextInput::make('quantity')
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
                TextEntry::make('warehouse.name'),
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
                TextColumn::make('quantity')
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
                Action::make('reserve')
                    ->label('Réserver')
                    ->icon(Phosphor::LockKey)
                    ->color('warning')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantité à réserver')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->getAvailableQuantity()),
                    ])
                    ->action(function ($record, array $data) {
                        app(\App\Services\Articles\StockService::class)->reserve($record->item, $record->warehouse, $data['quantity']);
                        \Filament\Notifications\Notification::make()->title('Stock réservé')->success()->send();
                    }),
                Action::make('release')
                    ->label('Libérer')
                    ->icon(Phosphor::LockKeyOpen)
                    ->color('gray')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantité à libérer')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->reserved_quantity),
                    ])
                    ->action(function ($record, array $data) {
                        app(\App\Services\Articles\StockService::class)->release($record->item, $record->warehouse, $data['quantity']);
                        \Filament\Notifications\Notification::make()->title('Stock libéré')->success()->send();
                    })
                    ->visible(fn ($record) => $record->reserved_quantity > 0),
                Action::make('consume')
                    ->label('Consommer Rsv.')
                    ->icon(Phosphor::Package)
                    ->color('primary')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantité à consommer')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->reserved_quantity),
                        \Filament\Forms\Components\Select::make('chantier_id')
                            ->label('Chantier')
                            ->options(\App\Models\Chantiers\Chantier::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $chantier = \App\Models\Chantiers\Chantier::find($data['chantier_id']);
                        if (!$chantier) return;
                        $reason = "Consommation pour le chantier : " . $chantier->name;

                        app(\App\Services\Articles\StockService::class)->consumeReserved(
                            $record->item,
                            $record->warehouse,
                            $data['quantity'],
                            $reason,
                            \App\Enums\Articles\StockMouvementSource::SITE,
                            $chantier->id
                        );
                        \Filament\Notifications\Notification::make()->title('Stock consommé')->success()->send();
                    })
                    ->visible(fn ($record) => $record->reserved_quantity > 0),
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
