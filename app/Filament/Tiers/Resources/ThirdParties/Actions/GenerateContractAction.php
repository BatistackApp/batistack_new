<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\TiersDocumentService;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Http\Response;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class GenerateContractAction
{
    public static function make(): Action
    {
        return Action::make('generate_contract')
            ->label('Générer un contrat de sous traitant')
            ->icon(Phosphor::FileText)
            ->visible(fn (ThirdParty $record) => $record->type === ThirdPartyType::SUBCONTRACTOR)
            ->action(function (ThirdParty $record, TiersDocumentService $service) {
                $relativePath = $service->generateContract($record);
                $absolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($relativePath);

                // Sauvegarde du document
                $document = \App\Models\Tiers\ThirdPartyDocument::create([
                    'third_party_id' => $record->id,
                    'type' => 'contrat_sous_traitance',
                    'expiration_date' => now()->addYear(),
                    'status' => 'valid',
                ]);

                $media = $document->addMedia($absolutePath)->toMediaCollection('third_party_documents');

                \Filament\Notifications\Notification::make()
                    ->title('Contrat généré et sauvegardé dans les documents du sous-traitant')
                    ->success()
                    ->send();

                // On télécharge le fichier depuis son nouvel emplacement (Spatie a déplacé le fichier original)
                return \response()->download($media->getPath());
            });
    }
}
