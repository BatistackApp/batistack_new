<?php

namespace App\Filament\Subcontractor\Resources\GeneratedDocuments\Pages;

use App\Filament\Subcontractor\Resources\GeneratedDocuments\GeneratedDocumentResource;
use App\Models\Core\GeneratedDocument;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
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
                ->url(fn (GeneratedDocument $record): string => route(
                    'subcontractor.documents.generated.preview',
                    ['document' => $record->getKey()]
                ))
                ->openUrlInNewTab(),

            Action::make('download')
                ->label('Télécharger')
                ->icon(Phosphor::DownloadSimple)
                ->color('success')
                ->url(fn (GeneratedDocument $record): string => route(
                    'subcontractor.documents.generated.download',
                    ['document' => $record->getKey()]
                ))
                ->openUrlInNewTab(),
        ];
    }
}
