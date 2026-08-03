<?php

namespace App\Filament\Interventions;

use App\Filament\Interventions\Pages\CreateIntervention;
use App\Filament\Interventions\Pages\EditIntervention;
use App\Filament\Interventions\Pages\ListInterventions;
use App\Filament\Interventions\Schemas\InterventionForm;
use App\Filament\Interventions\Tables\InterventionsTable;
use App\Models\Interventions\Intervention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InterventionResource extends Resource
{
    protected static ?string $model = Intervention::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InterventionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Interventions\Schemas\InterventionInfolist::configure($schema);
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
            'create' => CreateIntervention::route('/create'),
            'view' => \App\Filament\Interventions\Pages\ViewIntervention::route('/{record}'),
            'edit' => EditIntervention::route('/{record}/edit'),
        ];
    }
}
