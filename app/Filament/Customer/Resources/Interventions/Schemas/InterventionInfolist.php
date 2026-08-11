<?php

namespace App\Filament\Customer\Resources\Interventions\Schemas;

use Filament\Schemas\Schema;

class InterventionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\TextEntry::make('reference')->label('Référence'),
                \Filament\Infolists\Components\TextEntry::make('status')->label('Statut'),
                \Filament\Infolists\Components\TextEntry::make('description')->label('Description'),
                \Filament\Infolists\Components\TextEntry::make('scheduled_at')->label('Prévu le')->dateTime(),
                \Filament\Infolists\Components\TextEntry::make('clientEquipment.name')->label('Équipement'),
            ]);
    }
}
