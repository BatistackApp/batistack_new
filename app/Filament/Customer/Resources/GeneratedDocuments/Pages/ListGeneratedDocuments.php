<?php

namespace App\Filament\Customer\Resources\GeneratedDocuments\Pages;

use App\Filament\Customer\Resources\GeneratedDocuments\GeneratedDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListGeneratedDocuments extends ListRecords
{
    protected static string $resource = GeneratedDocumentResource::class;

    protected static ?string $title = 'Mes Documents';

    protected static ?string $breadcrumb = 'Mes Documents';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
