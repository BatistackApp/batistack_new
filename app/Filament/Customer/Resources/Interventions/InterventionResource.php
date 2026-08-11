<?php

namespace App\Filament\Customer\Resources\Interventions;

use App\Filament\Customer\Resources\Interventions\Pages\EditIntervention;
use App\Filament\Customer\Resources\Interventions\Pages\ListInterventions;
use App\Filament\Customer\Resources\Interventions\Pages\ViewIntervention;
use App\Filament\Customer\Resources\Interventions\Schemas\InterventionForm;
use App\Filament\Customer\Resources\Interventions\Schemas\InterventionInfolist;
use App\Filament\Customer\Resources\Interventions\Tables\InterventionsTable;
use App\Models\Interventions\Intervention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InterventionResource extends Resource
{
    protected static ?string $model = Intervention::class;

    protected static ?string $modelLabel = 'Mon Intervention';

    protected static ?string $pluralModelLabel = 'Mes Interventions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'reference';

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
        return InterventionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InterventionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterventionsTable::configure($table);
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
            'index' => ListInterventions::route('/'),
            'view' => ViewIntervention::route('/{record}'),
            'edit' => EditIntervention::route('/{record}/edit'),
        ];
    }

}
