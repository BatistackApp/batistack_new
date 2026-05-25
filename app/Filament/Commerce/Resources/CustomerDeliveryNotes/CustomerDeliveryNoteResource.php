<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\CreateCustomerDeliveryNote;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\EditCustomerDeliveryNote;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\ListCustomerDeliveryNotes;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\ViewCustomerDeliveryNote;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas\CustomerDeliveryNoteForm;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas\CustomerDeliveryNoteInfolist;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Tables\CustomerDeliveryNotesTable;
use App\Models\Commerce\CustomerDeliveryNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerDeliveryNoteResource extends Resource
{
    protected static ?string $model = CustomerDeliveryNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Truck;

    protected static string|null|\UnitEnum $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Bon de Livraison';

    protected static ?string $pluralModelLabel = 'Bon de Livraison';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return CustomerDeliveryNoteForm::configure($schema);
    }

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
            'create' => CreateCustomerDeliveryNote::route('/create'),
            'view' => ViewCustomerDeliveryNote::route('/{record}'),
            'edit' => EditCustomerDeliveryNote::route('/{record}/edit'),
        ];
    }
}
