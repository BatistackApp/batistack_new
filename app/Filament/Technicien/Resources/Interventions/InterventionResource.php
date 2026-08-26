<?php

namespace App\Filament\Technicien\Resources\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Filament\Technicien\Resources\Interventions\Pages\CreateIntervention;
use App\Filament\Technicien\Resources\Interventions\Pages\EditIntervention;
use App\Filament\Technicien\Resources\Interventions\Pages\ListInterventions;
use App\Filament\Technicien\Resources\Interventions\Schemas\InterventionForm;
use App\Filament\Technicien\Resources\Interventions\Tables\InterventionsTable;
use App\Models\Interventions\Intervention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class InterventionResource extends Resource
{
    protected static ?string $model = Intervention::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Wrench;

    protected static ?string $modelLabel = 'Mes Interventions';

    protected static ?string $pluralModelLabel = 'Mes Interventions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('workers', function ($query) {
                // Filtre sur le salarié connecté
                $salarieId = auth()->user()?->salarie?->id;
                if ($salarieId) {
                    $query->where('employee_id', $salarieId);
                }
            })
            ->where('status', '!=', InterventionStatus::BROUILLON->value);
    }

    public static function form(Schema $schema): Schema
    {
        return InterventionForm::configure($schema);
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
            'edit' => EditIntervention::route('/{record}/edit'),
        ];
    }
}
