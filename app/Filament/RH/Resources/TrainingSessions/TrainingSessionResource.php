<?php

namespace App\Filament\RH\Resources\TrainingSessions;

use App\Filament\RH\Resources\TrainingSessions\Pages\CreateTrainingSession;
use App\Filament\RH\Resources\TrainingSessions\Pages\EditTrainingSession;
use App\Filament\RH\Resources\TrainingSessions\Pages\ListTrainingSessions;
use App\Filament\RH\Resources\TrainingSessions\Schemas\TrainingSessionForm;
use App\Filament\RH\Resources\TrainingSessions\Tables\TrainingSessionsTable;
use App\Models\RH\TrainingSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrainingSessionResource extends Resource
{
    protected static ?string $model = TrainingSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Annuaire & Dossiers';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Formations';

    protected static ?string $pluralModelLabel = 'Formations';

    public static function form(Schema $schema): Schema
    {
        return TrainingSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingSessions::route('/'),
            'create' => CreateTrainingSession::route('/create'),
            'edit' => EditTrainingSession::route('/{record}/edit'),
        ];
    }
}
