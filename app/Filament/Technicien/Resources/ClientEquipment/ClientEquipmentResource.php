<?php

namespace App\Filament\Technicien\Resources\ClientEquipment;

use App\Filament\Technicien\Resources\ClientEquipment\Pages\ListClientEquipment;
use App\Filament\Technicien\Resources\ClientEquipment\Pages\ViewClientEquipment;
use App\Filament\Technicien\Resources\ClientEquipment\Schemas\ClientEquipmentInfolist;
use App\Filament\Technicien\Resources\ClientEquipment\Tables\ClientEquipmentTable;
use App\Models\Interventions\ClientEquipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ClientEquipmentResource extends Resource
{
    protected static ?string $model = ClientEquipment::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::HardDrives;

    protected static ?string $modelLabel = 'Équipements';

    protected static ?string $pluralModelLabel = 'Équipements';

    protected static UnitEnum|string|null $navigationGroup = 'Interventions';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        $employeeId = auth()->user()?->salarie?->id;

        if (! $employeeId) {
            return false;
        }

        return $record->interventions()->whereHas('workers', fn ($q) => $q->where('employee_id', $employeeId))->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        $employeeId = auth()->user()?->salarie?->id;

        return parent::getEloquentQuery()
            ->whereHas('interventions.workers', fn ($q) => $q->where('employee_id', $employeeId))
            ->distinct();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientEquipment::route('/'),
            'view' => ViewClientEquipment::route('/{record}'),
        ];
    }
}
