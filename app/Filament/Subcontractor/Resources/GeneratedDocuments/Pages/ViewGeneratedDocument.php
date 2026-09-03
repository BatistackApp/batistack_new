<?php

namespace App\Filament\Subcontractor\Resources\GeneratedDocuments\Pages;

use App\Filament\Subcontractor\Resources\GeneratedDocuments\GeneratedDocumentResource;
use App\Models\Core\GeneratedDocument;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewGeneratedDocument extends ViewRecord
{
    protected static string $resource = GeneratedDocumentResource::class;

    protected static ?string $title = 'Détail du document';

    protected static ?string $breadcrumb = 'Détail';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Aperçu PDF')
                ->icon(Phosphor::Eye)
                ->color('info')
                ->url(function () {
                    /** @var GeneratedDocument $record */
                    $record = $this->record;

                    return $record->temporaryUrl() ?? '#';
                })
                ->openUrlInNewTab(),

            Action::make('download')
                ->label('Télécharger')
                ->icon(Phosphor::DownloadSimple)
                ->color('success')
                ->action(function () {
                    /** @var GeneratedDocument $record */
                    $record = $this->record;

                    if (! $record->file_path) {
                        return;
                    }

                    return Storage::disk($record->file_disk)->download($record->file_path, $record->file_name.'.pdf');
                }),
        ];
    }
}
