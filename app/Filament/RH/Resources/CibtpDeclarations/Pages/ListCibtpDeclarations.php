<?php

namespace App\Filament\RH\Resources\CibtpDeclarations\Pages;

use App\Filament\RH\Resources\CibtpDeclarations\CibtpDeclarationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCibtpDeclarations extends ListRecords
{
    protected static string $resource = CibtpDeclarationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
