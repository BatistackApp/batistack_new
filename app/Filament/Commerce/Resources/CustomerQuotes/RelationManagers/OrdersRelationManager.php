<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\RelationManagers;

use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'order';

    protected static ?string $relatedResource = CustomerOrderResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
