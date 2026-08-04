<?php

namespace App\Filament\RH\Resources\PayrollExports;

use App\Filament\RH\Resources\PayrollExports\Pages\CreatePayrollExport;
use App\Filament\RH\Resources\PayrollExports\Pages\EditPayrollExport;
use App\Filament\RH\Resources\PayrollExports\Pages\ListPayrollExports;
use App\Filament\RH\Resources\PayrollExports\Pages\ViewPayrollExport;
use App\Filament\RH\Resources\PayrollExports\Schemas\PayrollExportForm;
use App\Filament\RH\Resources\PayrollExports\Schemas\PayrollExportInfolist;
use App\Filament\RH\Resources\PayrollExports\Tables\PayrollExportsTable;
use App\Models\RH\PayrollExport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollExportResource extends Resource
{
    protected static ?string $model = PayrollExport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static \UnitEnum|string|null $navigationGroup = 'Déclarations & Exports';

    protected static ?string $navigationLabel = 'Exports Paie';
    protected static ?string $modelLabel = 'Export Paie';
    protected static ?string $pluralModelLabel = 'Exports Paie';

    public static function form(Schema $schema): Schema
    {
        return PayrollExportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollExportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollExportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\RH\Resources\PayrollExports\RelationManagers\VariablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollExports::route('/'),
            'create' => CreatePayrollExport::route('/create'),
            'view' => ViewPayrollExport::route('/{record}'),
            'edit' => EditPayrollExport::route('/{record}/edit'),
        ];
    }
}
