<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Pages;

use App\Filament\Laser\Resources\LaserQuotes\LaserQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaserQuote extends ViewRecord
{
    protected static string $resource = LaserQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
