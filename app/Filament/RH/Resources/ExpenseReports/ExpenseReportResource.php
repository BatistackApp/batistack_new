<?php

namespace App\Filament\RH\Resources\ExpenseReports;

use App\Filament\RH\Resources\ExpenseReports\Pages\CreateExpenseReport;
use App\Filament\RH\Resources\ExpenseReports\Pages\EditExpenseReport;
use App\Filament\RH\Resources\ExpenseReports\Pages\ListExpenseReports;
use App\Filament\RH\Resources\ExpenseReports\Pages\ViewExpenseReport;
use App\Filament\RH\Resources\ExpenseReports\Schemas\ExpenseReportForm;
use App\Filament\RH\Resources\ExpenseReports\Schemas\ExpenseReportInfolist;
use App\Filament\RH\Resources\ExpenseReports\Tables\ExpenseReportsTable;
use App\Models\RH\ExpenseReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpenseReportResource extends Resource
{
    protected static ?string $model = ExpenseReport::class;

    protected static ?string $modelLabel = 'Note de frais';
    protected static ?string $pluralModelLabel = 'Notes de frais';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ExpenseReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpenseReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\RH\Resources\ExpenseReports\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseReports::route('/'),
            'create' => CreateExpenseReport::route('/create'),
            'view' => ViewExpenseReport::route('/{record}'),
            'edit' => EditExpenseReport::route('/{record}/edit'),
        ];
    }
}
