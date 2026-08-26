<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances;

use App\Filament\Salarie\Resources\ExpenseAdvances\Pages\CreateExpenseAdvance;
use App\Filament\Salarie\Resources\ExpenseAdvances\Pages\EditExpenseAdvance;
use App\Filament\Salarie\Resources\ExpenseAdvances\Pages\ListExpenseAdvances;
use App\Filament\Salarie\Resources\ExpenseAdvances\Schemas\ExpenseAdvanceForm;
use App\Filament\Salarie\Resources\ExpenseAdvances\Tables\ExpenseAdvancesTable;
use App\Models\RH\ExpenseAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseAdvanceResource extends Resource
{
    protected static ?string $model = ExpenseAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Mes Avances sur Frais';

    protected static ?string $modelLabel = 'Avance sur frais';

    protected static ?string $pluralModelLabel = 'Avances sur frais';

    public static function form(Schema $schema): Schema
    {
        return ExpenseAdvanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseAdvancesTable::configure($table);
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
            'index' => ListExpenseAdvances::route('/'),
            'create' => CreateExpenseAdvance::route('/create'),
            'edit' => EditExpenseAdvance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $employeeId = auth()->user()->getEmployeeIdOrFail();

        return parent::getEloquentQuery()->where('employee_id', $employeeId);
    }
}
