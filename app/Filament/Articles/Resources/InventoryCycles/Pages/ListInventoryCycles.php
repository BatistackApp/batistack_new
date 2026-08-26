<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Pages;

use App\Filament\Articles\Resources\InventoryCycles\InventoryCycleResource;
use App\Models\Articles\Warehouse;
use App\Services\Articles\CycleCountingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCycles extends ListRecords
{
    protected static string $resource = InventoryCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateCycle')
                ->label('Générer un comptage tournant')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Select::make('warehouse_id')
                        ->label('Dépôt')
                        ->options(Warehouse::pluck('name', 'id'))
                        ->required(),
                    TextInput::make('item_count')
                        ->label('Nombre d\'articles')
                        ->numeric()
                        ->default(10)
                        ->required(),
                ])
                ->action(function (array $data, CycleCountingService $service) {
                    $warehouse = Warehouse::find($data['warehouse_id']);
                    $service->generateCycle($warehouse, $data['item_count'], auth()->user());
                    Notification::make()->title('Comptage généré')->success()->send();
                }),
        ];
    }
}
