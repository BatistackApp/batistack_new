<?php

namespace App\Filament\Gpao\ManufacturingOrders;

use App\Filament\Gpao\ManufacturingOrders\Pages\CreateManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\Pages\EditManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\Pages\KanbanManufacturingOrders;
use App\Filament\Gpao\ManufacturingOrders\Pages\ListManufacturingOrders;
use App\Filament\Gpao\ManufacturingOrders\Pages\ViewManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\RelationManagers\QualityChecksRelationManager;
use App\Filament\Gpao\ManufacturingOrders\RelationManagers\RequirementsRelationManager;
use App\Filament\Gpao\ManufacturingOrders\Schemas\ManufacturingOrderForm;
use App\Filament\Gpao\ManufacturingOrders\Schemas\ManufacturingOrderInfolist;
use App\Filament\Gpao\ManufacturingOrders\Tables\ManufacturingOrdersTable;
use App\Models\Articles\Item;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\User;
use App\Notifications\Gpao\ProductionAlertNotification;
use App\Services\Gpao\ManufacturingScrapService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ManufacturingOrderResource extends Resource
{
    protected static ?string $model = ManufacturingOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Production';

    protected static ?string $modelLabel = 'Ordre de Fabrication';

    protected static ?string $pluralModelLabel = 'Ordres de Fabrication';

    public static function form(Schema $schema): Schema
    {
        return ManufacturingOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ManufacturingOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManufacturingOrdersTable::configure($table)
            ->pushActions([
                Action::make('declare_scrap')
                    ->label('Déclarer Rebut')
                    ->icon('heroicon-o-trash')
                    ->color('warning')
                    ->form([
                        Select::make('item_id')
                            ->label('Composant perdu')
                            ->options(fn (ManufacturingOrder $record) => $record->requirements()->with('item')->get()->pluck('item.name', 'item_id'))
                            ->required(),
                        TextInput::make('quantity')->label('Quantité')
                            ->label('Quantité (Rebut)')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required(),
                        Select::make('reason')
                            ->label('Motif')
                            ->options([
                                'machine_breakdown' => 'Casse Machine',
                                'material_defect' => 'Défaut Matière',
                                'human_error' => 'Erreur Humaine',
                            ])
                            ->required(),
                        Textarea::make('notes')->label('Notes')
                            ->label('Commentaires'),
                    ])
                    ->action(function (array $data, ManufacturingOrder $record, ManufacturingScrapService $scrapService) {
                        $item = Item::find($data['item_id']);
                        $scrapService->declareScrap($record, $item, (float) $data['quantity'], $data['reason'], $data['notes'] ?? null);

                        Notification::make()
                            ->title('Rebut déclaré et stock mis à jour')
                            ->success()
                            ->send();
                    }),
                Action::make('trigger_alert')
                    ->label('Alerte Urgence')
                    ->icon('heroicon-o-bell-alert')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ManufacturingOrder $record) {
                        $users = User::all(); // Dans la vraie vie, filtrer par rôle Chef d'Atelier
                        \Illuminate\Support\Facades\Notification::send($users, new ProductionAlertNotification(
                            '⚠️ Panne signalée !',
                            "Panne critique déclarée sur l'OF : {$record->reference}",
                            ManufacturingOrderResource::getUrl('view', ['record' => $record->id])
                        ));

                        Notification::make()
                            ->title('Alerte envoyée à toute l\'équipe')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RequirementsRelationManager::class,
            QualityChecksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturingOrders::route('/'),
            'kanban' => KanbanManufacturingOrders::route('/kanban'),
            'create' => CreateManufacturingOrder::route('/create'),
            'view' => ViewManufacturingOrder::route('/{record}'),
            'edit' => EditManufacturingOrder::route('/{record}/edit'),
        ];
    }
}
