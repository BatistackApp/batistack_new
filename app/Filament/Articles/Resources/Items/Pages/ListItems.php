<?php

namespace App\Filament\Articles\Resources\Items\Pages;

use App\Filament\Articles\Resources\Items\ItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_csv')
                ->label('Export CSV Inventaire')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::FileCsv)
                ->color('success')
                ->action(function (\App\Services\Articles\InventoryService $service) {
                    $csv = $service->generateValuationCsv();
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'valorisation_inventaire_' . now()->format('YmdHis') . '.csv');
                }),
                
            \Filament\Actions\Action::make('export_pdf')
                ->label('Export PDF Inventaire')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::FilePdf)
                ->color('danger')
                ->action(function (\App\Services\Articles\InventoryService $service) {
                    $path = $service->generateValuationPdf();
                    return response()->download(storage_path('app/public/' . $path));
                }),

            CreateAction::make(),
        ];
    }
}
