<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Pages;

use App\Filament\Laser\Resources\LaserQuotes\LaserQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaserQuote extends EditRecord
{
    protected static string $resource = LaserQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }
}
