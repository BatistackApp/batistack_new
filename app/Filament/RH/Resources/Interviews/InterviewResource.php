<?php

namespace App\Filament\RH\Resources\Interviews;

use App\Filament\RH\Resources\Interviews\Schemas\InterviewForm;
use App\Filament\RH\Resources\Interviews\Tables\InterviewsTable;
use App\Models\RH\Interview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Annuaire & Dossiers';

    protected static ?string $modelLabel = 'Entretien';

    protected static ?string $pluralModelLabel = 'Entretiens';

    public static function form(Schema $schema): Schema
    {
        return InterviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterviewsTable::configure($table);
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
            'index' => Pages\ListInterviews::route('/'),
            'create' => Pages\CreateInterview::route('/create'),
            'edit' => Pages\EditInterview::route('/{record}/edit'),
        ];
    }
}
