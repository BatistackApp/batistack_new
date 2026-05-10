<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;
    protected static ?string $title = 'Création d\'une unité de mesure';
    protected static ?string $breadcrumb = 'Création';
}
