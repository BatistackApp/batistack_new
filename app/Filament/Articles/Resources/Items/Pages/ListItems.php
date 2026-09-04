<?php

namespace App\Filament\Articles\Resources\Items\Pages;

use App\Filament\Articles\Actions\DestockKitAction;
use App\Filament\Articles\Resources\Items\ItemResource;
use App\Services\Articles\InventoryService;
use App\Services\Core\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV Inventaire')
                ->icon(Phosphor::FileCsv)
                ->color('success')
                ->action(function (InventoryService $service) {
                    $csv = $service->generateValuationCsv();

                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'valorisation_inventaire_'.now()->format('YmdHis').'.csv');
                }),

            Action::make('export_pdf')
                ->label('Export PDF Inventaire')
                ->icon(Phosphor::FilePdf)
                ->color('danger')
                ->action(function (InventoryService $service) {
                    $path = $service->generateValuationPdf();

                    return app(DocumentService::class)->download($path);
                }),

            DestockKitAction::make(),
            CreateAction::make(),
        ];
    }
}
