<?php

namespace App\Filament\Articles\Resources\Store\Pages;

use App\Filament\Articles\Resources\Store\StoreResource;
use App\Filament\Articles\Resources\Store\StoreTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListStoreItems extends ListRecords
{
    protected static string $resource = StoreResource::class;

    public function table(Table $table): Table
    {
        return StoreTable::configure($table);
    }
}
