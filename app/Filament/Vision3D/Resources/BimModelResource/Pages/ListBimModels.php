<?php

namespace App\Filament\Vision3D\Resources\BimModelResource\Pages;

use App\Filament\Vision3D\Resources\BimModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBimModels extends ListRecords
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
