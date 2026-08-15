<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\AssetMaintenanceTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListAssetMaintenanceTickets extends ListRecords
{
    protected static string $resource = AssetMaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
