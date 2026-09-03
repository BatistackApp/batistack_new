<?php

namespace App\Filament\Resources\GeneratedDocuments\Pages;

use App\Filament\Resources\GeneratedDocuments\GeneratedDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListGeneratedDocuments extends ListRecords
{
    protected static string $resource = GeneratedDocumentResource::class;

    protected static ?string $title = 'Gestion Documentaire (GED)';

    protected static ?string $breadcrumb = 'Documents';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
