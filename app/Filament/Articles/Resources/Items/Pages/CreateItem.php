<?php

namespace App\Filament\Articles\Resources\Items\Pages;

use App\Filament\Articles\Resources\Items\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
}
