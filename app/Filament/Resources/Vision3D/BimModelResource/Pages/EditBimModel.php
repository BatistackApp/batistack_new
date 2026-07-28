<?php

namespace App\Filament\Resources\Vision3D\BimModelResource\Pages;

use App\Filament\Resources\Vision3D\BimModelResource;
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
