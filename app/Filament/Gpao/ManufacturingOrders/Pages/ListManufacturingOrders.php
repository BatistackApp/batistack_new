<?php

namespace App\Filament\Gpao\ManufacturingOrders\Pages;

use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Vue Kanban')
                ->icon('heroicon-o-view-columns')
                ->url(fn (): string => KanbanManufacturingOrders::getUrl()),
            CreateAction::make(),
        ];
    }
}
