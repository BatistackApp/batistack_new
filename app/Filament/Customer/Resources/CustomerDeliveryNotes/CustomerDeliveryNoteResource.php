<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes;

use App\Filament\Customer\Resources\CustomerDeliveryNotes\Pages\ListCustomerDeliveryNotes;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\Pages\ViewCustomerDeliveryNote;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\RelationManagers\ItemsRelationManager;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\Schemas\CustomerDeliveryNoteInfolist;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\Tables\CustomerDeliveryNotesTable;
use App\Models\Commerce\CustomerDeliveryNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class CustomerDeliveryNoteResource extends Resource
{
    protected static ?string $model = CustomerDeliveryNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Truck;

    protected static ?string $navigationLabel = 'Mes Livraison';

    protected static ?string $modelLabel = 'Livraison';

    protected static ?string $pluralModelLabel = 'Livraisons';

    protected static string|UnitEnum|null $navigationGroup = 'Mes Achats et Prestations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function infolist(Schema $schema): Schema
    {
        return CustomerDeliveryNoteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerDeliveryNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerDeliveryNotes::route('/'),
            'view' => ViewCustomerDeliveryNote::route('/{record}'),
        ];
    }
}
