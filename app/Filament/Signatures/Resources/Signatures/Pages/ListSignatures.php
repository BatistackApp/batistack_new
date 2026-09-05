<?php

namespace App\Filament\Signatures\Resources\Signatures\Pages;

use App\Filament\Signatures\Resources\Signatures\SignatureResource;
use Filament\Resources\Pages\ListRecords;

class ListSignatures extends ListRecords
{
    protected static string $resource = SignatureResource::class;
}
