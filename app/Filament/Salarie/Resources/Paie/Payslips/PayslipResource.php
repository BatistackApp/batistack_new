<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips;

use App\Filament\Salarie\Resources\Paie\Payslips\Pages\CreatePayslip;
use App\Filament\Salarie\Resources\Paie\Payslips\Pages\EditPayslip;
use App\Filament\Salarie\Resources\Paie\Payslips\Pages\ListPayslips;
use App\Filament\Salarie\Resources\Paie\Payslips\Pages\ViewPayslip;
use App\Filament\Salarie\Resources\Paie\Payslips\Schemas\PayslipForm;
use App\Filament\Salarie\Resources\Paie\Payslips\Schemas\PayslipInfolist;
use App\Filament\Salarie\Resources\Paie\Payslips\Tables\PayslipsTable;
use App\Models\Paie\Payslip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    protected static ?string $recordTitleAttribute = 'period';

    public static function form(Schema $schema): Schema
    {
        return PayslipForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayslipInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayslipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', auth()->user()->salarie?->id)
            ->whereIn('status', [\App\Enums\Paie\PayslipStatus::VALIDATED, \App\Enums\Paie\PayslipStatus::PAID]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayslips::route('/'),
            'view' => ViewPayslip::route('/{record}'),
        ];
    }
}
