<?php

namespace App\Filament\Vision3D\Resources\BimModelResource\Pages;

use App\Filament\Vision3D\Resources\BimModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBimModel extends ViewRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
