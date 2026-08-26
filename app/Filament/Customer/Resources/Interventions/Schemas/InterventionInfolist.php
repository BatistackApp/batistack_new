<?php

namespace App\Filament\Customer\Resources\Interventions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InterventionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference')->label('Référence'),
                TextEntry::make('status')->label('Statut'),
                TextEntry::make('description')->label('Description'),
                TextEntry::make('scheduled_at')->label('Prévu le')->dateTime(),
                TextEntry::make('clientEquipment.name')->label('Équipement'),
            ]);
    }
}
