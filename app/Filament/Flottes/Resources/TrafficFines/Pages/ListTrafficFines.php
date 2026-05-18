<?php

namespace App\Filament\Flottes\Resources\TrafficFines\Pages;

use App\Filament\Flottes\Resources\TrafficFines\TrafficFineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrafficFines extends ListRecords
{
    protected static string $resource = TrafficFineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
