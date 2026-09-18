<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Pages;

use App\Filament\Laser\Resources\LaserQuotes\LaserQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaserQuotes extends ListRecords
{
    protected static string $resource = LaserQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
