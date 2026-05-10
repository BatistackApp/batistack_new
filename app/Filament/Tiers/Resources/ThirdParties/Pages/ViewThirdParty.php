<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SynchronizeSirenAction;
use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewThirdParty extends ViewRecord
{
    protected static string $resource = ThirdPartyResource::class;
    protected static ?string $breadcrumb = 'Fiche';


    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            PrintAction::make('details'),
            SynchronizeSirenAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Fiche du tiers: '.$this->record->legal_name;
    }
}
