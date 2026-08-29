<?php

namespace App\Filament\Articles\Resources\Warehouses\RelationManagers;

use App\Models\Articles\StockLocation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MarceloRodigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Inventaire du dépôt';

    protected static string|null|\BackedEnum $icon = Phosphor::Package;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenu de l\'emplacement')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Stock physique')
                            ->state(fn ($record) => number_format((float) $record->quantity, 2, ',', ' ')." {$record->item->unit->symbol}"),
                        TextEntry::make('locations_list')
                            ->label('Emplacements')
                            ->state(fn ($record) => $record->locations->map(fn ($loc) => "{$loc->location_code}: ".number_format($loc->quantity, 2, ',', ' '))->implode(' | ') ?: 'Non assigné'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.reference')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Réf.')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable(),
                TextColumn::make('quantity')->label('Quantité')
                    ->label('Stock actuel')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($record) => $record->quantity <= $record->min_threshold ? 'danger' : 'success')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('locations_summary')
                    ->label('Emplacements')
                    ->state(fn ($record) => $record->locations->pluck('location_code')->filter()->implode(', ') ?: '—')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('scanBin')
                    ->label('Scanner emplacement')
                    ->icon(Phosphor::Scan)
                    ->color('primary')
                    ->form([
                        BarcodeInput::make('location_code')
                            ->label('Scanner le code du bin')
                            ->autofocus()
                            ->live(debounce: 500)
                            ->required()
                            ->placeholder('Scanner ou taper le code...'),
                    ])
                    ->action(function (array $data) {
                        $locationCode = $data['location_code'];
                        $ownerRecord = $this->getOwnerRecord();

                        $locations = StockLocation::where('location_code', $locationCode)
                            ->whereHas('stock', fn ($q) => $q->where('warehouse_id', $ownerRecord->id))
                            ->with('stock.item')
                            ->get();

                        if ($locations->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Aucun stock trouvé')
                                ->body("L'emplacement « {$locationCode} » ne contient aucun stock dans cet entrepôt.")
                                ->send();

                            return;
                        }

                        $content = $locations->map(function ($loc) {
                            $item = $loc->stock->item;

                            return "{$item->reference} — {$item->name}: ".number_format($loc->quantity, 2, ',', ' ')." {$item->unit?->symbol}";
                        })->implode("\n");

                        Notification::make()
                            ->success()
                            ->title("Contenu de {$locationCode}")
                            ->body($content)
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Ajuster seuil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
