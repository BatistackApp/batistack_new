<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditThirdParty extends EditRecord
{
    protected static string $resource = ThirdPartyResource::class;
    protected static ?string $breadcrumb = 'Edition';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edition du tiers: '.$this->record->legal_name;
    }
}
