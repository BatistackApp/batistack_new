<?php

namespace App\Filament\Chantier\Resources\ChecklistTemplates;

use App\Filament\Chantier\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Chantier\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use App\Filament\Chantier\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use App\Filament\Chantier\Resources\ChecklistTemplates\Schemas\ChecklistTemplateForm;
use App\Filament\Chantier\Resources\ChecklistTemplates\Tables\ChecklistTemplatesTable;
use App\Models\Chantiers\ChecklistTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static ?string $modelLabel = 'Modèle de Checklist';

    protected static ?string $pluralModelLabel = 'Modèles de Checklist';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Schema $schema): Schema
    {
        return ChecklistTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistTemplatesTable::configure($table);
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
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}
