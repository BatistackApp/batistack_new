<?php

namespace App\Filament\Resources\Vision3D\BimModelResource\Pages;

use App\Filament\Resources\Vision3D\BimModelResource;
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
