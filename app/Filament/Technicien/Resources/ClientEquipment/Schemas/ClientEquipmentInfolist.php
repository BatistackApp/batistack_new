<?php

namespace App\Filament\Technicien\Resources\ClientEquipment\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClientEquipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(2)
                    ->components([
                        TextEntry::make('name')
                            ->label('Nom'),

                        TextEntry::make('brand')
                            ->label('Marque')
                            ->placeholder('—'),

                        TextEntry::make('serial_number')
                            ->label('Numéro de série')
                            ->fontFamily('mono')
                            ->placeholder('—'),

                        TextEntry::make('installation_date')
                            ->label('Date d\'installation')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                    ]),

                Section::make('Client')
                    ->columns(2)
                    ->components([
                        TextEntry::make('thirdParty.name')
                            ->label('Nom du client'),

                        TextEntry::make('thirdParty.email')
                            ->label('Email')
                            ->placeholder('—'),

                        TextEntry::make('thirdParty.phone')
                            ->label('Téléphone')
                            ->placeholder('—'),

                        TextEntry::make('thirdParty.address_full')
                            ->label('Adresse')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
