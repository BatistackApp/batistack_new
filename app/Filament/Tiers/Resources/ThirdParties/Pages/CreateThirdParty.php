<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThirdParty extends CreateRecord
{
    protected static string $resource = ThirdPartyResource::class;
    protected static ?string $title = 'Créer un nouveau tiers';
    protected static ?string $breadcrumb = 'Création';
}
