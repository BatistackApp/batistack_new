<?php

namespace App\Filament\Paie\Resources\Paie\AdvancePayments;

use App\Filament\Paie\Resources\Paie\AdvancePayments\Pages\CreateAdvancePayment;
use App\Filament\Paie\Resources\Paie\AdvancePayments\Pages\EditAdvancePayment;
use App\Filament\Paie\Resources\Paie\AdvancePayments\Pages\ListAdvancePayments;
use App\Filament\Paie\Resources\Paie\AdvancePayments\Schemas\AdvancePaymentForm;
use App\Filament\Paie\Resources\Paie\AdvancePayments\Tables\AdvancePaymentsTable;
use App\Models\Paie\AdvancePayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdvancePaymentResource extends Resource
{
    protected static ?string $model = AdvancePayment::class;

    protected static ?string $modelLabel = 'Acompte';

    protected static ?string $pluralModelLabel = 'Acomptes';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion de la Paie';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AdvancePaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvancePaymentsTable::configure($table);
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
            'index' => ListAdvancePayments::route('/'),
            'create' => CreateAdvancePayment::route('/create'),
            'edit' => EditAdvancePayment::route('/{record}/edit'),
        ];
    }
}
