<?php

namespace App\Filament\Technicien\Resources\ClientEquipment\Pages;

use App\Filament\Technicien\Resources\ClientEquipment\ClientEquipmentResource;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewClientEquipment extends ViewRecord
{
    protected static string $resource = ClientEquipmentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return static::getResource()::infolist($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
