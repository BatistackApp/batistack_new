<?php

namespace App\Filament\Commerce\Resources\Payments;

use App\Filament\Commerce\Resources\Payments\Pages\CreatePayment;
use App\Filament\Commerce\Resources\Payments\Pages\EditPayment;
use App\Filament\Commerce\Resources\Payments\Pages\ListPayments;
use App\Filament\Commerce\Resources\Payments\Pages\ViewPayment;
use App\Filament\Commerce\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Commerce\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Commerce\Resources\Payments\Tables\PaymentsTable;
use App\Models\Commerce\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Bank;
    protected static ?string $navigationLabel = 'Paiements';
    protected static ?string $modelLabel = 'Paiement';
    protected static ?string $pluralModelLabel = 'Paiements';
    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
