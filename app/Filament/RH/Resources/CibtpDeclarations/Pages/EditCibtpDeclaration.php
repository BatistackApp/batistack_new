<?php

namespace App\Filament\RH\Resources\CibtpDeclarations\Pages;

use App\Filament\RH\Resources\CibtpDeclarations\CibtpDeclarationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCibtpDeclaration extends EditRecord
{
    protected static string $resource = CibtpDeclarationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
