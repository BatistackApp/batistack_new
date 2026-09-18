<?php

namespace App\Filament\Laser\Resources\LaserInvoices;

use App\Filament\Laser\Resources\LaserInvoices\Pages\ListLaserInvoices;
use App\Filament\Laser\Resources\LaserInvoices\Pages\ViewLaserInvoice;
use App\Filament\Laser\Resources\LaserInvoices\RelationManagers\LinesRelationManager;
use App\Filament\Laser\Resources\LaserInvoices\Schemas\LaserInvoiceForm;
use App\Filament\Laser\Resources\LaserInvoices\Tables\LaserInvoicesTable;
use App\Models\Laser\LaserInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserInvoiceResource extends Resource
{
    protected static ?string $model = LaserInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Receipt;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $pluralModelLabel = 'Factures';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'order']);
    }

    public static function form(Schema $schema): Schema
    {
        return LaserInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserInvoicesTable::configure($table);
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
            'index' => ListLaserInvoices::route('/'),
            'view' => ViewLaserInvoice::route('/{record}'),
        ];
    }
}
