<?php

namespace App\Filament\Interventions\Pages;

use App\Enums\Core\SignatureType;
use App\Filament\Interventions\MaintenanceContractResource;
use App\Models\Interventions\MaintenanceContract;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureService;
use App\Services\Interventions\MaintenanceContractDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewMaintenanceContract extends ViewRecord
{
    protected static string $resource = MaintenanceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('send_contract_for_signature')
                    ->label('Envoyer le contrat pour signature')
                    ->icon(Phosphor::Envelope)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer le contrat d\'entretien')
                    ->modalDescription('Le contrat PDF sera généré et une demande de signature sera envoyée.')
                    ->form([
                        Toggle::make('is_multi')
                            ->label('Signature multi-signataires')
                            ->default(false)
                            ->live(),
                        Repeater::make('signers')
                            ->label('Signataires')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required(),
                                Select::make('role')
                                    ->label('Rôle')
                                    ->options([
                                        'Signataire' => 'Signataire',
                                        'Client' => 'Client',
                                        'Manager' => 'Manager',
                                        'Autre' => 'Autre',
                                    ])
                                    ->default('Signataire'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un signataire')
                            ->visible(fn (Get $get) => $get('is_multi')),
                    ])
                    ->action(function (MaintenanceContract $record, array $data, MaintenanceContractDocumentService $service, SignatureService $signatureService) {
                        $path = $service->generateContractPdf($record);

                        if ($data['is_multi'] ?? false) {
                            $signatureService->requestMultiSignature(
                                model: $record,
                                type: SignatureType::AUTOGRAPH,
                                signers: $data['signers'],
                                documentPath: $path,
                            );

                            Notification::make()
                                ->title('Contrat envoyé')
                                ->body('Une demande de signature multi-signataires a été envoyée.')
                                ->success()
                                ->send();
                        } else {
                            $client = $record->thirdParty;
                            $contact = $client?->getPrimaryContact();
                            $email = $contact?->email ?: $client?->email;
                            $name = $contact ? trim("{$contact->first_name} {$contact->last_name}") : ($client?->name ?? 'Client');

                            if ($email) {
                                $signatureService->requestSignature(
                                    model: $record,
                                    type: SignatureType::AUTOGRAPH,
                                    email: $email,
                                    name: $name,
                                    documentPath: $path,
                                );

                                Notification::make()
                                    ->title('Contrat envoyé')
                                    ->body("Une demande de signature a été envoyée au client ({$email}).")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Contrat généré')
                                    ->body("Le contrat a été généré, mais le client n'a pas d'adresse email renseignée pour l'envoi de la signature.")
                                    ->warning()
                                    ->send();
                            }
                        }
                    }),

                Action::make('download_contract')
                    ->label('Télécharger le contrat PDF')
                    ->icon(Phosphor::FilePdf)
                    ->color('gray')
                    ->action(function (MaintenanceContract $record, MaintenanceContractDocumentService $service, DocumentService $documentService) {
                        $path = $service->generateContractPdf($record);

                        return $documentService->download($path);
                    }),
            ])
                ->label('Contrat')
                ->icon(Phosphor::FileText)
                ->button()
                ->color('gray'),
        ];
    }
}
