<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations;

use App\Filament\Commerce\Resources\SubcontractorSituations\Pages\CreateSubcontractorSituation;
use App\Filament\Commerce\Resources\SubcontractorSituations\Pages\EditSubcontractorSituation;
use App\Filament\Commerce\Resources\SubcontractorSituations\Pages\ListSubcontractorSituations;
use App\Filament\Commerce\Resources\SubcontractorSituations\Schemas\SubcontractorSituationForm;
use App\Filament\Commerce\Resources\SubcontractorSituations\Tables\SubcontractorSituationsTable;
use App\Models\Commerce\SubcontractorSituation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubcontractorSituationResource extends Resource
{
    protected static ?string $model = SubcontractorSituation::class;

    protected static string|BackedEnum|null $navigationIcon = \ToneGabes\Filament\Icons\Enums\Phosphor::Percent;
    protected static ?string $navigationLabel = 'Situations Sous-traitants';
    protected static string | \UnitEnum | null $navigationGroup = 'Achats';
    protected static ?string $modelLabel = 'Situation Sous-traitant';
    protected static ?string $pluralModelLabel = 'Situations Sous-traitants';

    public static function form(Schema $schema): Schema
    {
        return SubcontractorSituationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubcontractorSituationsTable::configure($table);
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
            'index' => ListSubcontractorSituations::route('/'),
            'create' => CreateSubcontractorSituation::route('/create'),
            'edit' => EditSubcontractorSituation::route('/{record}/edit'),
        ];
    }
}
