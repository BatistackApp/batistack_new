<?php

namespace App\Filament\Resources\VatRates\Pages;

use App\Filament\Resources\VatRates\VatRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVatRate extends CreateRecord
{
    protected static string $resource = VatRateResource::class;
    protected static ?string $title = 'Création d\'un taux de TVA';
    protected static ?string $breadcrumb = 'Création';
}
