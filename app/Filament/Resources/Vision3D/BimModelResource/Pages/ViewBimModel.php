<?php

namespace App\Filament\Resources\Vision3D\BimModelResource\Pages;

use App\Filament\Resources\Vision3D\BimModelResource;
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
