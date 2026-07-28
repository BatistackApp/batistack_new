<?php

namespace App\Filament\Vision3D\Resources\BimModelResource\Pages;

use App\Filament\Vision3D\Resources\BimModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBimModel extends EditRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
