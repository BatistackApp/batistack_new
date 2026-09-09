<?php

namespace App\Filament\Laser\Resources\LaserQuotes;

use App\Filament\Laser\Resources\LaserQuotes\Pages\CreateLaserQuote;
use App\Filament\Laser\Resources\LaserQuotes\Pages\EditLaserQuote;
use App\Filament\Laser\Resources\LaserQuotes\Pages\ListLaserQuotes;
use App\Filament\Laser\Resources\LaserQuotes\Pages\ViewLaserQuote;
use App\Filament\Laser\Resources\LaserQuotes\RelationManagers\LinesRelationManager;
use App\Filament\Laser\Resources\LaserQuotes\Schemas\LaserQuoteForm;
use App\Filament\Laser\Resources\LaserQuotes\Tables\LaserQuotesTable;
use App\Models\Laser\LaserQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserQuoteResource extends Resource
{
    protected static ?string $model = LaserQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::FileText;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Devis';

    protected static ?string $modelLabel = 'Devis Laser';

    protected static ?string $pluralModelLabel = 'Devis Laser';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('client');
    }

    public static function form(Schema $schema): Schema
    {
        return LaserQuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserQuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaserQuotes::route('/'),
            'create' => CreateLaserQuote::route('/create'),
            'view' => ViewLaserQuote::route('/{record}'),
            'edit' => EditLaserQuote::route('/{record}/edit'),
        ];
    }
}
