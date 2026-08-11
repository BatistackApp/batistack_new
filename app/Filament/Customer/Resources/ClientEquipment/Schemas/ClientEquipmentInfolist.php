<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Schemas;

use Filament\Schemas\Schema;

class ClientEquipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\TextEntry::make('name')->label('Nom'),
                \Filament\Infolists\Components\TextEntry::make('brand')->label('Marque'),
                \Filament\Infolists\Components\TextEntry::make('serial_number')->label('Numéro de Série'),
                \Filament\Infolists\Components\TextEntry::make('installation_date')->label('Date d\'installation')->date(),
            ]);
    }
}
