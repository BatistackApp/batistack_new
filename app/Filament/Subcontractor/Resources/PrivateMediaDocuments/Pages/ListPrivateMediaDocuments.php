<?php

namespace App\\Filament\\Subcontractor\\Resources\\PrivateMediaDocuments\\Pages;

use App\\Filament\\Subcontractor\\Resources\\PrivateMediaDocuments\\PrivateMediaDocumentResource;
use Filament\\Resources\\Pages\\ListRecords;

class ListPrivateMediaDocuments extends ListRecords
{
    protected static string $resource = PrivateMediaDocumentResource::class;

    protected static ?string $title = 'Documents contractuels';

    protected static ?string $breadcrumb = 'Documents contractuels';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
