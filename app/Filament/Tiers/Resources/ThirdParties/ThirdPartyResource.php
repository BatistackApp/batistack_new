<?php

namespace App\Filament\Tiers\Resources\ThirdParties;

use App\Filament\Tiers\Resources\ThirdParties\Pages\CreateThirdParty;
use App\Filament\Tiers\Resources\ThirdParties\Pages\EditThirdParty;
use App\Filament\Tiers\Resources\ThirdParties\Pages\ListThirdParties;
use App\Filament\Tiers\Resources\ThirdParties\Pages\ViewThirdParty;
use App\Filament\Tiers\Resources\ThirdParties\RelationManagers\AddressesRelationManager;
use App\Filament\Tiers\Resources\ThirdParties\RelationManagers\ContactsRelationManager;
use App\Filament\Tiers\Resources\ThirdParties\RelationManagers\DocumentsRelationManager;
use App\Filament\Tiers\Resources\ThirdParties\Schemas\ThirdPartyForm;
use App\Filament\Tiers\Resources\ThirdParties\Schemas\ThirdPartyInfolist;
use App\Filament\Tiers\Resources\ThirdParties\Tables\ThirdPartiesTable;
use App\Models\Tiers\ThirdParty;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ThirdPartyResource extends Resource
{
    protected static ?string $model = ThirdParty::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::AddressBook;

    protected static ?string $modelLabel = 'Tiers';

    protected static ?string $pluralModelLabel = 'Tiers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ThirdPartyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ThirdPartyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThirdPartiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
            ContactsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThirdParties::route('/'),
            'create' => CreateThirdParty::route('/create'),
            'view' => ViewThirdParty::route('/{record}'),
            'edit' => EditThirdParty::route('/{record}/edit'),
        ];
    }
}
