<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EditThirdParty extends EditRecord
{
    protected static string $resource = ThirdPartyResource::class;

    protected static ?string $breadcrumb = 'Edition';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Voir')
                ->icon(Phosphor::Eye),
            DeleteAction::make()
                ->label('Supprimer')
                ->icon(Phosphor::Trash),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edition du tiers: '.$this->record->legal_name;
    }
}
