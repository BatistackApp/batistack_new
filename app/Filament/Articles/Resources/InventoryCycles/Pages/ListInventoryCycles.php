<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Pages;

use App\Filament\Articles\Resources\InventoryCycles\InventoryCycleResource;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCycles extends ListRecords
{
    protected static string $resource = InventoryCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('generateCycle')
                ->label('Générer un comptage tournant')
                ->icon('heroicon-o-sparkles')
                ->form([
                    \Filament\Forms\Components\Select::make('warehouse_id')
                        ->label('Dépôt')
                        ->options(\App\Models\Articles\Warehouse::pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('item_count')
                        ->label('Nombre d\'articles')
                        ->numeric()
                        ->default(10)
                        ->required(),
                ])
                ->action(function (array $data, \App\Services\Articles\CycleCountingService $service) {
                    $warehouse = \App\Models\Articles\Warehouse::find($data['warehouse_id']);
                    $service->generateCycle($warehouse, $data['item_count'], auth()->user());
                    \Filament\Notifications\Notification::make()->title('Comptage généré')->success()->send();
                }),
        ];
    }
}
