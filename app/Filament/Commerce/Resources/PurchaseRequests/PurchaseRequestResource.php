<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests;

use App\Filament\Commerce\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Commerce\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Commerce\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Commerce\Resources\PurchaseRequests\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Commerce\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\Commerce\PurchaseRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ClipboardText;

    protected static ?string $navigationLabel = 'Demandes d\'achat';

    protected static string|\UnitEnum|null $navigationGroup = 'Achats';

    protected static ?string $modelLabel = 'Demande d\'achat';

    protected static ?string $pluralModelLabel = 'Demandes d\'achat';

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
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
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
