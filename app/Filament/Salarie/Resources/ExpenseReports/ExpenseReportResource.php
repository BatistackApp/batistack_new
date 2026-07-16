<?php

namespace App\Filament\Salarie\Resources\ExpenseReports;

use App\Filament\Salarie\Resources\ExpenseReports\Pages\EditExpenseReport;
use App\Filament\Salarie\Resources\ExpenseReports\Pages\ListExpenseReports;
use App\Filament\Salarie\Resources\ExpenseReports\Pages\ViewExpenseReport;
use App\Filament\Salarie\Resources\ExpenseReports\RelationManagers\ItemsRelationManager;
use App\Filament\Salarie\Resources\ExpenseReports\Schemas\ExpenseReportForm;
use App\Filament\Salarie\Resources\ExpenseReports\Schemas\ExpenseReportInfolist;
use App\Filament\Salarie\Resources\ExpenseReports\Tables\ExpenseReportsTable;
use App\Models\RH\ExpenseReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseReportResource extends Resource
{
    protected static ?string $model = ExpenseReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'month';

    protected static ?string $modelLabel = 'Note de frais';

    protected static ?string $pluralModelLabel = 'Mes notes de frais';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', auth()->user()->salarie?->id);
    }

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
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseReports::route('/'),
            'view' => ViewExpenseReport::route('/{record}'),
            'edit' => EditExpenseReport::route('/{record}/edit'),
        ];
    }
}
