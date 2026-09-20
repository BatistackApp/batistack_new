<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class LaserInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('due_date')
                ->label('Date d\'échéance')
                ->native(false)
                ->visibleOn('edit'),
        ]);
    }
}
