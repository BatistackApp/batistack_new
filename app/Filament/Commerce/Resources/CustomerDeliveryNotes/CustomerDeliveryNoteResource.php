<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\CreateCustomerDeliveryNote;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\EditCustomerDeliveryNote;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages\ListCustomerDeliveryNotes;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas\CustomerDeliveryNoteForm;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Tables\CustomerDeliveryNotesTable;
use App\Models\CustomerDeliveryNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerDeliveryNoteResource extends Resource
{
    protected static ?string $model = CustomerDeliveryNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CustomerDeliveryNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerDeliveryNotesTable::configure($table);
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
            'index' => ListCustomerDeliveryNotes::route('/'),
            'create' => CreateCustomerDeliveryNote::route('/create'),
            'edit' => EditCustomerDeliveryNote::route('/{record}/edit'),
        ];
    }
}
