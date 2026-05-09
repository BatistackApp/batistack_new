<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;
    protected static ?string $title = 'Création d\'une configuration';
    protected static ?string $breadcrumb = 'Création';
}
