<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\InterventionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIntervention extends ViewRecord
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('sign')
                ->label('Faire Signer')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(fn (\App\Models\Interventions\Intervention $record) => $record->status === \App\Enums\Interventions\InterventionStatus::TERMINEE)
                ->form([
                    \Filament\Forms\Components\TextInput::make('signer_name')
                        ->label('Nom du signataire (Client)')
                        ->required(),
                    \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('signature')
                        ->label('Signature')
                        ->required(),
                ])
                ->action(function (\App\Models\Interventions\Intervention $record, array $data, \App\Services\Core\SignatureService $signatureService) {
                    $signatureService->sign(
                        model: $record,
                        signatureData: $data['signature'],
                        type: \App\Enums\Core\SignatureType::AUTOGRAPH,
                        additionalMetadata: [
                            'signer_name' => $data['signer_name'],
                            'role' => 'client',
                        ]
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Intervention signée et scellée avec succès !')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('download_pdf')
                ->label('Télécharger le Bon')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (\App\Models\Interventions\Intervention $record, \App\Services\Interventions\InterventionPdfService $pdfService) {
                    $path = $pdfService->generatePdf($record);

                    return response()->download($path);
                }),
            Actions\Action::make('create_invoice')
                ->label('Générer Facture')
                ->icon('heroicon-o-document-currency-euro')
                ->color('warning')
                ->visible(fn (\App\Models\Interventions\Intervention $record) => $record->status === \App\Enums\Interventions\InterventionStatus::TERMINEE)
                ->requiresConfirmation()
                ->action(function (\App\Models\Interventions\Intervention $record, \App\Services\Interventions\InterventionBillingService $billingService) {
                    try {
                        $invoice = $billingService->generateInvoice($record);
                        if ($invoice) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture générée avec succès !')
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur lors de la facturation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
