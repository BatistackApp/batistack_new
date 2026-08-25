<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClientEquipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('brand')->label('Marque'),
                TextEntry::make('serial_number')->label('Numéro de Série'),
                TextEntry::make('installation_date')->label('Date d\'installation')->date(),
            ]);
    }
}
