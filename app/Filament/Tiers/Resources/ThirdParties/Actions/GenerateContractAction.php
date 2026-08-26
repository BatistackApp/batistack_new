<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Tiers\ContractingGuardService;
use App\Services\Tiers\TiersDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
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
                $guard = app(ContractingGuardService::class);
                $check = $guard->check($record);

                if ($check['blocked']) {
                    Notification::make()
                        ->title('Contrat bloqué')
                        ->body($check['reason'])
                        ->danger()
                        ->send();

                    return;
                }

                if ($check['warned']) {
                    Notification::make()
                        ->title('Avertissement')
                        ->body($check['reason'])
                        ->warning()
                        ->send();
                }

                $relativePath = $service->generateContract($record);
                $absolutePath = Storage::disk('public')->path($relativePath);

                // Sauvegarde du document
                $document = ThirdPartyDocument::updateOrCreate(
                    ['third_party_id' => $record->id, 'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE],
                    ['expiration_date' => now()->addYear(), 'status' => ThirdPartyDocumentStatus::VALID]
                );

                $document->clearMediaCollection('third_party_documents');
                $media = $document->addMedia($absolutePath)->toMediaCollection('third_party_documents');

                Notification::make()
                    ->title('Contrat généré et sauvegardé dans les documents du sous-traitant')
                    ->success()
                    ->send();

                // On télécharge le fichier depuis son nouvel emplacement (Spatie a déplacé le fichier original)
                return \response()->download($media->getPath());
            });
    }
}
