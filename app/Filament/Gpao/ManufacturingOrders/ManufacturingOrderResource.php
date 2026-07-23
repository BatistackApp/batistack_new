<?php

namespace App\Filament\Gpao\ManufacturingOrders;

use App\Filament\Gpao\ManufacturingOrders\Pages\CreateManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\Pages\EditManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\Pages\ListManufacturingOrders;
use App\Filament\Gpao\ManufacturingOrders\Pages\ViewManufacturingOrder;
use App\Filament\Gpao\ManufacturingOrders\Schemas\ManufacturingOrderForm;
use App\Filament\Gpao\ManufacturingOrders\Schemas\ManufacturingOrderInfolist;
use App\Filament\Gpao\ManufacturingOrders\Tables\ManufacturingOrdersTable;
use App\Models\Gpao\ManufacturingOrder;
use BackedEnum;
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
                \Filament\Tables\Actions\Action::make('trigger_alert')
                    ->label('Alerte Urgence')
                    ->icon('heroicon-o-bell-alert')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ManufacturingOrder $record) {
                        $users = \App\Models\User::all(); // Dans la vraie vie, filtrer par rôle Chef d'Atelier
                        \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\Gpao\ProductionAlertNotification(
                            '⚠️ Panne signalée !',
                            "Panne critique déclarée sur l'OF : {$record->reference}",
                            ManufacturingOrderResource::getUrl('view', ['record' => $record->id])
                        ));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Alerte envoyée à toute l\'équipe')
                            ->success()
                            ->send();
                    })
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Gpao\ManufacturingOrders\RelationManagers\RequirementsRelationManager::class,
            \App\Filament\Gpao\ManufacturingOrders\RelationManagers\QualityChecksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturingOrders::route('/'),
            'kanban' => \App\Filament\Gpao\ManufacturingOrders\Pages\KanbanManufacturingOrders::route('/kanban'),
            'create' => CreateManufacturingOrder::route('/create'),
            'view' => ViewManufacturingOrder::route('/{record}'),
            'edit' => EditManufacturingOrder::route('/{record}/edit'),
        ];
    }
}
