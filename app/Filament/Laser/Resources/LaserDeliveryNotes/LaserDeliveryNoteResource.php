<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes;

use App\Filament\Laser\Resources\LaserDeliveryNotes\Pages\ListLaserDeliveryNotes;
use App\Filament\Laser\Resources\LaserDeliveryNotes\Pages\ViewLaserDeliveryNote;
use App\Filament\Laser\Resources\LaserDeliveryNotes\RelationManagers\LinesRelationManager;
use App\Filament\Laser\Resources\LaserDeliveryNotes\Schemas\LaserDeliveryNoteForm;
use App\Filament\Laser\Resources\LaserDeliveryNotes\Tables\LaserDeliveryNotesTable;
use App\Models\Laser\LaserDeliveryNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserDeliveryNoteResource extends Resource
{
    protected static ?string $model = LaserDeliveryNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Truck;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Bons de livraison';

    protected static ?string $modelLabel = 'Bon de livraison';

    protected static ?string $pluralModelLabel = 'Bons de livraison';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'order']);
    }

    public static function form(Schema $schema): Schema
    {
        return LaserDeliveryNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserDeliveryNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaserDeliveryNotes::route('/'),
            'view' => ViewLaserDeliveryNote::route('/{record}'),
        ];
    }
}
