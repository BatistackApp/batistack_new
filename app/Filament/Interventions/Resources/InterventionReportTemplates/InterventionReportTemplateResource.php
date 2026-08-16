<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates;

use App\Filament\Interventions\Resources\InterventionReportTemplates\Pages\CreateInterventionReportTemplate;
use App\Filament\Interventions\Resources\InterventionReportTemplates\Pages\EditInterventionReportTemplate;
use App\Filament\Interventions\Resources\InterventionReportTemplates\Pages\ListInterventionReportTemplates;
use App\Filament\Interventions\Resources\InterventionReportTemplates\Schemas\InterventionReportTemplateForm;
use App\Filament\Interventions\Resources\InterventionReportTemplates\Tables\InterventionReportTemplatesTable;
use App\Models\Interventions\InterventionReportTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InterventionReportTemplateResource extends Resource
{
    protected static ?string $model = InterventionReportTemplate::class;

    protected static ?string $modelLabel = 'Modèle de Rapport d\'Intervention';
    protected static ?string $pluralModelLabel = 'Modèles de Rapport d\'Intervention';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 15;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Schema $schema): Schema
    {
        return InterventionReportTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterventionReportTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterventionReportTemplates::route('/'),
            'create' => CreateInterventionReportTemplate::route('/create'),
            'edit' => EditInterventionReportTemplate::route('/{record}/edit'),
        ];
    }
}