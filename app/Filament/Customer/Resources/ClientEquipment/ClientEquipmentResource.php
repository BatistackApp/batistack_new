<?php

namespace App\Filament\Customer\Resources\ClientEquipment;

use App\Filament\Customer\Resources\ClientEquipment\Pages\EditClientEquipment;
use App\Filament\Customer\Resources\ClientEquipment\Pages\ListClientEquipment;
use App\Filament\Customer\Resources\ClientEquipment\Pages\ViewClientEquipment;
use App\Filament\Customer\Resources\ClientEquipment\Schemas\ClientEquipmentForm;
use App\Filament\Customer\Resources\ClientEquipment\Schemas\ClientEquipmentInfolist;
use App\Filament\Customer\Resources\ClientEquipment\Tables\ClientEquipmentTable;
use App\Models\Interventions\ClientEquipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientEquipmentResource extends Resource
{
    protected static ?string $model = ClientEquipment::class;

    protected static ?string $modelLabel = 'Mon Équipement';

    protected static ?string $pluralModelLabel = 'Mes Équipements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    use \App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;

    public static function form(Schema $schema): Schema
    {
        return ClientEquipmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientEquipmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientEquipmentTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientEquipment::route('/'),
            'view' => ViewClientEquipment::route('/{record}'),
            'edit' => EditClientEquipment::route('/{record}/edit'),
        ];
    }
}
