<?php

namespace App\Filament\Flottes\Resources\TrafficFines;

use App\Filament\Flottes\Resources\TrafficFines\Pages\CreateTrafficFine;
use App\Filament\Flottes\Resources\TrafficFines\Pages\EditTrafficFine;
use App\Filament\Flottes\Resources\TrafficFines\Pages\ListTrafficFines;
use App\Filament\Flottes\Resources\TrafficFines\Schemas\TrafficFineForm;
use App\Filament\Flottes\Resources\TrafficFines\Tables\TrafficFinesTable;
use App\Models\Flottes\TrafficFine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TrafficFineResource extends Resource
{
    protected static ?string $model = TrafficFine::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Gavel;

    protected static ?string $modelLabel = 'Procès-Verbal / Amende';

    protected static ?string $pluralModelLabel = 'Infractions & PV';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return TrafficFineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrafficFinesTable::configure($table);
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
            'index' => ListTrafficFines::route('/'),
            'create' => CreateTrafficFine::route('/create'),
            'edit' => EditTrafficFine::route('/{record}/edit'),
        ];
    }
}
