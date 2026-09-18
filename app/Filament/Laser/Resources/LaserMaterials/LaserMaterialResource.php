<?php

namespace App\Filament\Laser\Resources\LaserMaterials;

use App\Filament\Laser\Resources\LaserMaterials\Pages\CreateLaserMaterial;
use App\Filament\Laser\Resources\LaserMaterials\Pages\EditLaserMaterial;
use App\Filament\Laser\Resources\LaserMaterials\Pages\ListLaserMaterials;
use App\Filament\Laser\Resources\LaserMaterials\Schemas\LaserMaterialForm;
use App\Filament\Laser\Resources\LaserMaterials\Tables\LaserMaterialsTable;
use App\Models\Laser\LaserMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserMaterialResource extends Resource
{
    protected static ?string $model = LaserMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Cube;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel';

    protected static ?string $navigationLabel = 'Matériaux';

    protected static ?string $modelLabel = 'Matériau';

    protected static ?string $pluralModelLabel = 'Matériaux';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LaserMaterialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserMaterialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaserMaterials::route('/'),
            'create' => CreateLaserMaterial::route('/create'),
            'edit' => EditLaserMaterial::route('/{record}/edit'),
        ];
    }
}
